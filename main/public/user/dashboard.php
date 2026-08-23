<?php
/**
 * dashboard.php (User Dashboard - SPA Shell)
 *
 * Premium Tenant Control Panel for nodexGosolutions.
 * Fully styled with native Bootstrap 5.3 UI components.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../php/db.php';

secureSession();

$hostDomain = $_SERVER['HTTP_HOST'] ?? 'nodexplatform.com.ng';
if (str_contains($hostDomain, ':')) {
    $hostDomain = explode(':', $hostDomain)[0];
}

if (!isset($_SESSION['email'])) {
    header('Location: /login');
    exit;
}

$user = $conn->selectOne('users', ['email' => $_SESSION['email']]);
if (!$user) {
    header('Location: /login');
    exit;
}

$successMessage = '';
$errorMessage = '';
if (empty($_SESSION['profile_csrf'])) $_SESSION['profile_csrf'] = bin2hex(random_bytes(24));

/** Store a validated avatar using the actual image MIME type, never the client filename. */
function storeTenantAvatar(array $upload): string
{
    if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('Please choose a valid profile picture.');
    if (($upload['size'] ?? 0) > 5 * 1024 * 1024) throw new RuntimeException('Profile pictures must be 5 MB or smaller.');
    $info = @getimagesize((string)$upload['tmp_name']);
    $types = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
    $mime = (string)($info['mime'] ?? '');
    if (!$info || !isset($types[$mime])) throw new RuntimeException('Use a valid JPG, PNG, GIF, or WebP image.');
    $dir = __DIR__ . '/../../../uploads/avatars';
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) throw new RuntimeException('Avatar storage is not writable.');
    $name = bin2hex(random_bytes(16)) . '.' . $types[$mime];
    if (!move_uploaded_file((string)$upload['tmp_name'], $dir . '/' . $name)) throw new RuntimeException('Unable to save the profile picture.');
    @chmod($dir . '/' . $name, 0644);
    return '/uploads/avatars/' . $name;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (in_array((string)($_POST['action'] ?? ''), ['update_profile','update_password'], true) && !hash_equals((string)($_SESSION['profile_csrf'] ?? ''), (string)($_POST['csrf'] ?? ''))) {
        $errorMessage = 'Security token expired. Refresh and try again.';
    }
    if ($errorMessage === '' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $fullname = cleanInput($_POST['fullname'] ?? '');
        $avatarBase64 = $_POST['avatar_base64'] ?? '';
        $avatarUrl = $user['avatar'] ?? '';

        if (isset($_FILES['avatar_file']) && ($_FILES['avatar_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try { $avatarUrl = storeTenantAvatar($_FILES['avatar_file']); } catch (Throwable $e) { $errorMessage = $e->getMessage(); }
        } elseif (!empty($avatarBase64) && str_starts_with($avatarBase64, '/uploads/avatars/')) {
            $avatarUrl = $avatarBase64;
        }

        if (empty($fullname)) {
            $errorMessage = 'Full name field cannot be empty.';
        }

        if (empty($errorMessage)) {
            $conn->update('users', [
                'fullname' => $fullname,
                'avatar' => $avatarUrl,
                'updated_at' => date('Y-m-d H:i:s')
            ], ['email' => $_SESSION['email']]);

            $_SESSION['fullname'] = $fullname;
            $user = $conn->selectOne('users', ['email' => $_SESSION['email']]);
            $successMessage = 'Profile information successfully updated!';
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'update_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $errorMessage = 'All password fields are strictly required.';
        } elseif ($newPassword !== $confirmPassword) {
            $errorMessage = 'New password and confirmation fields do not match.';
        } elseif (strlen($newPassword) < 6) {
            $errorMessage = 'New password must be at least 6 characters in length.';
        } else {
            if (password_verify($currentPassword, $user['password'])) {
                $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $conn->update('users', [
                    'password' => $hashedNewPassword,
                    'updated_at' => date('Y-m-d H:i:s')
                ], ['email' => $_SESSION['email']]);

                $successMessage = 'Your password has been changed successfully!';
            } else {
                $errorMessage = 'Invalid current password entered.';
            }
        }
    }
}

$subscriptionStatus = checkAndUpdateSubscription($user, $conn);
$daysRemaining = 0;
if ($subscriptionStatus === 'trial') {
    $trialEndTimestamp = strtotime($user['trial_end'] ?? '');
    $secondsLeft = $trialEndTimestamp - time();
    $daysRemaining = max(0, (int)ceil($secondsLeft / 86400));
}

$userWorkspaces = getUserWorkspaces((int)$user['id']);
$activeWorkspace = getActiveWorkspace($user);
$activeWorkspaceId = (int)$activeWorkspace['id'];

$conn->createTable('workspace_members');
$workspaceMembers = $conn->select('workspace_members', ['workspace_id' => $activeWorkspaceId]) ?: [];

$conn->createTable('websites');
$allUserWebsites = $conn->select('websites', ['user_id' => $user['id']]) ?: [];

$myWebsites = array_values(array_filter($allUserWebsites, function($web) use ($activeWorkspaceId) {
    if (!isset($web['workspace_id']) || empty($web['workspace_id'])) {
        return true;
    }
    return (int)$web['workspace_id'] === $activeWorkspaceId;
}));
$websitesCount = count($myWebsites);

function getDirectorySize(string $path): int {
    $totalSize = 0;
    if (!is_dir($path)) return 0;
    $files = @scandir($path);
    if ($files === false) return 0;
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $filePath = $path . '/' . $file;
            $totalSize += is_dir($filePath) ? getDirectorySize($filePath) : (int)filesize($filePath);
        }
    }
    return $totalSize;
}

