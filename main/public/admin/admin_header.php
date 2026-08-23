<?php
/**
 * admin_header.php
 *
 * Common layout header component for all standalone administrative pages.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../php/db.php';

secureSession();

$user = $conn->selectOne('users', ['email' => $_SESSION['email'] ?? '']);
if (!$user) {
    header('Location: /login');
    exit;
}

$activeRole = strtolower((string)($user['role'] ?? $_SESSION['role'] ?? 'tenant'));
$staffRoles = ['admin', 'super admin', 'superadmin', 'manager', 'moderator', 'support', 'financial', 'marketing_head', 'marketinghead'];
if (!in_array($activeRole, $staffRoles, true)) {
    header('Location: /login');
    exit;
}

$requestUri = $_SERVER['REQUEST_URI'] ?? '/admin/dashboard';
if (!hasAdminPagePermission($activeRole, $requestUri)) {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>403 Forbidden - Access Denied</title>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; color: #1e293b; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
            .card { text-align: center; padding: 40px; background-color: #ffffff; border-radius: 24px; box-shadow: 0 10px 25px rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.08); max-width: 500px; }
            h1 { font-size: 24px; font-weight: 800; color: #dc2626; margin: 0 0 10px; }
            p { font-size: 14px; color: #64748b; line-height: 1.6; margin: 0 0 20px; }
        </style>
    </head>
    <body>
        <div class="card">
            <h1>403 Forbidden - Access Denied</h1>
            <p>Access Denied: Your assigned role (<?php echo strtoupper(htmlspecialchars($activeRole)); ?>) is not authorized to access this section.</p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$roleClean = str_replace(' ', '', str_replace('_', '', $activeRole));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle ?? 'Admin Control Center'); ?> | nodexGo</title>

  <!-- Google Fonts & FontAwesome -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Source+Sans+3:wght@400;600;700&display=fallback" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/nx-secure.css">
  <link href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.css" rel="stylesheet">
  <link href="https://releases.transloadit.com/uppy/v4.13.3/uppy.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>

  <script>
    tailwind.config = { corePlugins: { preflight: false } }
  </script>

  <style>
    body { font-family: 'Plus Jakarta Sans', 'Source Sans 3', sans-serif; background-color: #f8fafc; }
    .dashboard-layout { display: flex; min-height: 100vh; }
    .main-content { flex-grow: 1; padding: 30px; overflow-x: hidden; min-width: 0; }
    .table-responsive { -webkit-overflow-scrolling: touch; }
    .modal-content { border: 0; border-radius: 1rem; box-shadow: 0 1rem 3rem rgba(15,23,42,.18); }
    .profile-avatar { width: 48px; height: 48px; object-fit: cover; border-radius: 50%; }
    @media (max-width: 767.98px) { .main-content { padding: 15px; } .dashboard-layout { display: block; } .sidebar-container { transform: translateX(-100%); position: fixed; inset: 0 auto 0 0; height: 100vh; } .sidebar-container.is-open { transform: translateX(0); } .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_length { float: none !important; text-align: left !important; margin-bottom: .75rem; } }

    .analytic-card { background: #ffffff; border: 1px solid rgba(0, 114, 255, 0.08); border-radius: 12px; padding: 16px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(0, 114, 255, 0.02); transition: transform 0.2s; }
    .analytic-card:hover { transform: translateY(-2px); }
    .material-shell { border-radius: 20px; background: #fff; box-shadow: 0 12px 35px rgba(15, 23, 42, .08); border: 1px solid #e8eef7; }
    .material-shell .btn, .material-shell .btn-large { border-radius: 12px; text-transform: none; font-weight: 700; }
    .material-shell .input-field input:focus, .material-shell .input-field textarea:focus { border-bottom-color: #1565c0; box-shadow: 0 1px 0 0 #1565c0; }
    .materialize-modal-full { width: 100%; max-height: 100%; height: 100%; top: 0 !important; border-radius: 0; }
  </style>
  <!-- Major secure-panel redesign; routes, roles, and action hooks remain unchanged. -->
  <link rel="stylesheet" href="/assets/css/secure-command.css?v=20260821">
</head>
<body>

<div class="dashboard-layout">
  <?php require_once __DIR__ . '/../../modul/sidebar.php'; ?>

  <div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4 border-b border-gray-100 pb-3 flex-wrap gap-3">
      <div class="d-flex align-items-center gap-3">
        <button class="btn btn-primary d-md-none rounded-pill" id="sidebarToggleBtn" type="button" style="height: 40px; width: 40px; display: flex; align-items: center; justify-content: center;">
          <i class="fa-solid fa-bars"></i>
        </button>
        <div>
          <h2 class="fw-bold text-dark mb-0" style="font-family: 'Orbitron', sans-serif; font-size: 1.5rem;"><?php echo htmlspecialchars($pageTitle ?? 'System Administration'); ?></h2>
          <p class="text-muted small mb-0 d-none d-sm-block"><?php echo htmlspecialchars($pageSubtitle ?? 'Manage platform configurations and operational workspace.'); ?></p>
        </div>
      </div>
      <div>
        <span class="badge bg-primary text-white px-2.5 py-1.5 text-xs rounded-md">
          <i class="fas fa-shield-alt mr-1"></i> PRIVILEGED MODE ACTIVE (<?php echo strtoupper(htmlspecialchars($activeRole)); ?>)
        </span>
      </div>
    </div>

    <!-- Command summary: presentation-only, with existing permissions enforced by the header. -->
    <div class="command-strip">
      <span class="command-item"><i class="fa-solid fa-shield-halved text-info"></i><strong>Operations control plane</strong></span>
      <span class="command-item"><i class="fa-solid fa-user-lock"></i>Role: <?php echo htmlspecialchars(strtoupper($activeRole)); ?></span>
      <span class="command-item"><i class="fa-solid fa-clock"></i><?php echo date('D, M j Y'); ?></span>
      <a class="btn btn-sm btn-outline-primary ms-md-auto" href="/admin/users"><i class="fa-solid fa-users me-1"></i>Users</a>
      <a class="btn btn-sm btn-primary" href="/admin/websites"><i class="fa-solid fa-globe me-1"></i>Websites</a>
    </div>
