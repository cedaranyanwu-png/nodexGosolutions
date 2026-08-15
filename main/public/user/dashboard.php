<?php
/**
 * dashboard.php (User Dashboard)
 *
 * Premium, White & Blue themed Tenant Control Panel for nodexGosolutions.
 * Fully styled with Tailwind CSS & customized Bootstrap UI panels.
 * Features:
 * - Real-time trial and subscription checking (TRIAL, ACTIVE, EXPIRED, SUSPENDED) server-side.
 * - Exact remaining days count calculation for dynamic trial tracking.
 * - Formulates dynamic website listings and allows physical creation inside /public/<subdomain>/
 *   which automatically associates with subdomain structures (username.yourdomain.com).
 * - Full sidebar tools mapping integration: Website Build, Manage Files, and Databases.
 * - Dynamic Pricing section loaded directly from the database system to neutralize client-side price tampering.
 * - Integration of real Flutterwave checkout gateway with server-controlled payment verification.
 * - Integrated sandboxed File Manager for managing folder assets of active tenant websites.
 * - Clean, fully responsive layout. No FAB floating button.
 * - All code contains line-by-line comments for readability and scale.
 */

// Enable strict typing for safety
declare(strict_types=1);

// Require central system configurations and security helpers
require_once __DIR__ . '/../../../php/db.php';

// Instantiate secure session configurations
secureSession();

// Define hostDomain parameter dynamically to avoid undefined variable warnings
$hostDomain = $_SERVER['HTTP_HOST'] ?? 'nodexplatform.com.ng';
if (str_contains($hostDomain, ':')) {
    $hostDomain = explode(':', $hostDomain)[0];
}

// Access Control: Ensure the user session is active and authenticated
if (!isset($_SESSION['email'])) {
    header('Location: /login');
    exit;
}

// Fetch active user details from database in real-time
$user = $conn->selectOne('users', ['email' => $_SESSION['email']]);
if (!$user) {
    header('Location: /login');
    exit;
}

// Initialize dynamic alert status notification variables
$successMessage = '';
$errorMessage = '';

// Handle profile and password change forms submitted via POST directly inside the dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Profile Details Update (Full Name & Avatar)
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        // Sanitize incoming full name parameters
        $fullname = cleanInput($_POST['fullname'] ?? '');
        // Extract fallback base64 avatar URL if provided
        $avatarBase64 = $_POST['avatar_base64'] ?? '';

        // Retrieve existing avatar path as default fallback
        $avatarUrl = $user['avatar'] ?? '';
        // If a file resource has been uploaded successfully
        if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['avatar_file']['tmp_name'];
            $fileName = $_FILES['avatar_file']['name'];
            $fileSize = $_FILES['avatar_file']['size'];
            $fileType = $_FILES['avatar_file']['type'];

            // Extract lowercase file extension
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            // Define list of strictly allowed extensions
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            // Verify if uploaded asset is a valid web image
            if (in_array($fileExtension, $allowedExtensions, true)) {
                // Construct target destination upload folders
                $uploadDir1 = __DIR__ . '/../../../uploads/avatars/';
                $uploadDir2 = __DIR__ . '/../uploads/avatars/';
                if (!is_dir($uploadDir1)) { @mkdir($uploadDir1, 0777, true); }
                if (!is_dir($uploadDir2)) { @mkdir($uploadDir2, 0777, true); }

                // Generate cryptographically isolated destination file name
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $destPath1 = $uploadDir1 . $newFileName;
                $destPath2 = $uploadDir2 . $newFileName;

                // Attempt to transition file resource onto uploads path
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
            // Apply base64 encoded payload fallback if no raw files uploaded
            $avatarUrl = $avatarBase64;
        }

        // Validate that full name parameter is non-empty
        if (empty($fullname)) {
            $errorMessage = 'Full name field cannot be empty.';
        }

        // If no processing errors occurred, commit changes to system
        if (empty($errorMessage)) {
            // Update tenant information inside custom JSON Database
            $conn->update('users', [
                'fullname' => $fullname,
                'avatar' => $avatarUrl,
                'updated_at' => date('Y-m-d H:i:s')
            ], ['email' => $_SESSION['email']]);

            // Sync session variables to update layouts
            $_SESSION['fullname'] = $fullname;

            // Refresh user details array cache
            $user = $conn->selectOne('users', ['email' => $_SESSION['email']]);
            $successMessage = 'Profile information successfully updated!';
        }
    }

    // 2. Secure Password Update
    if (isset($_POST['action']) && $_POST['action'] === 'update_password') {
        // Isolate current password input
        $currentPassword = $_POST['current_password'] ?? '';
        // Isolate new password input
        $newPassword = $_POST['new_password'] ?? '';
        // Isolate confirmed password input
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validate that all elements are completed
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $errorMessage = 'All password fields are strictly required.';
        } elseif ($newPassword !== $confirmPassword) {
            $errorMessage = 'New password and confirmation fields do not match.';
        } elseif (strlen($newPassword) < 6) {
            $errorMessage = 'New password must be at least 6 characters in length.';
        } else {
            // Verify if input current password matches stored hash
            if (password_verify($currentPassword, $user['password'])) {
                // Generate cryptographically secure password hash representation
                $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                // Commit updated credentials to JSON database
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

// Resolve user's dynamic trial/subscription status securely on server-side
$subscriptionStatus = checkAndUpdateSubscription($user, $conn);

// Calculate remaining free trial days dynamically
$daysRemaining = 0;
if ($subscriptionStatus === 'trial') {
    $trialEndTimestamp = strtotime($user['trial_end'] ?? '');
    $secondsLeft = $trialEndTimestamp - time();
    $daysRemaining = (int)ceil($secondsLeft / 86400);
    if ($daysRemaining < 0) {
        $daysRemaining = 0;
    }
}

// Retrieve custom website projects created by current tenant
$conn->createTable('websites');
$myWebsites = $conn->select('websites', ['user_id' => $user['id']]) ?: [];
$websitesCount = count($myWebsites);

// Dynamic storage directory calculator helper
function getDirectorySize(string $path): int {
    $totalSize = 0;
    if (!is_dir($path)) {
        return 0;
    }
    $files = @scandir($path);
    if ($files === false) {
        return 0;
    }
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $filePath = $path . '/' . $file;
            if (is_dir($filePath)) {
                $totalSize += getDirectorySize($filePath);
            } else {
                $totalSize += (int)filesize($filePath);
            }
        }
    }
    return $totalSize;
}

function formatBytes(int $bytes): string {
    if ($bytes < 1024) {
        return $bytes . ' B';
    } elseif ($bytes < 1048576) {
        return round($bytes / 1024, 2) . ' KB';
    } elseif ($bytes < 1073741824) {
        return round($bytes / 1048576, 2) . ' MB';
    } else {
        return round($bytes / 1073741824, 2) . ' GB';
    }
}

// Calculate user actual workspace storage size
$totalBytes = 0;
$publicDir = __DIR__ . '/../../../public';
foreach ($myWebsites as $web) {
    $wSub = $web['subdomain'] ?? '';
    if (!empty($wSub)) {
        $tenantPath = $publicDir . '/' . $wSub;
        if (is_dir($tenantPath)) {
            $totalBytes += getDirectorySize($tenantPath);
        }
    }
}
$formattedStorage = formatBytes($totalBytes);

// Calculate user dynamic website traffic visits sum
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

// Fetch dynamic active pricing plans from database
$conn->createTable('plans');
$dbPlans = $conn->select('plans', ['is_active' => 1]) ?: [];

// Load system databases to fetch general platform analytics
$siteCmsDb = new Database(__DIR__ . '/../../../databases', 'site_cms');
$siteCmsDb->createTable('pages');
$myCmsPages = $siteCmsDb->select('pages', ['user_id' => $user['id']]) ?: [];

// Collect general analytical counts
$cmsPagesCount  = count($myCmsPages);
$deploymentsCount = $websitesCount + $cmsPagesCount;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Workspace Dashboard | nodexGo</title>

  <!-- Google Font: Plus Jakarta Sans & Source Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Source+Sans+3:wght@400;600;700&display=fallback" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>

  <script>
    // Disable preflight to avoid styling collisons with Bootstrap 5
    tailwind.config = {
      corePlugins: {
        preflight: false,
      }
    }
  </script>

  <style>
    body {
        font-family: 'Plus Jakarta Sans', 'Source Sans 3', sans-serif;
        background-color: #f8fafc;
    }
    .dashboard-layout {
        display: flex;
        min-height: 100vh;
    }
    .main-content {
        flex-grow: 1;
        padding: 30px;
        overflow-x: hidden;
    }
    .code-editor-textarea {
      font-family: 'Fira Code', 'Courier New', Courier, monospace;
      font-size: 13px;
      background-color: #0f172a;
      color: #38bdf8;
      border: 1px solid rgba(0, 114, 255, 0.08);
      border-radius: 8px;
      padding: 12px;
      resize: vertical;
    }
    .code-editor-textarea:focus {
      background-color: #0b0f19;
      border-color: #0072ff;
      outline: none;
      box-shadow: 0 0 12px rgba(0, 114, 255, 0.15);
    }
    .hover-translate {
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .hover-translate:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 20px rgba(0, 114, 255, 0.05);
    }
  </style>