function formatBytes(int $bytes): string {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 2) . ' KB';
    if ($bytes < 1073741824) return round($bytes / 1048576, 2) . ' MB';
    return round($bytes / 1073741824, 2) . ' GB';
}

$totalBytes = 0;
$publicDir = __DIR__ . '/../../../public';
foreach ($myWebsites as $web) {
    $wSub = $web['subdomain'] ?? '';
    if (!empty($wSub)) {
        $tenantPath = $publicDir . '/' . $wSub;
        if (is_dir($tenantPath)) $totalBytes += getDirectorySize($tenantPath);
    }
}
$formattedStorage = formatBytes($totalBytes);

$userTraffic = 0;
$conn->createTable('traffic');
$trafficRecords = $conn->select('traffic') ?: [];
foreach ($trafficRecords as $tRecord) {
    $tWebId = $tRecord['website_id'] ?? '';
    foreach ($myWebsites as $web) {
        if ((string)($web['id'] ?? '') === (string)$tWebId) {
            $userTraffic += (int)($tRecord['visits'] ?? 0);
            break;
        }
    }
}
$formattedTraffic = number_format($userTraffic) . ' Visits';

$conn->createTable('plans');
$dbPlans = $conn->select('plans', ['is_active' => 1]) ?: [];

$allTemplates = getDiscoveredTemplates();
$activeTemplates = array_filter($allTemplates, function($t) {
    return strtolower((string)($t['status'] ?? 'active')) === 'active';
});

$conn->createTable('categories');
$userCategoriesList = $conn->select('categories') ?: [];

// SPA / Direct Access Logic
$dashboardContext = true;
$activeTab = $requestedTab ?? ($_GET['tab'] ?? 'overview');

$tabs = [
    'overview'           => ['label' => 'Overview',        'icon' => 'fa-chart-pie'],
    'subscriptions'      => ['label' => 'Subscriptions',   'icon' => 'fa-credit-card'],
    'build-website'      => ['label' => 'Website Builder', 'icon' => 'fa-layer-group'],
    'manage-files'       => ['label' => 'File Manager',    'icon' => 'fa-folder-tree'],
    'user-db-manager'    => ['label' => 'Databases',       'icon' => 'fa-database'],
    'monetization'       => ['label' => 'Monetization',    'icon' => 'fa-rectangle-ad'],
    'analytics'          => ['label' => 'Analytics',       'icon' => 'fa-chart-line'],
    'team'               => ['label' => 'Team',            'icon' => 'fa-users'],
    'support'            => ['label' => 'Support Tickets', 'icon' => 'fa-headset'],
    'workspace-settings' => ['label' => 'Settings',        'icon' => 'fa-sliders'],
    'profile'            => ['label' => 'Profile',         'icon' => 'fa-user'],
];
?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Workspace Dashboard | nodexGo</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/grapesjs/dist/css/grapes.min.css">
  <link rel="stylesheet" href="https://releases.transloadit.com/uppy/v4.13.3/uppy.min.css">
  <script src="https://unpkg.com/grapesjs"></script>

  <link rel="stylesheet" href="/assets/css/app.css">
  <link rel="stylesheet" href="/assets/css/nx-secure.css">
  <link rel="stylesheet" href="/assets/css/user.css">
  <link rel="stylesheet" href="/assets/css/file-manager.css">
  <link rel="stylesheet" href="/assets/css/subscriptions.css">

  <style>
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background-color: #f8fafc;
    }
    .dashboard-layout {
      display: flex;
      min-height: 100vh;
    }
    .main-content {
      flex: 1;
      padding: 1.5rem;
      overflow-y: auto;
    }
    .command-hero {
      background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
      color: #ffffff;
    }
    .metric-pill {
      font-size: 0.8125rem;
      font-weight: 600;
      color: #475569;
    }
  </style>
