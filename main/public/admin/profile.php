<?php
/**
 * Administrator profile page.
 *
 * This page handles only the signed-in administrator's own profile. The same
 * server-side image validation rules are used for admin and tenant avatars.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../../php/db.php';
secureSession();
if (empty($_SESSION['email'])) { header('Location: /login'); exit; }
$user = $conn->selectOne('users', ['email' => $_SESSION['email']]);
if (!$user) { header('Location: /login'); exit; }

if (empty($_SESSION['profile_csrf'])) $_SESSION['profile_csrf'] = bin2hex(random_bytes(24));
$success = '';
$error = '';

/** Store a safe, resized avatar using the detected MIME type rather than the filename extension. */
function saveProfileAvatar(array $upload): string
{
    if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('Please choose a valid profile picture.');
    if (($upload['size'] ?? 0) > 5 * 1024 * 1024) throw new RuntimeException('Profile pictures must be 5 MB or smaller.');
    $info = @getimagesize((string)$upload['tmp_name']);
    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
    $mime = (string)($info['mime'] ?? '');
    if (!$info || !isset($allowed[$mime])) throw new RuntimeException('Use a valid JPG, PNG, GIF, or WebP image.');
    $directory = __DIR__ . '/../../../uploads/avatars';
    if (!is_dir($directory) && !@mkdir($directory, 0755, true)) throw new RuntimeException('Avatar storage is not writable.');
    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    $destination = $directory . '/' . $name;
    if (!move_uploaded_file((string)$upload['tmp_name'], $destination)) throw new RuntimeException('Unable to save the profile picture.');
    @chmod($destination, 0644);
    return '/uploads/avatars/' . $name;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals((string)($_SESSION['profile_csrf'] ?? ''), (string)($_POST['csrf'] ?? ''))) $error = 'Security token expired. Refresh and try again.';
    $action = (string)($_POST['action'] ?? '');
    if ($error === '' && $action === 'update_profile') {
        $name = cleanInput((string)($_POST['fullname'] ?? ''));
        if ($name === '') $error = 'Full name is required.';
        $avatar = (string)($user['avatar'] ?? '');
        if ($error === '' && isset($_FILES['avatar_file']) && ($_FILES['avatar_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try { $avatar = saveProfileAvatar($_FILES['avatar_file']); } catch (Throwable $e) { $error = $e->getMessage(); }
        }
        if ($error === '') { $conn->update('users', ['fullname'=>$name,'avatar'=>$avatar,'updated_at'=>date('Y-m-d H:i:s')], ['id'=>$user['id']]); $_SESSION['fullname']=$name; $success='Profile updated successfully.'; $user=$conn->selectOne('users',['id'=>$user['id']]); }
    }
    if ($error === '' && $action === 'update_password') {
        $current=(string)($_POST['current_password']??''); $new=(string)($_POST['new_password']??''); $confirm=(string)($_POST['confirm_password']??'');
        if (!password_verify($current, (string)$user['password'])) $error='Current password is incorrect.';
        elseif (strlen($new)<8 || $new!==$confirm) $error='New passwords must match and contain at least 8 characters.';
        else { $conn->update('users',['password'=>password_hash($new,PASSWORD_DEFAULT),'updated_at'=>date('Y-m-d H:i:s')],['id'=>$user['id']]); $success='Password updated successfully.'; }
    }
}

$pageTitle = 'My Profile';
$pageSubtitle = 'Manage your account details, profile picture, and security.';
require_once __DIR__ . '/admin_header.php';
$avatar = trim((string)($user['avatar'] ?? ''));
?>
<section class="row g-4">
  <div class="col-12 col-xl-4"><div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body text-center p-4"><div class="mb-3"><?php if($avatar): ?><img src="<?php echo htmlspecialchars($avatar); ?>" class="rounded-circle object-fit-cover" style="width:120px;height:120px" alt="Your profile picture"><?php else: ?><div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center fw-bold" style="width:120px;height:120px;font-size:3rem"><?php echo htmlspecialchars(strtoupper(substr((string)$user['fullname'],0,1))); ?></div><?php endif; ?></div><h1 class="h5 fw-bold mb-1"><?php echo htmlspecialchars((string)$user['fullname']); ?></h1><p class="text-muted small mb-3"><?php echo htmlspecialchars((string)$user['email']); ?></p><span class="badge text-bg-primary text-uppercase"><?php echo htmlspecialchars(str_replace('_',' ',(string)$user['role'])); ?></span><hr><dl class="row small text-start mb-0"><dt class="col-5">Status</dt><dd class="col-7"><?php echo htmlspecialchars((string)($user['status'] ?? 'active')); ?></dd><dt class="col-5">Joined</dt><dd class="col-7"><?php echo htmlspecialchars((string)($user['created_at'] ?? '-')); ?></dd></dl></div></div></div>
  <div class="col-12 col-xl-8"><div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body p-4"><h2 class="h6 fw-bold">Profile details</h2><?php if($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?><?php if($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?><form method="post" enctype="multipart/form-data" class="row g-3" novalidate><input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['profile_csrf']); ?>"><input type="hidden" name="action" value="update_profile"><div class="col-12"><label class="form-label">Full name</label><input class="form-control" name="fullname" value="<?php echo htmlspecialchars((string)$user['fullname']); ?>" required></div><div class="col-12"><label class="form-label">Profile picture</label><input class="form-control" type="file" name="avatar_file" accept="image/jpeg,image/png,image/gif,image/webp"><div class="form-text">JPG, PNG, GIF, or WebP up to 5 MB.</div></div><div class="col-12"><button class="btn btn-primary">Save profile</button></div></form></div></div><div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4"><h2 class="h6 fw-bold">Password and security</h2><form method="post" class="row g-3" novalidate><input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['profile_csrf']); ?>"><input type="hidden" name="action" value="update_password"><div class="col-12"><label class="form-label">Current password</label><input class="form-control" type="password" name="current_password" required></div><div class="col-md-6"><label class="form-label">New password</label><input class="form-control" type="password" name="new_password" minlength="8" required></div><div class="col-md-6"><label class="form-label">Confirm password</label><input class="form-control" type="password" name="confirm_password" minlength="8" required></div><div class="col-12"><button class="btn btn-outline-primary">Update password</button></div></form></div></div></div>
</section>
<?php require_once __DIR__ . '/admin_footer.php'; ?>