</head>
<body>

<div class="dashboard-layout">

  <!-- Include upgraded modular Sidebar component (with hierarchical collapsible submenus) -->
  <?php require_once __DIR__ . '/../../modul/sidebar.php'; ?>

  <!-- Content Wrapper -->
  <div class="main-content">

    <!-- Header Section / Topbar -->
    <div class="d-flex justify-content-between align-items-center mb-4 border-b border-gray-100 pb-3 flex-wrap gap-3">
      <div class="d-flex align-items-center gap-3">
        <!-- Responsive hamburger toggle button (Mobile only) -->
        <button class="btn btn-primary d-md-none rounded-pill" id="sidebarToggleBtn" type="button" style="height: 40px; width: 40px; display: flex; align-items: center; justify-content: center;">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div>
          <h2 class="fw-bold text-dark mb-0" style="font-size: 1.5rem;">Welcome back, <?php echo htmlspecialchars((string)($user['fullname'] ?? '')); ?></h2>
          <p class="text-muted small mb-0 d-none d-sm-block">Deploy real subdomains, manage sandboxed file assets, edit database tables, and track subscriptions.</p>
        </div>
      </div>
      <div class="d-flex align-items-center gap-3">
        <!-- Unified Header Subscription Status indicators -->
        <?php if ($subscriptionStatus === 'trial'): ?>
          <span class="badge bg-primary text-white px-2.5 py-1.5 text-xs rounded-md">
            <i class="fas fa-clock mr-1"></i> ● FREE TRIAL (Ends: <?php echo date('F d, Y', strtotime($user['trial_end'])); ?>)
          </span>
        <?php elseif ($subscriptionStatus === 'active'): ?>
          <span class="badge bg-success text-white px-2.5 py-1.5 text-xs rounded-md">
            <i class="fas fa-circle-check mr-1"></i> ● ACTIVE (Plan: <?php echo htmlspecialchars((string)($user['subscription_plan'] ?? 'Growth')); ?>, Renews: <?php echo date('F d, Y', strtotime($user['subscription_end'])); ?>)
          </span>
        <?php else: ?>
          <span class="badge bg-danger text-white px-2.5 py-1.5 text-xs rounded-md cursor-pointer" data-bs-toggle="modal" data-bs-target="#pricingModal">
            <i class="fas fa-exclamation-triangle mr-1"></i> ● SUBSCRIPTION REQUIRED (Your trial has expired. [ Subscribe Now ])
          </span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Alert Notifications Feedback Hub -->
    <div id="dashboardAlerts" class="alert d-none text-xs rounded-lg p-3 mb-4" role="alert"></div>

    <!-- RENDER DYNAMIC SUBMISSION RESPONSES FOR PROFILE AND CREDENTIAL UPDATES -->
    <?php if (!empty($successMessage)): ?>
      <div class="alert alert-success text-xs rounded-lg p-3 mb-4 d-block" role="alert">
          <i class="fas fa-circle-check me-2"></i><strong>Success:</strong> <?php echo htmlspecialchars((string)$successMessage); ?>
      </div>
    <?php endif; ?>
    <?php if (!empty($errorMessage)): ?>
      <div class="alert alert-danger text-xs rounded-lg p-3 mb-4 d-block" role="alert">
          <i class="fas fa-exclamation-triangle me-2"></i><strong>Error:</strong> <?php echo htmlspecialchars((string)$errorMessage); ?>
      </div>
    <?php endif; ?>

    <!-- DYNAMIC FEEDBACK ROUTE PARAMETERS EVALUATORS -->
    <?php if (isset($_GET['payment'])): ?>
      <script>
        document.addEventListener("DOMContentLoaded", function() {
          const alertBox = document.getElementById("dashboardAlerts");
          alertBox.classList.remove("d-none");
          const pStatus = "<?php echo cleanInput($_GET['payment']); ?>";
          if (pStatus === "success") {
            alertBox.className = "alert alert-success text-xs rounded-lg p-3 mb-4";
            alertBox.innerHTML = "<i class='fas fa-circle-check me-2'></i><strong>Payment Verified Successfully!</strong> Your premium subscription is now active. Website building and file managing tools are fully unlocked!";
          } else if (pStatus === "failed") {
            alertBox.className = "alert alert-danger text-xs rounded-lg p-3 mb-4";
            alertBox.innerHTML = "<i class='fas fa-circle-xmark me-2'></i><strong>Payment Verification Failed:</strong> The payment was rejected or cancelled. Please try again or contact support.";
          } else {
            alertBox.className = "alert alert-warning text-xs rounded-lg p-3 mb-4";
            alertBox.innerHTML = "<i class='fas fa-exclamation-triangle me-2'></i><strong>Notification:</strong> Transaction processing complete (Code: " + pStatus.replace(/_/g, " ") + ").";
          }
        });
      </script>
    <?php endif; ?>

    <!-- MAIN DASHBOARD CARDS ROW -->
    <div class="row mb-4">
      <!-- Card 1: Websites Count -->
      <div class="col-lg-3 col-md-6 col-12 mb-3">
        <div class="bg-white border border-gray-100 shadow-sm rounded-xl p-4 d-flex align-items-center justify-content-between hover-translate">
          <div>
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Websites</span>
            <span class="text-2xl font-extrabold text-gray-800 d-block"><?php echo $websitesCount; ?></span>
          </div>
          <div class="w-12 h-12 bg-primary bg-opacity-10 text-primary rounded-xl d-flex align-items-center justify-content-center text-xl">
            <i class="fas fa-globe"></i>
          </div>
        </div>
      </div>

      <!-- Card 2: Storage Size -->
      <div class="col-lg-3 col-md-6 col-12 mb-3">
        <div class="bg-white border border-gray-100 shadow-sm rounded-xl p-4 d-flex align-items-center justify-content-between hover-translate">
          <div>
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Storage</span>
            <span class="text-2xl font-extrabold text-gray-800 d-block"><?php echo $formattedStorage; ?></span>
          </div>
          <div class="w-12 h-12 bg-info bg-opacity-10 text-info rounded-xl d-flex align-items-center justify-content-center text-xl">
            <i class="fas fa-hdd"></i>
          </div>
        </div>
      </div>

      <!-- Card 3: Traffic Bandwidth -->
      <div class="col-lg-3 col-md-6 col-12 mb-3">
        <div class="bg-white border border-gray-100 shadow-sm rounded-xl p-4 d-flex align-items-center justify-content-between hover-translate">
          <div>
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Traffic</span>
            <span class="text-2xl font-extrabold text-gray-800 d-block"><?php echo $formattedTraffic; ?></span>
          </div>
          <div class="w-12 h-12 bg-purple-500 bg-opacity-10 text-purple-600 rounded-xl d-flex align-items-center justify-content-center text-xl">
            <i class="fas fa-chart-line"></i>
          </div>
        </div>
      </div>

      <!-- Card 4: Current Subscription Status -->
      <div class="col-lg-3 col-md-6 col-12 mb-3">
        <div class="bg-white border border-gray-100 shadow-sm rounded-xl p-4 d-flex align-items-center justify-content-between hover-translate">
          <div>
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Subscription</span>
            <span class="text-sm font-extrabold block text-gray-800">
              <?php if ($subscriptionStatus === 'trial'): ?>
                <span class="text-blue-600">● FREE TRIAL</span><br>
                <span class="text-2xs text-gray-500 font-semibold" style="font-size: 10px;">Ends: <?php echo date('F d, Y', strtotime($user['trial_end'])); ?></span>
              <?php elseif ($subscriptionStatus === 'active'): ?>
                <span class="text-green-600">● ACTIVE</span><br>
                <span class="text-2xs text-gray-500 font-semibold" style="font-size: 10px;">Plan: <?php echo htmlspecialchars((string)($user['subscription_plan'] ?? 'Growth')); ?></span><br>
                <span class="text-2xs text-gray-400" style="font-size: 9px;">Renews: <?php echo date('F d, Y', strtotime($user['subscription_end'])); ?></span>
              <?php else: ?>
                <span class="text-red-600">● SUBSCRIPTION REQUIRED</span><br>
                <span class="text-2xs text-gray-500 font-semibold" style="font-size: 10px;">Your trial has expired.</span><br>
                <button class="btn btn-xs btn-primary font-bold text-white bg-blue-600 border-0 mt-1 py-0.5 px-2" style="font-size: 9px;" data-bs-toggle="modal" data-bs-target="#pricingModal">[ Subscribe Now ]</button>
              <?php endif; ?>
            </span>
          </div>
          <div class="w-12 h-12 bg-warning bg-opacity-10 text-warning rounded-xl d-flex align-items-center justify-content-center text-xl">
            <i class="fas fa-credit-card"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Workspace Content Area -->
    <section class="content px-0">
      <div class="container-fluid px-0">

        <?php if ($subscriptionStatus === 'expired' || $subscriptionStatus === 'suspended'): ?>
          <!-- RESTRICTED SUBSCRIBER EXPIRED SCREEN / CARD -->
          <div class="row">
            <div class="col-12">
              <div class="bg-white border-2 border-red-200 shadow-lg rounded-2xl p-8 text-center max-w-2xl mx-auto my-5">
                <div class="w-20 h-20 bg-red-100 text-red-600 rounded-full flex items-center justify-center text-3xl mx-auto mb-4">
                  <i class="fas fa-lock"></i>
                </div>
                <h2 class="text-2xl font-extrabold text-gray-800 mb-2">Your Free Trial Has Ended</h2>
                <p class="text-gray-600 mb-6 text-sm">
                  Your 1-month free trial has expired. Choose a hosting plan to continue using your workspace.
                </p>
                <div class="p-4 bg-gray-50 border border-gray-100 rounded-xl mb-6 text-left text-xs max-w-md mx-auto">
                  <span class="font-bold text-gray-700 block mb-1"><i class="fas fa-shield-alt mr-1"></i> Data Security Protocol:</span>
                  All of your hosted websites, databases, files, and configurations remain safely stored on our servers. Hosting access is suspended until a subscription is activated.
                </div>
                <button class="bg-blue-600 hover:bg-blue-700 text-white font-extrabold px-6 py-3 rounded-full shadow-md hover:shadow-lg transition duration-150 text-sm border-0" data-bs-toggle="modal" data-bs-target="#pricingModal">
                  <i class="fas fa-rocket mr-2"></i> Subscribe Now
                </button>
              </div>
            </div>
          </div>
        <?php else: ?>

          <!-- FREE TRIAL STATE - PROMOTIONAL TOP CARD -->
          <?php if ($subscriptionStatus === 'trial'): ?>
            <div class="row mb-4">
              <div class="col-12">
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white shadow-md rounded-2xl p-6 d-flex flex-col md:flex-row justify-between align-items-start md:align-items-center gap-4">
                  <div>
                    <h3 class="text-lg font-extrabold mb-1 flex align-items-center">
                      <i class="fas fa-star text-warning mr-2"></i> FREE TRIAL ACTIVE
                    </h3>
                    <p class="text-xs text-blue-100 m-0">Your workspace is currently on a 1-month free trial. Enjoy all wildcard hosting subdomains completely free!</p>
                  </div>
                  <div class="d-flex align-items-center gap-4">
                    <div class="text-right text-xs">
                      <div class="font-semibold text-blue-100">Trial started: <span class="text-white font-bold"><?php echo date('M d, Y', strtotime($user['trial_start'])); ?></span></div>
                      <div class="font-semibold text-blue-100">Trial ends: <span class="text-white font-bold"><?php echo date('M d, Y', strtotime($user['trial_end'])); ?></span></div>
                    </div>
                    <div class="bg-white bg-opacity-10 px-4 py-2.5 rounded-xl border border-white border-opacity-20 text-center min-w-[100px]">
                      <span class="text-2xs uppercase font-bold text-blue-200 block" style="font-size: 9px;">Days remaining</span>
                      <span class="text-lg font-extrabold"><?php echo $daysRemaining; ?></span>
                    </div>
                    <button class="bg-white hover:bg-gray-100 text-blue-600 font-bold px-4 py-2 rounded-lg text-xs shadow-sm transition duration-150 border-0" data-bs-toggle="modal" data-bs-target="#pricingModal">
                      Choose a Plan
                    </button>
                  </div>
                </div>
              </div>
            </div>
          <?php endif; ?>

          <!-- DYNAMIC WEBSITE BUILD CONSOLE SECTION -->
          <div class="row mb-4" id="build-website">
            <div class="col-12">
              <div class="card border-0 shadow-sm rounded-xl">
                <div class="card-header bg-white border-b border-gray-100 py-3">
                  <h3 class="text-base font-bold text-gray-800 m-0 d-flex align-items-center">
                    <i class="fas fa-screwdriver-wrench text-primary mr-2"></i> Build New Website Subdomain
                  </h3>
                </div>
                <div class="card-body p-4">
                  <div id="websiteCreateFeedback" class="alert d-none text-xs rounded-lg p-2.5 mb-3" role="alert"></div>

                  <form id="buildWebsiteForm" class="row g-3">
                    <div class="col-md-6 text-start">
                      <label class="block text-2xs uppercase font-bold text-gray-500 mb-1" style="font-size: 10px;">Website Name</label>
                      <input type="text" id="webName" class="form-control text-sm rounded-md px-3 py-2 border-gray-200 w-full" placeholder="e.g. My Portfolio Workspace" required />
                    </div>
                    <div class="col-md-6 text-start">
                      <label class="block text-2xs uppercase font-bold text-gray-500 mb-1" style="font-size: 10px;">Subdomain Prefix</label>
                      <div class="input-group">
                        <input type="text" id="webSubdomain" class="form-control text-sm rounded-md px-3 py-2 border-gray-200" placeholder="e.g. janesmith" style="border-radius: 6px 0 0 6px;" required />
                        <span class="input-group-text text-sm" style="border-radius: 0 6px 6px 0; background-color: #f1f5f9; border-color: #e2e8f0;">.<?php echo $hostDomain; ?></span>
                      </div>
                      <div class="form-text text-muted small" style="font-size: 10px;">Spaces and special characters are forbidden. Alphanumeric only.</div>
                    </div>

                    <div class="col-12 text-end">
                      <button type="submit" id="btnBuildSite" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs py-2.5 px-4 rounded-lg transition border-0">
                        <i class="fas fa-circle-plus mr-1"></i> Provision Physical Workspace
                      </button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>

          <!-- DYNAMIC WEBSITE LISTING AND DETAILS VIEW -->
          <div class="row mb-4">
            <div class="col-12">
              <div class="card border-0 shadow-sm rounded-xl">
                <div class="card-header bg-white border-b border-gray-100 py-3 d-flex justify-content-between align-items-center">
                  <h3 class="text-base font-bold text-gray-800 m-0 d-flex align-items-center">
                    <i class="fas fa-globe text-primary mr-2"></i> My Active Subdomains Directory
                  </h3>
                  <span class="badge bg-primary bg-opacity-10 text-primary text-xs px-2.5 py-1 rounded-full font-bold">Wildcard Mapping Enabled</span>
                </div>
                <div class="card-body p-0">
                  <div class="table-responsive">
                    <table class="table table-hover mb-0 text-xs">
                      <thead class="bg-gray-50 text-gray-500 font-bold">
                        <tr>
                          <th class="p-3.5">Website Name</th>
                          <th class="p-3.5">Subdomain Target</th>
                          <th class="p-3.5">Folder Path</th>
                          <th class="p-3.5">Provision Date</th>
                          <th class="p-3.5 text-right">Actions</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-gray-100">
                        <?php if ($websitesCount > 0): ?>
                          <?php foreach ($myWebsites as $web):
                            $wSub = htmlspecialchars((string)($web['subdomain'] ?? ''));
                          ?>
                            <tr>
                              <td class="p-3.5 font-semibold text-gray-800"><?php echo htmlspecialchars((string)($web['name'] ?? '')); ?></td>
                              <td class="p-3.5 font-mono text-blue-600"><a href="<?php echo htmlspecialchars((string)($web['url'] ?? '')); ?>" target="_blank" class="hover:underline"><?php echo htmlspecialchars((string)($web['url'] ?? '')); ?></a></td>
                              <td class="p-3.5"><span class="badge bg-secondary bg-opacity-10 text-secondary text-2xs px-2 py-1 rounded">/public/<?php echo $wSub; ?>/</span></td>
                              <td class="p-3.5 text-gray-400"><?php echo date('M d, Y', strtotime($web['created_at'])); ?></td>
                              <td class="p-3.5 text-right">
                                <button class="btn btn-xs btn-outline-primary rounded-md me-1 btn-open-file-manager" data-subdomain="<?php echo $wSub; ?>">
                                  <i class="fas fa-folder-open mr-1"></i> Manage Files
                                </button>
                                <a href="<?php echo htmlspecialchars((string)($web['url'] ?? '')); ?>" target="_blank" class="btn btn-xs btn-primary rounded-md text-white bg-blue-600 border-0 px-2.5 py-1 me-1">
                                  <i class="fas fa-external-link-alt mr-1"></i> Visit
                                </a>
                                <button class="btn btn-xs btn-outline-danger rounded-md btn-user-delete-site" data-id="<?php echo (int)($web['id'] ?? 0); ?>">
                                  <i class="fas fa-trash"></i> Delete
                                </button>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        <?php else: ?>
                          <tr>
                            <td colspan="5" class="text-center text-gray-400 py-5">No websites provisioned yet. Use the Website Build tool above to launch your first subdomain hosting folder!</td>
                          </tr>
                        <?php endif; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- SANDBOXED ISOLATED FILE MANAGER CONSOLE TAB -->
          <div class="row mb-4 d-none" id="manage-files">
            <div class="col-12">
              <div class="card border-0 shadow-sm rounded-xl">
                <div class="card-header bg-white border-b border-gray-100 py-3 d-flex justify-content-between align-items-center">
                  <h3 class="text-base font-bold text-gray-800 m-0 d-flex align-items-center">
                    <i class="fas fa-folder-tree text-primary mr-2"></i> File Assets Manager Console: <span id="activeManagerSite" class="text-blue-600 ms-1 font-mono"></span>
                  </h3>
                  <button class="btn btn-sm btn-outline-secondary rounded-md" id="btnCloseFileManager"><i class="fas fa-circle-xmark mr-1"></i> Close Manager</button>
                </div>
                <div class="card-body p-4 bg-gray-50 rounded-b-xl">
                  <div class="row g-3">

                    <!-- File Directory Tree List Panel -->
                    <div class="col-md-4 border-r border-gray-100 pr-3">
                      <label class="block text-2xs uppercase font-bold text-gray-500 mb-2" style="font-size: 10px;">Subdomain Files Directory</label>
                      <div class="list-group list-group-flush pl-0 mb-0" id="fileManagerListGroup">
                        <!-- List entries loaded dynamically via AJAX -->
                      </div>
                    </div>

                    <!-- Sandbox Source Code Editor Panel -->
                    <div class="col-md-8">
                      <div id="fileEditFeedback" class="alert d-none text-xs rounded-lg p-2 mb-3" role="alert"></div>

                      <div id="editorContentPanel" class="d-none">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                          <span class="text-xs font-mono font-bold text-gray-700" id="activeEditorFile"></span>
                          <button class="btn btn-xs btn-outline-danger rounded-md py-0.5 px-2 text-2xs" id="btnDeleteFile"><i class="fas fa-trash me-1"></i> Delete File</button>
                        </div>
                        <div class="mb-3">
                          <textarea id="fileEditorTextarea" class="form-control code-editor-textarea w-full" style="min-height: 350px;"></textarea>
                        </div>
                        <button id="btnSaveFileChanges" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs py-2 px-4 rounded-lg border-0 w-full transition">
                          <i class="fas fa-save mr-1"></i> Save File Modifications
                        </button>
                      </div>

                      <div id="editorEmptyState" class="text-center text-gray-400 py-10">
                        <i class="fas fa-file-code text-4xl mb-3 text-gray-300"></i>
                        <p class="text-xs m-0">Select any text-based asset file from the left panel list directory to modify code.</p>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- ISOLATED CUSTOM USER DATABASE MANAGEMENT SECTION -->
          <div class="row mb-4" id="user-db-manager">
            <div class="col-12">
              <div class="card border-0 shadow-sm rounded-xl">
                <div class="card-header bg-white border-b border-gray-100 py-3">
                  <h3 class="text-base font-bold text-gray-800 m-0 d-flex align-items-center">
                    <i class="fas fa-database text-primary mr-2"></i> Isolated Relational Table Schema Manager
                  </h3>
                </div>
                <div class="card-body p-4">
                  <p class="text-xs text-gray-500 mb-4">Design dynamic custom relational table schemas and insert, view, and purge JSON-serialized row records inside your isolated workspace.</p>

                  <div class="row g-3 items-end mb-4 d-flex flex-wrap gap-3">
                    <!-- Create Schema Input -->
                    <div class="flex-grow-1 min-w-[200px]">
                      <label class="block text-2xs uppercase font-bold text-gray-500 mb-1" style="font-size: 10px;">Create Custom Database Table</label>
                      <input type="text" id="newTableName" class="form-control text-sm rounded-md px-3 py-2 border-gray-200 w-full" placeholder="Type table name..." />
                    </div>
                    <!-- Create Button -->
                    <div class="w-full md:w-auto">
                      <button class="btn btn-primary btn-sm rounded-md w-full font-bold py-2 shadow-sm bg-blue-600 text-white border-0" id="btnCreateTable">Create Schema</button>
                    </div>
                    <!-- Schema Selector -->
                    <div class="flex-grow-1 min-w-[200px]">
                      <label class="block text-2xs uppercase font-bold text-gray-500 mb-1" style="font-size: 10px;">Select Active Database Schema</label>
                      <select id="activeTableSelect" class="form-control text-sm rounded-md px-3 py-2 border-gray-200 w-full">
                        <option value="">-- Choose active schema --</option>
                      </select>
                    </div>
                    <!-- Delete Button -->
                    <div class="w-full md:w-auto">
                      <button class="btn btn-outline-danger btn-sm rounded-md w-full font-bold py-2" id="btnDropTable">Drop Table</button>
                    </div>
                  </div>

                  <!-- Dynamic rows display area -->
                  <div id="tableDisplayPanel" class="hidden mt-4 border border-gray-100 rounded-lg p-3 bg-gray-50">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <h5 id="activeTableTitle" class="text-sm font-bold text-gray-800 m-0">Table Structure: <span class="text-blue-600"></span></h5>
                      <button class="btn btn-success btn-xs rounded-md font-semibold bg-emerald-600 text-white border-0" id="btnAddRowBtn" data-bs-toggle="modal" data-bs-target="#insertRowModal"><i class="fas fa-plus mr-1"></i>Insert Record</button>
                    </div>

                    <div class="table-responsive">
                      <table class="table table-hover mb-0 text-xs" id="dynamicDataTable">
                        <thead class="bg-gray-100 text-gray-600 font-bold">
                          <tr id="dynamicDataTableHead">
                            <!-- Header columns populated dynamically -->
                          </tr>
                        </thead>
                        <tbody id="dynamicDataTableBody" class="text-gray-700 bg-white">
                          <!-- Custom rows content -->
                        </tbody>
                      </table>
                    </div>
                  </div>

                </div>
              </div>
            </div>
          </div>

          <!-- DYNAMIC WEBSITE-SPECIFIC ANALYTICS SECTION -->
          <div class="row mb-4" id="analytics">
            <div class="col-12">
              <div class="card border-0 shadow-sm rounded-xl">
                <div class="card-header bg-white border-b border-gray-100 py-3">
                  <h3 class="text-base font-bold text-gray-800 m-0 d-flex align-items-center">
                    <i class="fas fa-chart-simple text-primary mr-2"></i> My Websites Real-Time Analytics
                  </h3>
                </div>
                <div class="card-body p-0">
                  <div class="table-responsive">
                    <table class="table table-hover mb-0 text-xs">
                      <thead class="bg-gray-50 text-gray-500 font-bold">
                        <tr>
                          <th class="p-3.5">Website Domain</th>
                          <th class="p-3.5 text-center">Visits / Traffic</th>
                          <th class="p-3.5 text-center">Unique Visitors</th>
                          <th class="p-3.5 text-center">Page Views</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-gray-100">
                        <?php if ($websitesCount > 0): ?>
                          <?php foreach ($myWebsites as $web):
                            // Fetch traffic row matching this website ID
                            $conn->createTable('traffic');
                            $tRow = $conn->selectOne('traffic', ['website_id' => $web['id']]);
                            $visits = (int)($tRow['visits'] ?? 0);
                            $visitors = (int)($tRow['visitors'] ?? 0);
                            $pageViews = (int)($tRow['page_views'] ?? 0);
                          ?>
                            <tr>
                              <td class="p-3.5 font-semibold text-gray-800"><?php echo htmlspecialchars((string)($web['name'] ?? '')); ?> (<?php echo htmlspecialchars((string)($web['subdomain'] ?? '')); ?>)</td>
                              <td class="p-3.5 text-center font-bold text-blue-600"><?php echo number_format($visits); ?></td>
                              <td class="p-3.5 text-center font-semibold text-gray-700"><?php echo number_format($visitors); ?></td>
                              <td class="p-3.5 text-center text-gray-500"><?php echo number_format($pageViews); ?></td>
                            </tr>
                          <?php endforeach; ?>
                        <?php else: ?>
                          <tr>
                            <td colspan="4" class="text-center text-gray-400 py-4">No website analytics available. Provision a subdomain to start tracking traffic metrics.</td>
                          </tr>
                        <?php endif; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- USER PAYMENT HISTORY AND ACTIVITY LOGS -->
          <div class="row mb-4">
            <!-- Col 1: Payment History Ledger -->
            <div class="col-lg-6 mb-3">
              <div class="card border-0 shadow-sm rounded-xl">
                <div class="card-header bg-white border-b border-gray-100 py-3">
                  <h3 class="text-base font-bold text-gray-800 m-0"><i class="fas fa-receipt text-emerald-600 mr-2"></i> My Payments & Invoice History</h3>
                </div>
                <div class="card-body p-0">
                  <div class="table-responsive">
                    <table class="table table-hover mb-0 text-xs">
                      <thead class="bg-gray-50 text-gray-500 font-bold">
                        <tr>
                          <th class="p-3">Reference / Tx ID</th>
                          <th class="p-3">Plan / Amount</th>
                          <th class="p-3">Status</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-gray-100">
                        <?php
                        $conn->createTable('payments');
                        $myPayments = $conn->select('payments', ['user_id' => $user['id']]) ?: [];
                        if (count($myPayments) > 0):
                          foreach (array_reverse($myPayments) as $p):
                            $pStatus = strtolower((string)($p['status'] ?? ''));
                        ?>
                            <tr>
                              <td class="p-3 font-mono text-gray-600"><?php echo htmlspecialchars((string)($p['tx_ref'] ?? '')); ?></td>
                              <td class="p-3">
                                <span class="block font-bold text-gray-800"><?php echo htmlspecialchars((string)($p['plan_name'] ?? '')); ?></span>
                                <span class="block text-blue-600 font-semibold"><?php echo htmlspecialchars((string)($p['currency'] ?? 'NGN')) . ' ' . number_format((float)($p['amount'] ?? 0.0)); ?></span>
                              </td>
                              <td class="p-3">
                                <?php if ($pStatus === 'successful'): ?>
                                  <span class="badge bg-success bg-opacity-10 text-success rounded-full font-bold px-2 py-0.5" style="font-size: 9px;">Successful</span>
                                <?php elseif ($pStatus === 'pending'): ?>
                                  <span class="badge bg-warning bg-opacity-10 text-warning rounded-full font-bold px-2 py-0.5" style="font-size: 9px;">Pending</span>
                                <?php else: ?>
                                  <span class="badge bg-danger bg-opacity-10 text-danger rounded-full font-bold px-2 py-0.5" style="font-size: 9px;">Failed</span>
                                <?php endif; ?>
                              </td>
                            </tr>
                        <?php
                          endforeach;
                        else:
                        ?>
                          <tr>
                            <td colspan="3" class="text-center text-gray-400 py-4">No payment receipts registered yet.</td>
                          </tr>
                        <?php endif; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

            <!-- Col 2: Recent Activity Logs -->
            <div class="col-lg-6 mb-3">
              <div class="card border-0 shadow-sm rounded-xl">
                <div class="card-header bg-white border-b border-gray-100 py-3">
                  <h3 class="text-base font-bold text-gray-800 m-0"><i class="fas fa-rectangle-list text-primary mr-2"></i> My Recent Activity Logs</h3>
                </div>
                <div class="card-body p-0">
                  <div class="table-responsive">
                    <table class="table table-hover mb-0 text-xs">
                      <thead class="bg-gray-50 text-gray-500 font-bold">
                        <tr>
                          <th class="p-3">Operational Event</th>
                          <th class="p-3">Details / Audit Log</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-gray-100">
                        <?php
                        $conn->createTable('activity_logs');
                        // Filter activities by the currently authenticated user's email only
                        $myActivities = $conn->select('activity_logs', ['email' => $user['email']]) ?: [];
                        if (count($myActivities) > 0):
                          foreach (array_reverse($myActivities) as $act):
                        ?>
                            <tr>
                              <td class="p-3"><span class="badge bg-indigo-50 text-indigo-700 font-bold border border-indigo-100 px-2 py-0.5 rounded"><?php echo strtoupper(str_replace('_', ' ', (string)($act['action'] ?? ''))); ?></span></td>
                              <td class="p-3 text-gray-600"><?php echo htmlspecialchars((string)($act['details'] ?? '')); ?></td>
                            </tr>
                        <?php
                          endforeach;
                        else:
                        ?>
                          <tr>
                            <td colspan="2" class="text-center text-gray-400 py-4">No recent activity logs recorded.</td>
                          </tr>
                        <?php endif; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endif; ?>

          <!-- DYNAMIC DISCOVERED TOOLS CONTAINERS -->
          <?php
          global $toolManager;
          if (isset($toolManager) && $toolManager instanceof ToolManager):
              $activeUserRole = strtolower((string)($user['role'] ?? 'tenant'));
              $accessibleTools = array_filter($toolManager->enabled(), function($t) use ($toolManager, $activeUserRole) {
                  return $toolManager->canAccess($t['slug'], $activeUserRole);
              });
              foreach ($accessibleTools as $aTool):
                  $dSlug = htmlspecialchars((string)($aTool['slug'] ?? ''));
          ?>
              <div class="row mb-4 d-none dynamic-tool-container" id="tool-<?php echo $dSlug; ?>">
                  <div class="col-12">
                      <?php
                      if (is_file($aTool['entry_path'])) {
                          include $aTool['entry_path'];
                      }
                      ?>
                  </div>
              </div>
          <?php
              endforeach;
          endif;
          ?>

        <!-- PROFILE SETTINGS TAB PANEL -->
        <div class="row mb-4 d-none" id="profile">
          <div class="col-12">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

              <!-- Left Column: Profile Card and Avatar Selection -->
              <div class="lg:col-span-1">
                <div class="card border-0 shadow-sm rounded-xl p-4 bg-white text-center">
                  <div class="mb-4">
                    <!-- Render user avatar preview dynamically -->
                    <?php if (!empty($user['avatar'])): ?>
                      <img src="<?php echo htmlspecialchars((string)$user['avatar']); ?>" alt="User Avatar" class="w-24 h-24 rounded-full mx-auto object-cover border-4 border-blue-500 shadow-md" id="avatarPreview">
                    <?php else: ?>
                      <div class="w-24 h-24 rounded-full mx-auto bg-blue-600 text-white font-bold text-3xl flex items-center justify-center border-4 border-blue-100 shadow-md" id="avatarPreview">
                        <?php echo strtoupper(substr((string)($user['fullname'] ?? 'S'), 0, 1)); ?>
                      </div>
                    <?php endif; ?>
                  </div>
                  <h3 class="text-lg font-bold text-gray-800 mb-1"><?php echo htmlspecialchars((string)($user['fullname'] ?? '')); ?></h3>
                  <p class="text-xs text-gray-400 mb-4"><?php echo htmlspecialchars((string)($user['email'] ?? '')); ?></p>
                  <div class="bg-blue-50 text-blue-700 text-xs font-semibold py-1 px-3 rounded-full inline-block uppercase tracking-wider">
                    <?php echo htmlspecialchars(strtoupper((string)($user['role'] ?? 'tenant'))); ?>
                  </div>
                </div>
              </div>

              <!-- Right Column: Details Forms -->
              <div class="lg:col-span-2 flex flex-col gap-6">

                <!-- Form Card 1: Account Information -->
                <div class="card border-0 shadow-sm rounded-xl bg-white">
                  <div class="card-header bg-white border-b border-gray-100 py-3.5 px-4">
                    <h4 class="text-sm font-bold text-gray-800 m-0"><i class="fas fa-user-circle text-primary mr-2"></i> Update Personal Information</h4>
                  </div>
                  <div class="card-body p-4">
                    <form action="/user/dashboard#profile" method="POST" enctype="multipart/form-data">
                      <input type="hidden" name="action" value="update_profile" />

                      <div class="mb-3.5">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Full Name</label>
                        <input type="text" name="fullname" value="<?php echo htmlspecialchars((string)($user['fullname'] ?? '')); ?>" class="form-control text-sm rounded-lg border-gray-200 p-2.5 w-full focus:ring-blue-500 focus:border-blue-500" required />
                      </div>

                      <div class="mb-3.5">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Upload Profile Avatar File</label>
                        <input type="file" name="avatar_file" accept="image/*" class="form-control text-sm rounded-lg border-gray-200 p-2 w-full" />
                        <small class="text-2xs text-gray-400 mt-1.5 block">Supported file formats: WebP, PNG, JPEG, GIF. Maximum file size: 2MB.</small>
                      </div>

                      <div class="mb-3.5">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Or Choose Standard Pre-selected Avatar</label>
                        <div class="flex gap-3 mt-2 flex-wrap">
                          <?php
                          $avatarsList = [
                            'https://placehold.co/150x150/0072ff/ffffff?text=User',
                            'https://placehold.co/150x150/27ae60/ffffff?text=Admin',
                            'https://placehold.co/150x150/e67e22/ffffff?text=Staff',
                            'https://placehold.co/150x150/9b59b6/ffffff?text=Tech'
                          ];
                          foreach ($avatarsList as $idx => $av):
                          ?>
                            <label class="cursor-pointer position-relative">
                              <input type="radio" name="avatar_base64" value="<?php echo $av; ?>" class="peer hidden" <?php echo (($user['avatar'] ?? '') === $av) ? 'checked' : ''; ?> />
                              <img src="<?php echo $av; ?>" class="w-12 h-12 rounded-full border-2 border-transparent peer-checked:border-blue-500 transition-all hover:scale-105" />
                            </label>
                          <?php endforeach; ?>
                        </div>
                      </div>

                      <div class="mt-4 text-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs py-2.5 px-4 rounded-lg transition border-0 shadow-md">
                          Save Changes
                        </button>
                      </div>
                    </form>
                  </div>
                </div>

                <!-- Form Card 2: Security Credentials Update -->
                <div class="card border-0 shadow-sm rounded-xl bg-white">
                  <div class="card-header bg-white border-b border-gray-100 py-3.5 px-4">
                    <h4 class="text-sm font-bold text-gray-800 m-0"><i class="fas fa-key text-red-500 mr-2"></i> Update Security Password</h4>
                  </div>
                  <div class="card-body p-4">
                    <form action="/user/dashboard#profile" method="POST">
                      <input type="hidden" name="action" value="update_password" />

                      <div class="mb-3.5">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Current Secure Password</label>
                        <input type="password" name="current_password" class="form-control text-sm rounded-lg border-gray-200 p-2.5 w-full focus:ring-blue-500 focus:border-blue-500" placeholder="••••••••" required />
                      </div>

                      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3.5">
                        <div>
                          <label class="block text-xs font-bold text-gray-500 uppercase mb-2">New Password</label>
                          <input type="password" name="new_password" class="form-control text-sm rounded-lg border-gray-200 p-2.5 w-full focus:ring-blue-500 focus:border-blue-500" placeholder="At least 6 characters" required />
                        </div>
                        <div>
                          <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Confirm New Password</label>
                          <input type="password" name="confirm_password" class="form-control text-sm rounded-lg border-gray-200 p-2.5 w-full focus:ring-blue-500 focus:border-blue-500" placeholder="Confirm password" required />
                        </div>
                      </div>

                      <div class="mt-4 text-end">
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold text-xs py-2.5 px-4 rounded-lg transition border-0 shadow-md">
                          Change Password
                        </button>
                      </div>
                    </form>
                  </div>
                </div>

              </div>

            </div>
          </div>
        </div>

      </div>
    </section>
  </div>

