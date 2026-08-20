<?php
/**
 * dashboard.php (User Dashboard - SPA Shell)
 *
 * Premium, White & Blue themed Tenant Control Panel for nodexGosolutions.
 * Fully styled with Tailwind CSS & customized Bootstrap UI panels.
 * SPA Mode: Includes all tab files. Each tab can also be accessed directly as a full page.
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $fullname = cleanInput($_POST['fullname'] ?? '');
        $avatarBase64 = $_POST['avatar_base64'] ?? '';
        $avatarUrl = $user['avatar'] ?? '';

        if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['avatar_file']['tmp_name'];
            $fileName = $_FILES['avatar_file']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($fileExtension, $allowedExtensions, true)) {
                $uploadDir1 = __DIR__ . '/../../../uploads/avatars/';
                $uploadDir2 = __DIR__ . '/../uploads/avatars/';
                if (!is_dir($uploadDir1)) { @mkdir($uploadDir1, 0777, true); }
                if (!is_dir($uploadDir2)) { @mkdir($uploadDir2, 0777, true); }

                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $destPath1 = $uploadDir1 . $newFileName;
                $destPath2 = $uploadDir2 . $newFileName;

                if (move_uploaded_file($fileTmpPath, $destPath1)) {
                    @copy($destPath1, $destPath2);
                    $avatarUrl = '/uploads/avatars/' . $newFileName;
                } elseif (move_uploaded_file($fileTmpPath, $destPath2)) {
                    @copy($destPath2, $destPath1);
                    $avatarUrl = '/uploads/avatars/' . $newFileName;
                } else {
                    $errorMessage = 'Failed to move uploaded avatar file. Check directory permissions.';
                }
            } else {
                $errorMessage = 'Invalid image extension allowed. Choose webp, png, jpeg, or gif.';
            }
        } elseif (!empty($avatarBase64)) {
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
    'overview'        => ['label' => 'Overview',        'icon' => 'fa-chart-pie'],
    'subscriptions'   => ['label' => 'Subscriptions',   'icon' => 'fa-credit-card'],
    'build-website'   => ['label' => 'Website Builder', 'icon' => 'fa-layer-group'],
    'manage-files'    => ['label' => 'File Manager',    'icon' => 'fa-folder-tree'],
    'user-db-manager' => ['label' => 'Databases',       'icon' => 'fa-database'],
    'monetization'    => ['label' => 'Monetization',    'icon' => 'fa-rectangle-ad'],
    'analytics'       => ['label' => 'Analytics',       'icon' => 'fa-chart-line'],
    'team'            => ['label' => 'Team',            'icon' => 'fa-users'],
    'workspace-settings' => ['label' => 'Settings',     'icon' => 'fa-sliders'],
    'profile'         => ['label' => 'Profile',         'icon' => 'fa-user'],
];
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Workspace Dashboard | nodexGo</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Source+Sans+3:wght@400;600;700&display=fallback" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://unpkg.com/grapesjs/dist/css/grapes.min.css">
  <script src="https://unpkg.com/grapesjs"></script>

  <link rel="stylesheet" href="/assets/css/app.css">
  <link rel="stylesheet" href="/assets/css/user.css">
  <link rel="stylesheet" href="/assets/css/file-manager.css">
  <link rel="stylesheet" href="/assets/css/subscriptions.css">

  <script>
    tailwind.config = { corePlugins: { preflight: false } }
  </script>
</head>
<body class="h-full">

<div class="dashboard-layout">
  <?php require_once __DIR__ . '/../../modul/sidebar.php'; ?>

  <div class="main-content">

    <!-- Topbar -->
    <div class="d-flex justify-content-between align-items-center mb-3 border-b border-gray-100 pb-3 flex-shrink-0 flex-wrap gap-3">
      <div class="d-flex align-items-center gap-3">
        <button class="btn btn-primary d-md-none rounded-pill" id="sidebarToggleBtn" type="button" style="height: 40px; width: 40px;">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div>
          <h2 class="fw-bold text-dark mb-0" style="font-size: 1.5rem;">Welcome back, <?php echo htmlspecialchars((string)($user['fullname'] ?? '')); ?></h2>
          <p class="text-muted small mb-0 d-none d-sm-block">Full page control panel for subdomains, files, database schemas, and workspace operations.</p>
        </div>
      </div>
      <div class="d-flex align-items-center gap-3">
        <?php if ($subscriptionStatus === 'trial'): ?>
          <span class="badge bg-primary text-white px-2.5 py-1.5 text-xs rounded-md">
            <i class="fas fa-clock mr-1"></i> FREE TRIAL (Ends: <?php echo date('F d, Y', strtotime($user['trial_end'])); ?>)
          </span>
        <?php elseif ($subscriptionStatus === 'active'): ?>
          <span class="badge bg-success text-white px-2.5 py-1.5 text-xs rounded-md cursor-pointer" data-bs-toggle="modal" data-bs-target="#pricingModal">
            <i class="fas fa-circle-check mr-1"></i> ACTIVE (Plan: <?php echo htmlspecialchars((string)($user['subscription_plan'] ?? 'Growth')); ?>)
          </span>
        <?php else: ?>
          <span class="badge bg-danger text-white px-2.5 py-1.5 text-xs rounded-md cursor-pointer" data-bs-toggle="modal" data-bs-target="#pricingModal">
            <i class="fas fa-exclamation-triangle mr-1"></i> SUBSCRIPTION REQUIRED
          </span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Navigation Tabs Bar -->
    <div class="bg-white border border-gray-100 shadow-sm rounded-2xl p-2 mb-3 d-flex gap-2 overflow-x-auto flex-shrink-0">
      <?php foreach ($tabs as $tabId => $tabInfo):
          $isActive = ($activeTab === $tabId);
      ?>
      <a href="#<?php echo $tabId; ?>"
         class="nav-tab-btn btn btn-sm font-bold text-xs py-2 px-3 rounded-xl border-0 <?php echo $isActive ? 'active bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'; ?>"
         data-tab="<?php echo $tabId; ?>">
        <i class="fa-solid <?php echo $tabInfo['icon']; ?> me-1"></i> <?php echo $tabInfo['label']; ?>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- Feedback Alerts -->
    <?php if (!empty($successMessage)): ?>
      <div class="alert alert-success text-xs rounded-lg p-3 mb-3 flex-shrink-0"><i class="fas fa-circle-check me-2"></i><?php echo htmlspecialchars((string)$successMessage); ?></div>
    <?php endif; ?>
    <?php if (!empty($errorMessage)): ?>
      <div class="alert alert-danger text-xs rounded-lg p-3 mb-3 flex-shrink-0"><i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars((string)$errorMessage); ?></div>
    <?php endif; ?>

    <!-- ==================== TAB CONTENT INCLUDES ==================== -->
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

<!-- Modal Dialogs -->
<div class="modal fade" id="pricingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content rounded-xl border-0 shadow-2xl">
      <div class="modal-header bg-slate-900 text-white"><h5 class="modal-title text-sm font-bold">Select Subscription Plan</h5></div>
      <div class="modal-body p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <?php foreach ($dbPlans as $plan): ?>
            <div class="border rounded-2xl p-4 bg-white">
              <h4 class="font-bold text-sm text-gray-800"><?php echo htmlspecialchars((string)$plan['name']); ?></h4>
              <p class="text-xs text-gray-500 my-2">$<?php echo htmlspecialchars((string)$plan['price']); ?> / month</p>
              <button class="btn btn-primary btn-xs bg-blue-600 text-white w-full mt-3 border-0 btn-process-payment" data-plan-id="<?php echo htmlspecialchars((string)$plan['id']); ?>">Upgrade Plan</button>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="uploadWebsiteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-2xl border-0 shadow-2xl">
      <div class="modal-header bg-blue-600 text-white py-3 px-4"><h5 class="modal-title font-bold text-sm">Create New Website Subdomain</h5></div>
      <div class="modal-body p-4">
        <form id="uploadWebsiteForm">
          <div class="mb-3">
            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Website Name</label>
            <input type="text" id="newWebName" class="form-control text-xs rounded-lg p-2.5 border-gray-200" placeholder="My Business Site" required />
          </div>
          <div class="mb-3">
            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Subdomain Prefix</label>
            <input type="text" id="newWebSubdomain" class="form-control text-xs rounded-lg p-2.5 border-gray-200" placeholder="mysite" required />
          </div>
          <button type="submit" class="bg-blue-600 text-white font-bold text-xs py-2.5 px-4 rounded-lg w-full border-0">Provision Subdomain</button>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="manageAdsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-2xl border-0 shadow-2xl">
      <div class="modal-header bg-slate-900 text-white py-3 px-4"><h5 class="modal-title font-bold text-sm">Monetization Ad Slots</h5></div>
      <div class="modal-body p-4">
        <form id="saveAdsForm">
          <input type="hidden" id="adSubdomainTarget" />
          <div class="mb-3">
            <label class="block text-2xs uppercase font-bold text-gray-500 mb-1">Header Script / Ad Code</label>
            <textarea id="adHeaderCode" class="form-control text-xs font-mono rounded-lg border-gray-200" rows="3" placeholder="<!-- Insert Google AdSense or Custom HTML Code -->"></textarea>
          </div>
          <button type="submit" class="bg-blue-600 text-white font-bold text-xs py-2 px-4 rounded-lg w-full border-0">Save Ad Configurations</button>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="inviteMemberModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-2xl border-0 shadow-2xl">
      <div class="modal-header bg-blue-600 text-white py-3 px-4"><h5 class="modal-title font-bold text-sm">Invite Team Member</h5></div>
      <div class="modal-body p-4">
        <form id="inviteMemberForm">
          <div class="mb-3">
            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Member Email</label>
            <input type="email" id="inviteMemberEmail" class="form-control text-xs rounded-lg p-2.5 border-gray-200" placeholder="colleague@domain.com" required />
          </div>
          <button type="submit" class="bg-blue-600 text-white font-bold text-xs py-2.5 px-4 rounded-lg w-full border-0">Send Invitation</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="/app.js"></script>

<?php if (isset($requestedTab) && $requestedTab): ?>
<script>
    window.location.hash = '#<?php echo $requestedTab; ?>';
</script>
<?php endif; ?>

</body>
</html>