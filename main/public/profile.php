<?php
/**
 * profile.php
 *
 * Premium, White & Blue themed Profile Page for nodexGosolutions.
 * Fully styled using Tailwind CSS and compatible with AdminLTE 3/Bootstrap.
 * Allowing name changes, avatar pictures, and password updates for both tenants and admin.
 */

// Enable strict typing for safety
declare(strict_types=1);

// Require central database and security layers
require_once __DIR__ . '/../../php/db.php';

// Instantiate secure session configurations
secureSession();

// Access Control: Ensure user session is active
if (!isset($_SESSION['email'])) {
    header('Location: /login');
    exit;
}

$currentUserEmail = $_SESSION['email'];

// Retrieve logged-in user's profile details from systems database
$userProfile = $conn->selectOne('users', ['email' => $currentUserEmail]);

if (!$userProfile) {
    die("User profile not found. Please log in again.");
}

$successMessage = '';
$errorMessage = '';

// Handle Profile Updates POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Profile Details Update (Full Name & Avatar)
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $fullname = cleanInput($_POST['fullname'] ?? '');
        $avatarBase64 = $_POST['avatar_base64'] ?? '';

        // Handling file upload for real avatars
        $avatarUrl = $userProfile['avatar'] ?? '';
        if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['avatar_file']['tmp_name'];
            $fileName = $_FILES['avatar_file']['name'];
            $fileSize = $_FILES['avatar_file']['size'];
            $fileType = $_FILES['avatar_file']['type'];

            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($fileExtension, $allowedExtensions)) {
                // Ensure upload directories exist
                $uploadDir1 = __DIR__ . '/../../uploads/avatars/';
                $uploadDir2 = __DIR__ . '/uploads/avatars/';
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
            // Fallback base64 or preselected options
            $avatarUrl = $avatarBase64;
        }

        if (empty($fullname)) {
            $errorMessage = 'Full name field cannot be empty.';
        }

        if (empty($errorMessage)) {
            // Update systems JSON database
            $conn->update('users', [
                'fullname' => $fullname,
                'avatar' => $avatarUrl,
                'updated_at' => date('Y-m-d H:i:s')
            ], ['email' => $currentUserEmail]);

            // Sync session variables
            $_SESSION['fullname'] = $fullname;

            // Reload user profile details
            $userProfile = $conn->selectOne('users', ['email' => $currentUserEmail]);
            $successMessage = 'Profile information successfully updated!';
        }
    }

    // 2. Secure Password Update
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
            // Verify current password first
            if (password_verify($currentPassword, $userProfile['password'])) {
                $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $conn->update('users', [
                    'password' => $hashedNewPassword,
                    'updated_at' => date('Y-m-d H:i:s')
                ], ['email' => $currentUserEmail]);

                $successMessage = 'Your password has been changed successfully!';
            } else {
                $errorMessage = 'Invalid current password entered.';
            }
        }
    }
}