</div>

  <!-- Pricing Selection Modal (Integrated with server-controlled pricing structures) -->
  <div class="modal fade" id="pricingModal" tabindex="-1" role="dialog" aria-labelledby="pricingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
      <div class="modal-content rounded-xl border-0 shadow-2xl">
        <div class="modal-header border-b border-gray-100 pb-3">
          <h5 class="modal-title font-bold text-gray-800 d-flex align-items-center text-sm" id="pricingModalLabel">
            <i class="fas fa-credit-card text-primary mr-2"></i> Select Premium Hosting Workspace Plan
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4 bg-gray-50">
          <div class="text-center mb-4">
            <h4 class="font-extrabold text-gray-800 text-base">Affordable Pricing Built for High Performance</h4>
            <p class="text-xs text-gray-500">Deploy high-performance systems with zero operational overhead. Connect with Flutterwave checkout.</p>
          </div>

          <div id="paymentFeedback" class="alert d-none text-xs rounded-lg p-3 mb-4" role="alert"></div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <?php foreach ($dbPlans as $plan):
              $pFeatures = json_decode((string)($plan['features'] ?? '[]'), true) ?: [];
              $isPop = (int)($plan['is_recommended'] ?? 0) === 1;
            ?>
              <!-- Dynamic Plan Card -->
              <div class="bg-white border rounded-2xl p-4 d-flex flex-column justify-content-between hover-translate relative <?php echo $isPop ? 'border-2 border-primary' : 'border-gray-200'; ?>">
                <?php if ($isPop): ?>
                  <span class="absolute top-0 right-4 transform -translate-y-1/2 bg-blue-600 text-white text-[9px] font-black uppercase px-2.5 py-0.5 rounded-full" style="font-size: 9px; top: 0px;">Popular</span>
                <?php endif; ?>
                <div class="mb-4">
                  <span class="text-2xs font-extrabold text-blue-600 uppercase tracking-widest d-block mb-1" style="font-size: 9px;"><?php echo htmlspecialchars((string)($plan['name'] ?? '')); ?></span>
                  <span class="text-2xl font-black text-gray-800">₦<?php echo number_format((float)$plan['price']); ?><span class="text-xs font-normal text-gray-400">/<?php echo htmlspecialchars((string)($plan['billing_period'] ?? '')); ?></span></span>
                  <p class="text-2xs text-gray-500 mt-2" style="font-size: 11px;"><?php echo htmlspecialchars((string)($plan['description'] ?? '')); ?></p>
                  <hr class="my-3 border-gray-100">
                  <ul class="text-2xs text-gray-600 space-y-2 pl-0 list-unstyled" style="font-size: 10px;">
                    <?php foreach ($pFeatures as $feat): ?>
                      <li><i class="fas fa-check text-success mr-1"></i> <?php echo htmlspecialchars((string)$feat); ?></li>
                    <?php endforeach; ?>
                  </ul>
                </div>
                <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs py-2 px-3 rounded-lg w-full transition btn-process-payment border-0" data-plan-id="<?php echo htmlspecialchars((string)($plan['id'] ?? '')); ?>">
                  Select Plan
                </button>
              </div>
            <?php endforeach; ?>
          </div>

        </div>
        <div class="modal-footer border-t border-gray-100 bg-gray-50 rounded-b-xl py-2 d-flex justify-between align-items-center">
          <span class="text-[10px] text-gray-400"><i class="fas fa-lock mr-1"></i> All payment parameters are processed securely server-side.</span>
          <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- USER SUPPORT TICKET THREAD MODAL -->
  <div class="modal fade" id="userTicketModal" tabindex="-1" aria-labelledby="userTicketModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content rounded-xl border-0 shadow-2xl">
        <div class="modal-header border-b border-gray-100 bg-slate-900 text-white rounded-t-xl py-3">
          <h5 class="modal-title font-bold flex items-center text-sm" id="userTicketModalLabel"><i class="fa-solid fa-comments me-2 text-blue-400"></i> Support Request Conversation</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4 text-xs">
          <input type="hidden" id="userTicketModalId" value="">

          <div class="mb-3 border-b pb-2">
            <h4 class="font-bold text-gray-800 text-sm mb-1" id="userTicketModalTitle"></h4>
            <div class="text-gray-500 bg-gray-50 p-2.5 rounded-md border border-gray-100 mt-1" id="userTicketModalOriginalMsg"></div>
          </div>

          <div class="mb-3">
            <span class="text-gray-400 block font-bold text-2xs uppercase mb-2">Conversation Thread:</span>
            <div id="userTicketThreadContainer" class="flex flex-col gap-2 max-h-60 overflow-y-auto p-2 bg-gray-50 rounded-lg border border-gray-100">
              <!-- Thread messages rendered dynamically -->
            </div>
          </div>

          <form id="userTicketReplyForm" class="mt-3">
            <div class="mb-2">
              <textarea id="userTicketReplyInput" class="form-control text-xs rounded-md border-gray-200" rows="3" placeholder="Type your follow-up reply here..." required></textarea>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs py-2 px-3 rounded-lg border-0 transition">
              <i class="fas fa-paper-plane me-1"></i> Send Reply
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- ROW RECORD INSERTION MODAL -->
  <div class="modal fade" id="insertRowModal" tabindex="-1" aria-labelledby="insertRowModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-xl border-0 shadow-2xl">
        <div class="modal-header border-b border-gray-100 bg-primary text-white py-3">
          <h5 class="modal-title font-bold d-flex align-items-center text-sm" id="insertRowModalLabel"><i class="fas fa-plus-square mr-2"></i>Insert Row Data</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <form id="insertRowForm">
            <div class="mb-3 text-xs text-gray-500" style="font-size: 11px;">Specify up to 4 dynamic columns and field values below:</div>
            <div id="modalColumnsContainer">
              <div class="row g-2 mb-2 column-input-row d-flex gap-2">
                <div class="col-6">
                  <input type="text" class="form-control col-name-input text-xs rounded-md px-2 py-1.5 border-gray-200" placeholder="Column Key (e.g. name)" required>
                </div>
                <div class="col-6">
                  <input type="text" class="form-control col-val-input text-xs rounded-md px-2 py-1.5 border-gray-200" placeholder="Field Value" required>
                </div>
              </div>
            </div>
            <button type="button" class="btn btn-outline-primary btn-xs mt-2 rounded-md font-bold" id="btnAddColumnInput"><i class="fas fa-plus mr-1"></i>Add Column Tag</button>
          </form>
        </div>
        <div class="modal-footer border-t border-gray-100 bg-gray-50 rounded-b-xl py-2">
          <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary btn-sm rounded-pill px-4 bg-blue-600 text-white border-0" id="btnSubmitInsertRow">Save Record</button>
        </div>
      </div>
    </div>
  </div>