</head>
<body class="h-100">

<div class="dashboard-layout">
  <?php require_once __DIR__ . '/../../modul/sidebar.php'; ?>

  <div class="main-content">

    <header class="card border-0 shadow-sm p-3 mb-4 rounded-4 bg-white">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
          <button class="btn btn-primary d-md-none rounded-circle p-0" id="sidebarToggleBtn" type="button" style="width: 40px; height: 40px;">
            <i class="fa-solid fa-bars"></i>
          </button>
          <div>
            <h1 class="h4 fw-bold mb-0 text-dark">Welcome back, <?php echo htmlspecialchars((string)($user['fullname'] ?? '')); ?></h1>
            <p class="text-muted small mb-0 d-none d-sm-block">Manage your workspace subdomains, files, and resources.</p>
          </div>
        </div>
        <div>
          <?php if ($subscriptionStatus === 'trial'): ?>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill fw-semibold">
              <i class="fas fa-clock me-1"></i> FREE TRIAL (Ends: <?php echo date('F d, Y', strtotime($user['trial_end'])); ?>)
            </span>
          <?php elseif ($subscriptionStatus === 'active'): ?>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fw-semibold cursor-pointer" data-bs-toggle="modal" data-bs-target="#pricingModal">
              <i class="fas fa-circle-check me-1"></i> ACTIVE (Plan: <?php echo htmlspecialchars((string)($user['subscription_plan'] ?? 'Growth')); ?>)
            </span>
          <?php else: ?>
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill fw-semibold cursor-pointer" data-bs-toggle="modal" data-bs-target="#pricingModal">
              <i class="fas fa-exclamation-triangle me-1"></i> SUBSCRIPTION REQUIRED
            </span>
          <?php endif; ?>
        </div>
      </div>
    </header>

    <div class="card border-0 shadow-sm p-3 mb-4 rounded-4 bg-white">
      <div class="d-flex align-items-center flex-wrap gap-3">
        <div class="metric-pill"><i class="fa-solid fa-satellite-dish text-info me-2"></i>Workspace online</div>
        <div class="vr d-none d-sm-block"></div>
        <div class="metric-pill"><i class="fa-solid fa-globe text-primary me-2"></i><?php echo (int)$websitesCount; ?> website<?php echo $websitesCount === 1 ? '' : 's'; ?></div>
        <div class="vr d-none d-sm-block"></div>
        <div class="metric-pill"><i class="fa-solid fa-hard-drive text-warning me-2"></i><?php echo htmlspecialchars($formattedStorage); ?> stored</div>
        <div class="vr d-none d-sm-block"></div>
        <div class="metric-pill"><i class="fa-solid fa-chart-line text-success me-2"></i><?php echo htmlspecialchars($formattedTraffic); ?></div>
        <div class="ms-md-auto d-flex gap-2">
          <a class="btn btn-sm btn-outline-secondary rounded-3" href="#manage-files"><i class="fa-solid fa-folder-tree me-1"></i>Open workspace</a>
          <a class="btn btn-sm btn-primary rounded-3" href="#build-website"><i class="fa-solid fa-plus me-1"></i>Deploy site</a>
        </div>
      </div>
    </div>

    <section class="card command-hero border-0 shadow-sm rounded-4 p-4 mb-4">
      <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
          <span class="badge bg-info-subtle text-info text-uppercase fw-bold mb-2">Workspace Command Center</span>
          <h2 class="fw-extrabold mb-1">Build, publish, and operate your websites.</h2>
          <p class="text-white-50 mb-0">Your workspace is active for deployment, file editing, domain configurations, and live analytics.</p>
        </div>
        <div class="d-flex gap-2">
          <a class="btn btn-outline-light rounded-3 text-nowrap" href="#manage-files"><i class="fa-solid fa-code me-2"></i>Edit Files</a>
          <a class="btn btn-primary rounded-3 text-nowrap" href="#build-website"><i class="fa-solid fa-rocket me-2"></i>Launch Website</a>
        </div>
      </div>
    </section>

    <?php if (!empty($successMessage)): ?>
      <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
        <i class="fas fa-circle-check me-2"></i><?php echo htmlspecialchars((string)$successMessage); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    <?php if (!empty($errorMessage)): ?>
      <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars((string)$errorMessage); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php
    foreach ($tabs as $tabId => $tabInfo) {
        $tabFile = __DIR__ . '/tabs/' . $tabId . '.php';
        if (file_exists($tabFile)) {
            include $tabFile;
        }
    }
    ?>

  </div>