// Prepare avatar output
$currentAvatar = $userProfile['avatar'] ?? '';
$initialLetter = strtoupper(substr($userProfile['fullname'] ?? 'S', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?= renderSeoHead([
      'title' => 'Manage Profile | nodexGosolutions',
      'description' => 'User profile management panel for nodexGosolutions.',
      'og_title' => 'Manage Profile | nodexGosolutions',
      'og_description' => 'User profile management panel for nodexGosolutions.',
      'og_image' => '/main/assets/images/cedar-anyanwu.jpg',
      'og_type' => 'profile',
      'schema_type' => 'ProfilePage',
      'person_params' => [
          'name' => $userProfile['fullname'] ?? 'Cedar Anyanwu',
          'url' => 'https://nodexplatform.com.ng',
          'image' => 'https://nodexplatform.com.ng',
          'jobTitle' => $userProfile['role'] ?? 'Chief Executive Officer',
          'organization' => 'Nodexplatform',
          'sameAs' => ['https://linkedin.com']
      ]
  ]) ?>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- AdminLTE 3 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>

  <script>
    // Tailwind Configuration to avoid style overrides with AdminLTE
    tailwind.config = {
      corePlugins: {
        preflight: false,
      }
    }
  </script>

  <style>
    .avatar-preview-box {
      width: 110px;
      height: 110px;
      border-radius: 50%;
      border: 3px solid #3b82f6;
      box-shadow: 0 4px 10px rgba(59, 130, 246, 0.15);
      background-size: cover;
      background-position: center;
    }
  </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed" style="background-color: #f8fafc;">
<div class="wrapper">

  <!-- Include modular Navigation Bar component -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light border-b border-gray-100 px-3">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button" id="sidebarToggleBtn"><i class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="/" class="nav-link font-semibold">Home</a>
      </li>
    </ul>

    <ul class="navbar-nav ml-auto flex items-center gap-3">
      <li class="nav-item">
        <span class="badge bg-blue-100 text-blue-800 px-2.5 py-1.5 text-xs rounded-md">
          Logged in as: <?php echo htmlspecialchars($userProfile['fullname'] ?? ''); ?>
        </span>
      </li>
    </ul>
  </nav>

  <!-- Include modular Sidebar component -->
  <?php require_once __DIR__ . '/../modul/sidebar.php'; ?>

  <!-- Content Wrapper -->
  <div class="content-wrapper p-4" style="background-color: #f8fafc;">
    <div class="content-header p-0 mb-4">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0 text-2xl font-bold text-gray-800">My Account Profile</h1>
            <p class="text-xs text-gray-500 m-0">Modify personal credentials, select avatar representations, and update authorization codes.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <section class="content">
      <div class="container-fluid">

        <!-- Status Alerts -->
        <?php if (!empty($successMessage)): ?>
          <div class="p-4 mb-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-md text-sm text-emerald-800 font-semibold shadow-sm">
            <i class="fa-solid fa-circle-check mr-2 text-emerald-600 text-base"></i> <?php echo $successMessage; ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($errorMessage)): ?>
          <div class="p-4 mb-4 bg-rose-50 border-l-4 border-rose-500 rounded-md text-sm text-rose-800 font-semibold shadow-sm">
            <i class="fa-solid fa-triangle-exclamation mr-2 text-rose-600 text-base"></i> <?php echo $errorMessage; ?>
          </div>
        <?php endif; ?>

        <div class="row">

          <!-- Profile Card Left (Avatar View) -->
          <div class="col-lg-4 col-12 mb-4">
            <div class="card border-0 shadow-sm rounded-lg p-4 text-center bg-white">

              <!-- User Profile Avatar Image/Initial Preview -->
              <div class="flex justify-center mb-4">
                <?php if (!empty($currentAvatar) && str_starts_with($currentAvatar, '/')): ?>
                  <div class="avatar-preview-box" style="background-image: url('<?php echo htmlspecialchars($currentAvatar); ?>');"></div>
                <?php else: ?>
                  <div class="avatar-preview-box flex items-center justify-center bg-blue-100 text-blue-600 font-bold text-4xl">
                    <?php echo $initialLetter; ?>
                  </div>
                <?php endif; ?>
              </div>

              <h3 class="text-lg font-bold text-gray-800 mb-0"><?php echo htmlspecialchars($userProfile['fullname'] ?? ''); ?></h3>
              <p class="text-xs text-gray-400 mt-1 mb-3"><?php echo htmlspecialchars($userProfile['email'] ?? ''); ?></p>

              <span class="badge bg-blue-100 text-blue-800 px-3 py-1.5 rounded-pill text-xs font-bold uppercase tracking-wider">
                Role: <?php echo strtoupper(htmlspecialchars($userProfile['role'] ?? 'tenant')); ?>
              </span>

              <hr class="my-4 border-gray-100" />

              <div class="text-left text-xs text-gray-500 space-y-2.5">
                <div class="flex justify-between">
                  <span class="font-bold">Status:</span>
                  <span class="font-semibold text-emerald-600"><?php echo strtoupper(htmlspecialchars($userProfile['status'] ?? 'active')); ?></span>
                </div>
                <div class="flex justify-between">
                  <span class="font-bold">Member Since:</span>
                  <span><?php echo htmlspecialchars($userProfile['created_at'] ?? 'August 2026'); ?></span>
                </div>
                <div class="flex justify-between">
                  <span class="font-bold">Last Update:</span>
                  <span><?php echo htmlspecialchars($userProfile['updated_at'] ?? 'Just now'); ?></span>
                </div>
              </div>

            </div>
          </div>

          <!-- Edit Forms Right (Profile Data & Passwords) -->
          <div class="col-lg-8 col-12 mb-4">

            <!-- Form 1: Profile Information -->
            <div class="card border-0 shadow-sm rounded-lg bg-white mb-4">
              <div class="card-header bg-white border-b border-gray-100 py-3">
                <h3 class="text-base font-bold text-gray-800 m-0 flex items-center">
                  <i class="fas fa-user-edit text-blue-600 mr-2"></i> Update Profile Information
                </h3>
              </div>
              <div class="card-body p-4">
                <form action="/profile" method="POST" enctype="multipart/form-data">
                  <input type="hidden" name="action" value="update_profile" />

                  <!-- Full Name field -->
                  <div class="mb-4">
                    <label class="block text-xs font-bold uppercase text-gray-500 mb-2">My Full Name</label>
                    <input type="text" name="fullname" class="form-control text-sm rounded-md px-3.5 py-2.5 border-gray-200 w-full" value="<?php echo htmlspecialchars($userProfile['fullname'] ?? ''); ?>" required />
                  </div>

                  <!-- Upload Avatar File field -->
                  <div class="mb-4">
                    <label class="block text-xs font-bold uppercase text-gray-500 mb-2">Upload Profile Avatar File</label>
                    <input type="file" name="avatar_file" class="form-control text-sm rounded-md border-gray-200 w-full" accept="image/*" />
                    <p class="text-2xs text-gray-400 mt-1">Supported formats: webp, png, jpeg, gif. Maximum size: 2MB.</p>
                  </div>

                  <!-- Preselected Quick Avatar Preset Options -->
                  <div class="mb-4">
                    <label class="block text-xs font-bold uppercase text-gray-500 mb-2">Or Choose Avatar Preset Profile</label>
                    <div class="flex items-center gap-3">
                      <label class="cursor-pointer">
                        <input type="radio" name="avatar_base64" value="" class="sr-only peer" checked />
                        <div class="w-10 h-10 rounded-full border border-gray-200 bg-gray-50 hover:border-blue-500 flex items-center justify-center peer-checked:border-blue-600 peer-checked:ring-2 peer-checked:ring-blue-100 font-bold text-sm">
                          Default
                        </div>
                      </label>

                      <!-- Quick option 1 (Female developer preset) -->
                      <label class="cursor-pointer">
                        <input type="radio" name="avatar_base64" value="https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&q=80&w=150" class="sr-only peer" />
                        <div class="w-10 h-10 rounded-full border border-gray-200 bg-cover bg-center hover:border-blue-500 peer-checked:border-blue-600 peer-checked:ring-2 peer-checked:ring-blue-100" style="background-image: url('https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&q=80&w=150');">
                        </div>
                      </label>

                      <!-- Quick option 2 (Male developer preset) -->
                      <label class="cursor-pointer">
                        <input type="radio" name="avatar_base64" value="https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&q=80&w=150" class="sr-only peer" />
                        <div class="w-10 h-10 rounded-full border border-gray-200 bg-cover bg-center hover:border-blue-500 peer-checked:border-blue-600 peer-checked:ring-2 peer-checked:ring-blue-100" style="background-image: url('https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&q=80&w=150');">
                        </div>
                      </label>
                    </div>
                  </div>

                  <div class="pt-2 text-right">
                    <button type="submit" class="btn btn-primary btn-sm bg-blue-600 text-white rounded-md font-bold px-4 py-2">
                      <i class="fa-solid fa-cloud-arrow-up mr-1.5"></i> Save Profile Details
                    </button>
                  </div>
                </form>
              </div>
            </div>

            <!-- Form 2: Password Update -->
            <div class="card border-0 shadow-sm rounded-lg bg-white">
              <div class="card-header bg-white border-b border-gray-100 py-3">
                <h3 class="text-base font-bold text-gray-800 m-0 flex items-center">
                  <i class="fas fa-lock text-blue-600 mr-2"></i> Update Security Password
                </h3>
              </div>
              <div class="card-body p-4">
                <form action="/profile" method="POST">
                  <input type="hidden" name="action" value="update_password" />

                  <div class="row">
                    <!-- Current Password -->
                    <div class="col-12 mb-3">
                      <label class="block text-xs font-bold uppercase text-gray-500 mb-2">Current Security Password</label>
                      <input type="password" name="current_password" class="form-control text-sm rounded-md px-3.5 py-2.5 border-gray-200 w-full" placeholder="••••••••" required />
                    </div>

                    <!-- New Password -->
                    <div class="col-md-6 col-12 mb-3">
                      <label class="block text-xs font-bold uppercase text-gray-500 mb-2">New Security Password</label>
                      <input type="password" name="new_password" class="form-control text-sm rounded-md px-3.5 py-2.5 border-gray-200 w-full" placeholder="At least 6 characters" required />
                    </div>

                    <!-- Confirm Password -->
                    <div class="col-md-6 col-12 mb-3">
                      <label class="block text-xs font-bold uppercase text-gray-500 mb-2">Confirm New Password</label>
                      <input type="password" name="confirm_password" class="form-control text-sm rounded-md px-3.5 py-2.5 border-gray-200 w-full" placeholder="Re-type password" required />
                    </div>
                  </div>

                  <div class="pt-3 text-right">
                    <button type="submit" class="btn btn-warning btn-sm bg-amber-500 text-white border-0 rounded-md font-bold px-4 py-2 hover:bg-amber-600">
                      <i class="fa-solid fa-key mr-1.5"></i> Securely Update Password
                    </button>
                  </div>
                </form>
              </div>
            </div>

          </div>

        </div>

      </div>
    </section>
  </div>

</div>

<!-- Required Scripts: jQuery, Bootstrap 4, AdminLTE -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>