<!-- Required Scripts: jQuery, Bootstrap 5 -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Interactive Frontend Controls & File Explorer APIs -->
<script>
$(document).ready(function() {

    // 1. Handle secure payment initialization via AJAX to initialize_payment.php
    $('.btn-process-payment').on('click', function() {
        const planId = $(this).attr('data-plan-id');
        const feedback = $('#paymentFeedback');

        feedback.addClass('d-none').removeClass('alert-success alert-danger');
        $(this).prop('disabled', true).text('Opening Checkout...');

        $.ajax({
            url: '/php/initialize_payment.php',
            type: 'POST',
            dataType: 'json',
            data: { plan_id: planId },
            success: function(res) {
                if (res.success && res.link) {
                    feedback.removeClass('d-none').addClass('alert-success').text('Redirecting to secure Flutterwave Hosted checkout...');
                    // Redirect browser to Flutterwave secure link
                    window.location.href = res.link;
                } else {
                    feedback.removeClass('d-none').addClass('alert-danger').text(res.message || 'Verification initialization failed.');
                }
            },
            error: function(xhr) {
                const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Server communications error. Please try again.';
                feedback.removeClass('d-none').addClass('alert-danger').text(msg);
            }
        });
    });

    // 2. Handle Physical Website Building Form submissions
    $('#buildWebsiteForm').on('submit', function(e) {
        e.preventDefault();
        const feedback = $('#websiteCreateFeedback');
        const btn = $('#btnBuildSite');

        feedback.addClass('d-none').removeClass('alert-success alert-danger');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Provisioning Folders...');

        $.ajax({
            url: '/php/create_website_action.php',
            type: 'POST',
            dataType: 'json',
            data: {
                website_name: $('#webName').val(),
                website_subdomain: $('#webSubdomain').val()
            },
            success: function(res) {
                btn.prop('disabled', false).html('<i class="fas fa-circle-plus mr-1"></i> Provision Physical Workspace');
                if (res.success) {
                    feedback.removeClass('d-none').addClass('alert-success').text(res.message);
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    feedback.removeClass('d-none').addClass('alert-danger').text(res.message);
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fas fa-circle-plus mr-1"></i> Provision Physical Workspace');
                const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Folder provisioning communication failure.';
                feedback.removeClass('d-none').addClass('alert-danger').text(msg);
            }
        });
    });

    // ============================================================
    // SANDBOXED FILE MANAGER INTERACTIVE CODES
    // ============================================================
    let activeSubdomain = '';
    let activeFilePath  = '';

    // Trigger File Manager Panel for specific Subdomain Directory
    $('.btn-open-file-manager').on('click', function() {
        activeSubdomain = $(this).attr('data-subdomain');
        $('#activeManagerSite').text(activeSubdomain + '.<?php echo $hostDomain; ?>');
        $('#manage-files').removeClass('d-none');

        // Scroll smoothly to file manager panel
        $('html, body').animate({
            scrollTop: $("#manage-files").offset().top - 20
        }, 300);

        loadFileTreeList();
    });

    // Handle hash links / routing from sidebar dropdowns inside the dashboard
    const handleDashboardHashes = function() {
        const hash = window.location.hash;
        if (!hash) return;

        if (hash === '#manage-files') {
            // Find and click the first manage files button if available to activate file manager
            const fileManagerBtn = $('.btn-open-file-manager').first();
            if (fileManagerBtn.length > 0) {
                fileManagerBtn.click();
            } else {
                $('#manage-files').removeClass('d-none');
                $('#fileManagerListGroup').html('<div class="text-center text-gray-500 py-4 text-xs"><i class="fas fa-info-circle me-1"></i> No website directory is active. Please <a href="#build-website" class="text-blue-600 font-bold hover:underline">Build a Website</a> first!</div>');
            }
        } else if (hash === '#build-website') {
            $('html, body').animate({
                scrollTop: $("#build-website").offset().top - 20
            }, 300);
        } else if (hash === '#user-db-manager') {
            $('html, body').animate({
                scrollTop: $("#user-db-manager").offset().top - 20
            }, 300);
        } else if (hash === '#analytics') {
            // Ensure the analytics section is unhidden and scroll to its position smoothly
            $('#analytics').removeClass('d-none');
            $('html, body').animate({
                scrollTop: $("#analytics").offset().top - 20
            }, 300);
        } else if (hash === '#profile') {
            // Unhide the profile panel dynamically and trigger scroll
            $('#profile').removeClass('d-none');
            $('html, body').animate({
                scrollTop: $("#profile").offset().top - 20
            }, 300);
        } else if (hash.startsWith('#tool-')) {
            $('.dynamic-tool-container').addClass('d-none');
            $(hash).removeClass('d-none');
            $('html, body').animate({
                scrollTop: $(hash).offset().top - 20
            }, 300);
        } else if (hash === '#support') {
            // Unhide the support ticket panel dynamically and trigger scroll
            $('#support').removeClass('d-none');
            $('html, body').animate({
                scrollTop: $("#support").offset().top - 20
            }, 300);
        }
    };

    // Create Support Ticket Form Submission
    $('#createTicketForm').on('submit', function(e) {
        e.preventDefault();
        const feedback = $('#userTicketFeedback');
        feedback.addClass('d-none').removeClass('alert-success alert-danger');

        const title = $('#ticketTitle').val().trim();
        const category = $('#ticketCategory').val();
        const websiteId = $('#ticketWebsiteId').val();
        const message = $('#ticketMessage').val().trim();

        $.ajax({
            url: '/php/user_support_action.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'create_ticket',
                title: title,
                category: category,
                website_id: websiteId,
                message: message
            },
            success: function(res) {
                if (res.success) {
                    feedback.removeClass('d-none').addClass('alert-success').text(res.message);
                    setTimeout(function() { location.reload(); }, 1200);
                } else {
                    feedback.removeClass('d-none').addClass('alert-danger').text(res.message || 'Failed to submit ticket.');
                }
            },
            error: function() {
                feedback.removeClass('d-none').addClass('alert-danger').text('An error occurred submitting ticket.');
            }
        });
    });

    // User View Ticket Thread
    $(document).on('click', '.btn-view-user-ticket', function() {
        const id = $(this).data('id');
        const title = $(this).data('title');
        const message = $(this).data('message');
        const replies = $(this).data('replies');

        $('#userTicketModalId').val(id);
        $('#userTicketModalTitle').text(title);
        $('#userTicketModalOriginalMsg').text(message);

        const threadContainer = $('#userTicketThreadContainer');
        threadContainer.empty();

        if (replies && replies.length > 0) {
            replies.forEach(function(rep) {
                const isSupport = rep.sender === 'support';
                const bgClass = isSupport ? 'bg-blue-100 text-blue-900 self-start border-blue-200' : 'bg-gray-200 text-gray-800 self-end';
                const senderLabel = isSupport ? '<i class="fas fa-headset me-1 text-blue-600"></i> ' + rep.sender_name : '<i class="fas fa-user me-1 text-gray-600"></i> You';

                const msgHtml = `
                    <div class="p-2.5 rounded-lg border text-xs max-w-lg mb-1 ${bgClass}">
                        <div class="font-bold text-2xs mb-0.5">${senderLabel} <span class="text-gray-400 font-normal ms-2">${rep.created_at || ''}</span></div>
                        <div>${rep.message}</div>
                    </div>
                `;
                threadContainer.append(msgHtml);
            });
        } else {
            threadContainer.html('<div class="text-center text-gray-400 py-3 text-2xs">No replies yet. Our support team will respond shortly!</div>');
        }

        const ticketModal = new bootstrap.Modal(document.getElementById('userTicketModal'));
        ticketModal.show();
    });

    // User Reply Submission
    $('#userTicketReplyForm').on('submit', function(e) {
        e.preventDefault();
        const id = $('#userTicketModalId').val();
        const reply = $('#userTicketReplyInput').val().trim();

        $.ajax({
            url: '/php/user_support_action.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'add_reply', ticket_id: id, reply: reply },
            success: function(res) {
                if (res.success) {
                    alert(res.message);
                    location.reload();
                } else {
                    alert(res.message || 'Failed to send reply.');
                }
            }
        });
    });

    // Trigger on hash loads and hash change events
    handleDashboardHashes();
    $(window).on('hashchange', handleDashboardHashes);

    $('#btnCloseFileManager').on('click', function() {
        $('#manage-files').addClass('d-none');
    });

    // List Files
    function loadFileTreeList() {
        const fileGroup = $('#fileManagerListGroup');
        fileGroup.html('<div class="text-center py-4 text-xs"><i class="fas fa-spinner fa-spin me-2"></i>Reading directory contents...</div>');

        // Reset Editor state
        $('#editorContentPanel').addClass('d-none');
        $('#editorEmptyState').removeClass('d-none');

        $.ajax({
            url: '/php/manage_files_action.php',
            type: 'GET',
            dataType: 'json',
            data: {
                action: 'list_files',
                subdomain: activeSubdomain
            },
            success: function(res) {
                fileGroup.empty();
                if (res.success && res.files.length > 0) {
                    res.files.forEach(function(file) {
                        const icon = file.is_dir ? 'fa-folder text-warning' : 'fa-file-code text-primary';
                        const itemClass = file.is_dir ? '' : 'btn-select-file cursor-pointer';

                        fileGroup.append(`
                            <button class="list-group-item list-group-item-action py-2 px-3 text-dark text-start border border-gray-100 rounded-md mb-2 d-flex justify-content-between align-items-center ${itemClass}" data-path="${file.path}">
                                <span><i class="fa-solid ${icon} me-2"></i> ${file.name}</span>
                                <span class="text-2xs text-gray-400" style="font-size: 9px;">${file.is_dir ? 'Dir' : (file.size + ' B')}</span>
                            </button>
                        `);
                    });
                } else {
                    fileGroup.html('<div class="text-center text-gray-400 py-4 text-xs">This physical directory is empty.</div>');
                }
            },
            error: function(xhr) {
                const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to retrieve files directory.';
                fileGroup.html(`<div class="alert alert-danger text-2xs p-2">${msg}</div>`);
            }
        });
    }

    // Select and Read File
    $(document).on('click', '.btn-select-file', function() {
        $('.btn-select-file').removeClass('active bg-primary text-white');
        $(this).addClass('active bg-primary text-white');

        activeFilePath = $(this).attr('data-path');
        $('#activeEditorFile').text('Editing: ' + activeFilePath);

        $('#fileEditFeedback').addClass('d-none');

        $.ajax({
            url: '/php/manage_files_action.php',
            type: 'GET',
            dataType: 'json',
            data: {
                action: 'read_file',
                subdomain: activeSubdomain,
                file_path: activeFilePath
            },
            success: function(res) {
                if (res.success) {
                    $('#fileEditorTextarea').val(res.content);
                    $('#editorEmptyState').addClass('d-none');
                    $('#editorContentPanel').removeClass('d-none');
                } else {
                    alert(res.message);
                }
            },
            error: function(xhr) {
                alert(xhr.responseJSON ? xhr.responseJSON.message : 'Failed to read file content.');
            }
        });
    });

    // Save File modifications
    $('#btnSaveFileChanges').on('click', function() {
        const feedback = $('#fileEditFeedback');
        feedback.addClass('d-none').removeClass('alert-success alert-danger');
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Saving changes...');

        $.ajax({
            url: '/php/manage_files_action.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'save_file',
                subdomain: activeSubdomain,
                file_path: activeFilePath,
                content: $('#fileEditorTextarea').val()
            },
            success: function(res) {
                $('#btnSaveFileChanges').prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Save File Modifications');
                feedback.removeClass('d-none');
                if (res.success) {
                    feedback.addClass('alert-success').text(res.message);
                } else {
                    feedback.addClass('alert-danger').text(res.message);
                }
            },
            error: function(xhr) {
                $('#btnSaveFileChanges').prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Save File Modifications');
                feedback.removeClass('d-none').addClass('alert-danger').text(xhr.responseJSON ? xhr.responseJSON.message : 'Failed to save modifications.');
            }
        });
    });

    // Delete File
    $('#btnDeleteFile').on('click', function() {
        if (!confirm('Are you absolutely sure you want to permanently delete ' + activeFilePath + ' from your physical workspace?')) return;

        const feedback = $('#fileEditFeedback');
        feedback.addClass('d-none');

        $.ajax({
            url: '/php/manage_files_action.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'delete_file',
                subdomain: activeSubdomain,
                file_path: activeFilePath
            },
            success: function(res) {
                if (res.success) {
                    alert(res.message);
                    loadFileTreeList();
                } else {
                    feedback.removeClass('d-none').addClass('alert-danger').text(res.message);
                }
            },
            error: function(xhr) {
                alert(xhr.responseJSON ? xhr.responseJSON.message : 'Failed to delete file.');
            }
        });
    });

    // ============================================================
    // DATABASE MANAGER CLIENT CONSOLE CONTROLS
    // ============================================================

    // Fetch schemas list dynamically
    function loadTablesList() {
        if ($('#activeTableSelect').length === 0) return;
        $.ajax({
            url: '/php/user_database_action.php',
            type: 'GET',
            dataType: 'json',
            data: { action: 'list_tables' },
            success: function(res) {
                if (res.success) {
                    const select = $('#activeTableSelect');
                    const selectedVal = select.val();
                    select.empty().append('<option value="">-- Choose active schema --</option>');
                    res.tables.forEach(function(table) {
                        select.append(`<option value="${table}">${table}</option>`);
                    });
                    if (selectedVal) select.val(selectedVal);
                }
            }
        });
    }

    loadTablesList();

    // Create Custom Table Schema
    $('#btnCreateTable').on('click', function() {
        const tableName = $('#newTableName').val().trim();
        if (!tableName) {
            alert('Please enter a valid table name.');
            return;
        }
        $.ajax({
            url: '/php/user_database_action.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'create_table', table_name: tableName },
            success: function(res) {
                if (res.success) {
                    alert(res.message);
                    $('#newTableName').val('');
                    loadTablesList();
                } else {
                    alert(res.message || 'Failed to create table schema.');
                }
            }
        });
    });

    // Load & Render Custom Rows
    function loadTableRows(tableName) {
        if (!tableName) {
            $('#tableDisplayPanel').addClass('hidden');
            return;
        }
        $.ajax({
            url: '/php/user_database_action.php',
            type: 'GET',
            dataType: 'json',
            data: { action: 'get_rows', table_name: tableName },
            success: function(res) {
                if (res.success) {
                    $('#tableDisplayPanel').removeClass('hidden');
                    $('#activeTableTitle span').text(tableName);

                    const head = $('#dynamicDataTableHead');
                    const body = $('#dynamicDataTableBody');
                    head.empty();
                    body.empty();

                    let columns = ['id'];
                    if (res.rows.length > 0) {
                        res.rows.forEach(function(row) {
                            Object.keys(row).forEach(function(key) {
                                if (!columns.includes(key)) {
                                    columns.push(key);
                                }
                            });
                        });
                    } else {
                        columns.push('status');
                    }

                    columns.forEach(function(col) {
                        head.append(`<th class="p-2">${col}</th>`);
                    });
                    head.append('<th class="p-2 text-right">Actions</th>');

                    if (res.rows.length > 0) {
                        res.rows.forEach(function(row) {
                            let rowHtml = '<tr class="border-b border-gray-100 hover:bg-gray-50">';
                            columns.forEach(function(col) {
                                const cellVal = row[col] !== undefined ? row[col] : '-';
                                rowHtml += `<td class="p-2">${cellVal}</td>`;
                            });
                            rowHtml += `<td class="p-2 text-right">
                                <button class="btn btn-sm btn-outline-danger btn-delete-row rounded-md" data-id="${row.id}"><i class="fas fa-trash"></i></button>
                            </td></tr>`;
                            body.append(rowHtml);
                        });
                    } else {
                        body.append(`<tr><td colspan="${columns.length + 1}" class="text-center text-gray-400 py-3 text-xs">No records found inside '${tableName}'. Click Insert Record to begin.</td></tr>`);
                    }
                }
            }
        });
    }

    $('#activeTableSelect').on('change', function() {
        loadTableRows($(this).val());
    });

    // Drop Table
    $('#btnDropTable').on('click', function() {
        const tableName = $('#activeTableSelect').val();
        if (!tableName) {
            alert('Please choose a table schema first.');
            return;
        }
        if (!confirm(`Are you absolutely sure you want to drop '${tableName}' table?`)) return;
        $.ajax({
            url: '/php/user_database_action.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'drop_table', table_name: tableName },
            success: function(res) {
                if (res.success) {
                    alert(res.message);
                    $('#activeTableSelect').val('');
                    $('#tableDisplayPanel').addClass('hidden');
                    loadTablesList();
                } else {
                    alert(res.message);
                }
            }
        });
    });

    // Dynamic Input Fields inside Insertion Modal
    $('#btnAddColumnInput').on('click', function() {
        $('#modalColumnsContainer').append(`
            <div class="row g-2 mb-2 column-input-row d-flex gap-2">
                <div class="col-6">
                    <input type="text" class="form-control col-name-input text-xs rounded-md px-2 py-1.5 border-gray-200" placeholder="Column Key" required>
                </div>
                <div class="col-6">
                    <input type="text" class="form-control col-val-input text-xs rounded-md px-2 py-1.5 border-gray-200" placeholder="Value" required>
                </div>
            </div>
        `);
    });

    // Save Custom Database Row Record
    $('#btnSubmitInsertRow').on('click', function() {
        const tableName = $('#activeTableSelect').val();
        if (!tableName) return;

        let columns = [];
        $('.column-input-row').each(function() {
            const name = $(this).find('.col-name-input').val().trim();
            const value = $(this).find('.col-val-input').val().trim();
            if (name) {
                columns.push({ name: name, value: value });
            }
        });

        if (columns.length === 0) {
            alert('Please specify at least one column tag.');
            return;
        }

        $.ajax({
            url: '/php/user_database_action.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'insert_row', table_name: tableName, columns: columns },
            success: function(res) {
                if (res.success) {
                    $('#insertRowForm')[0].reset();
                    $('#modalColumnsContainer').html(`
                        <div class="row g-2 mb-2 column-input-row d-flex gap-2">
                            <div class="col-6">
                                <input type="text" class="form-control col-name-input text-xs rounded-md px-2 py-1.5 border-gray-200" placeholder="Column Key (e.g. name)" required>
                            </div>
                            <div class="col-6">
                                <input type="text" class="form-control col-val-input text-xs rounded-md px-2 py-1.5 border-gray-200" placeholder="Field Value" required>
                            </div>
                        </div>
                    `);

                    const insertRowModalEl = document.getElementById('insertRowModal');
                    if (insertRowModalEl) {
                        const modal = bootstrap.Modal.getInstance(insertRowModalEl);
                        if (modal) modal.hide();
                    }

                    loadTableRows(tableName);
                } else {
                    alert(res.message);
                }
            }
        });
    });

    // Delete Database Row Record
    $(document).on('click', '.btn-delete-row', function() {
        const tableName = $('#activeTableSelect').val();
        const rowId = $(this).attr('data-id');
        if (!tableName || !rowId) return;

        if (!confirm('Are you sure you want to delete this record?')) return;

        $.ajax({
            url: '/php/user_database_action.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'delete_row', table_name: tableName, row_id: rowId },
            success: function(res) {
                if (res.success) {
                    loadTableRows(tableName);
                } else {
                    alert(res.message);
                }
            }
        });
    });

    // Handle User Delete Website
    $(document).on('click', '.btn-user-delete-site', function() {
        const siteId = $(this).attr('data-id');
        if (!confirm('Are you absolutely sure you want to permanently delete this website and all its file directories on disk?\nThis action is irreversible!')) return;

        $.ajax({
            url: '/php/delete_website_action.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'delete_website', website_id: siteId },
            success: function(res) {
                alert(res.message);
                window.location.reload();
            },
            error: function(xhr) {
                alert(xhr.responseJSON ? xhr.responseJSON.message : 'Action failed.');
            }
        });
    });
});
</script>
</body>
</html>