</div>

<div class="modal fade" id="pricingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content rounded-4 border-0 shadow">
      <div class="modal-header bg-dark text-white rounded-top-4">
        <h5 class="modal-title fs-6 fw-bold"><i class="fa-solid fa-credit-card me-2 text-primary"></i>Select Subscription Plan</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4 bg-light">
        <div class="row g-3">
          <?php foreach ($dbPlans as $plan): ?>
            <div class="col-12 col-md-4">
              <div class="card h-100 border-0 shadow-sm rounded-3 p-3">
                <h6 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars((string)$plan['name']); ?></h6>
                <div class="fs-4 fw-extrabold text-primary mb-3">$<?php echo htmlspecialchars((string)$plan['price']); ?> <span class="fs-6 fw-normal text-muted">/ mo</span></div>
                <button class="btn btn-primary btn-sm w-100 mt-auto btn-process-payment" data-plan-id="<?php echo htmlspecialchars((string)$plan['id']); ?>">Upgrade Plan</button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="uploadWebsiteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow">
      <div class="modal-header bg-primary text-white rounded-top-4 py-3">
        <h5 class="modal-title fs-6 fw-bold"><i class="fa-solid fa-plus-circle me-2"></i>Create New Website Workspace</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <form id="uploadWebsiteForm">
          <div class="mb-3">
            <label class="form-label text-uppercase text-muted fw-bold small">Website Name</label>
            <input type="text" id="newWebName" class="form-control rounded-3" placeholder="My Business Site" required />
          </div>

          <div class="mb-3">
            <label class="form-label text-uppercase text-muted fw-bold small">Platform Subdomain</label>
            <div class="input-group">
              <input type="text" id="newWebSubdomain" class="form-control" placeholder="mysite" required />
              <span class="input-group-text bg-light text-muted font-monospace small">.nodexplatform.com.ng</span>
            </div>
            <div class="form-text small text-muted">Free trial & Micro plans include platform subdomains.</div>
          </div>

          <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <label class="form-label text-uppercase text-muted fw-bold small mb-0">Custom Domain (Optional)</label>
              <span class="badge bg-indigo-subtle text-indigo uppercase font-bold px-2 py-1 rounded">Growth / Pro</span>
            </div>
            <input type="text" id="newWebCustomDomain" class="form-control rounded-3" placeholder="e.g. mycompany.com" />
            <div class="form-text small text-primary"><i class="fas fa-lock me-1"></i> Custom domains require a Growth or Business Pro plan upgrade.</div>
          </div>

          <button type="submit" class="btn btn-primary w-100 py-2 rounded-3">Provision Website</button>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="manageAdsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow">
      <div class="modal-header bg-dark text-white rounded-top-4 py-3">
        <h5 class="modal-title fs-6 fw-bold"><i class="fa-solid fa-rectangle-ad me-2 text-warning"></i>Monetization Ad Slots</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <form id="saveAdsForm">
          <input type="hidden" id="adSubdomainTarget" />
          <div class="mb-3">
            <label class="form-label text-uppercase text-muted fw-bold small">Header Script / Ad Code</label>
            <textarea id="adHeaderCode" class="form-control font-monospace rounded-3" rows="3" placeholder=""></textarea>
          </div>
          <button type="submit" class="btn btn-primary w-100 py-2 rounded-3">Save Ad Configurations</button>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="inviteMemberModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow">
      <div class="modal-header bg-primary text-white rounded-top-4 py-3">
        <h5 class="modal-title fs-6 fw-bold"><i class="fa-solid fa-user-plus me-2"></i>Invite Team Member</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <form id="inviteMemberForm">
          <div class="mb-3">
            <label class="form-label text-uppercase text-muted fw-bold small">Member Email</label>
            <input type="email" id="inviteMemberEmail" class="form-control rounded-3" placeholder="colleague@domain.com" required />
          </div>
          <button type="submit" class="btn btn-primary w-100 py-2 rounded-3">Send Invitation</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/assets/js/nx-feedback.js"></script>
<script src="https://releases.transloadit.com/uppy/v4.13.3/uppy.min.js"></script>
<script src="/app.js"></script>

<?php if (isset($requestedTab) && $requestedTab): ?>
<script>
    window.location.hash = '#<?php echo $requestedTab; ?>';
</script>
<?php endif; ?>

</body>
</html>