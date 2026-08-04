<?php
/**
 * dashboard.php
 *
 * This is the enhanced dynamic AdminLTE-style Administrator Dashboard for nodexGosolutions.
 * It displays platform metrics, registered users, and provides interactive tools to
 * employ/suspend, promote/demote, and delete user profiles with advanced table filters.
 */

// Enable strict typing for better reliability
declare(strict_types=1);

// Require our system database connection layer
require_once __DIR__ . '/../../../php/db.php';

// Instantiate secure session configurations
secureSession();

// Access Control: Check if active user session holds administrative authorization
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // If not authorized, redirect back to login screen
    header('Location: /login');
    // Terminate script execution
    exit;
}

// Fetch list of registered users dynamically from system JSON tables database
$usersList = $conn->select('users');

// Initialize database instances to calculate sub-app counts and system analytics metrics
$siteCmsDb = new Database(__DIR__ . '/../../../databases', 'site_cms');
$urlDb     = new Database(__DIR__ . '/../../../databases', 'url_shortner');
$qrDb      = new Database(__DIR__ . '/../../../databases', 'qrcode');

// Count dynamic platform assets across all unified JSON database tables
$cmsPagesCount  = count($siteCmsDb->select('pages'));
$shortUrlsCount = count($urlDb->select('links'));
$qrCodesCount   = count($qrDb->select('qrcodes'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard | nodexGosolutions</title>

  <!-- Load standard Fonts and Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@300;400;600;700&family=Orbitron:wght@600;700;900&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- AdminLTE Theme and Custom Styling -->
  <style>
    :root {
      --bg-dark: #0f172a;
      --card-bg: #1e293b;
      --accent: #00d2ff;
      --accent-hover: #00a2cc;
      --text: #f8fafc;
      --text-muted: #94a3b8;
      --border-color: rgba(255, 255, 255, 0.08);
    }
    body {
      margin: 0;
      padding: 0;
      background-color: var(--bg-dark);
      color: var(--text);
      font-family: 'Source Sans 3', sans-serif;
    }
    .main-header {
      background: linear-gradient(135deg, #4f46e5, #0891b2);
      padding: 15px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 4px 20px rgba(0,0,0,0.4);
    }
    .main-header h1 {
      margin: 0;
      font-family: 'Orbitron', sans-serif;
      font-size: 22px;
      font-weight: 700;
      color: white;
    }
    .nav-links a {
      color: white;
      text-decoration: none;
      margin-left: 20px;
      font-weight: bold;
      transition: color 0.3s;
    }
    .nav-links a:hover {
      color: var(--accent);
    }
    .content-wrapper {
      max-width: 1300px;
      margin: 40px auto;
      padding: 0 20px;
    }
    .welcome-lead h2 {
      font-size: 30px;
      margin-bottom: 8px;
      font-family: 'Orbitron', sans-serif;
    }
    /* AdminLTE Info Box Styles */
    .info-box {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 8px;
      display: flex;
      min-height: 90px;
      overflow: hidden;
      margin-bottom: 20px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.15);
    }
    .info-box-icon {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 90px;
      font-size: 30px;
      color: white;
    }
    .bg-info-box { background-color: #3b82f6; }
    .bg-success-box { background-color: #10b981; }
    .bg-warning-box { background-color: #f59e0b; }
    .bg-danger-box { background-color: #ef4444; }

    .info-box-content {
      padding: 10px 15px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    .info-box-text {
      font-size: 13px;
      text-transform: uppercase;
      color: var(--text-muted);
      font-weight: 600;
    }
    .info-box-number {
      font-size: 24px;
      font-weight: 700;
      font-family: 'Orbitron', sans-serif;
    }
    /* CMS Callout */
    .cms-callout {
      background: linear-gradient(135deg, rgba(79, 70, 229, 0.12), rgba(8, 145, 178, 0.12));
      border-left: 5px solid #0891b2;
      border-radius: 0 8px 8px 0;
      padding: 24px;
      margin-bottom: 40px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 20px;
    }
    .cms-callout-text h3 {
      margin: 0 0 8px 0;
      font-family: 'Orbitron', sans-serif;
      font-size: 20px;
    }
    .btn-launch-cms {
      background: linear-gradient(135deg, #4f46e5, #0891b2);
      color: white;
      text-decoration: none;
      padding: 12px 24px;
      border-radius: 30px;
      font-weight: bold;
      font-family: 'Orbitron', sans-serif;
      box-shadow: 0 4px 12px rgba(8, 145, 178, 0.3);
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .btn-launch-cms:hover {
      transform: scale(1.03);
      box-shadow: 0 6px 15px rgba(8, 145, 178, 0.5);
      color: white;
    }
    /* Card Styles */
    .admin-card {
      background: var(--card-bg);
      border-radius: 12px;
      border: 1px solid var(--border-color);
      padding: 24px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.25);
    }
    .admin-card h3 {
      margin-top: 0;
      font-family: 'Orbitron', sans-serif;
      font-size: 18px;
      margin-bottom: 24px;
    }
    /* Table Styles */
    .table-container {
      overflow-x: auto;
    }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    th, td {
      padding: 14px 18px;
      border-bottom: 1px solid var(--border-color);
      vertical-align: middle;
    }
    th {
      font-family: 'Orbitron', sans-serif;
      color: var(--accent);
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    tr:hover {
      background: rgba(255,255,255,0.015);
    }
    .badge-status {
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: bold;
      letter-spacing: 0.5px;
    }
    .status-active {
      background: rgba(16, 185, 129, 0.15);
      color: #10b981;
      border: 1px solid rgba(16, 185, 129, 0.3);
    }
    .status-suspended {
      background: rgba(239, 68, 68, 0.15);
      color: #ef4444;
      border: 1px solid rgba(239, 68, 68, 0.3);
    }
    .badge-role {
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: bold;
      letter-spacing: 0.5px;
    }
    .role-admin {
      background: rgba(139, 92, 246, 0.15);
      color: #8b5cf6;
      border: 1px solid rgba(139, 92, 246, 0.3);
    }
    .role-tenant {
      background: rgba(6, 182, 212, 0.15);
      color: #06b6d4;
      border: 1px solid rgba(6, 182, 212, 0.3);
    }
    /* Filter Styles */
    .filter-panel {
      background: rgba(255,255,255,0.02);
      border: 1px solid var(--border-color);
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 25px;
    }
    .filter-input {
      background: rgba(0,0,0,0.25);
      border: 1px solid var(--border-color);
      color: white;
      padding: 8px 12px;
      border-radius: 6px;
      font-size: 14px;
    }
    .filter-input:focus {
      background: rgba(0,0,0,0.35);
      border-color: var(--accent);
      outline: none;
      color: white;
    }
    .btn-action {
      font-size: 12px;
      padding: 6px 12px;
      border-radius: 4px;
      font-weight: bold;
      transition: all 0.2s;
    }
  </style>
</head>
<body>

  <!-- Main Navigation Header -->
  <header class="main-header">
    <h1><i class="fa-solid fa-gauge-high me-2"></i>nodexGo Control Panel</h1>
    <div class="nav-links">
      <a href="/cms/admin.php"><i class="fa-solid fa-code me-1"></i>CMS Code Panel</a>
      <a href="/" target="_blank"><i class="fa-solid fa-globe me-1"></i>Visit Site</a>
    </div>
  </header>

  <!-- Content Container -->
  <div class="content-wrapper">

    <!-- Welcome lead header -->
    <div class="welcome-lead mb-4">
      <h2>Welcome Back, <?php echo htmlspecialchars($_SESSION['fullname'] ?? 'System Administrator'); ?>!</h2>
      <p class="text-muted mb-0">Control Center. Manage user deployments, adjust access states, and publish code payloads instantly.</p>
    </div>

    <!-- AdminLTE Style Info Boxes Stats Grid -->
    <div class="row">
      <!-- Total Users -->
      <div class="col-md-3 col-sm-6">
        <div class="info-box">
          <span class="info-box-icon bg-info-box"><i class="fa-solid fa-users"></i></span>
          <div class="info-box-content">
            <span class="info-box-text">Total Users</span>
            <span class="info-box-number"><?php echo count($usersList); ?></span>
          </div>
        </div>
      </div>
      <!-- CMS Pages -->
      <div class="col-md-3 col-sm-6">
        <div class="info-box">
          <span class="info-box-icon bg-success-box"><i class="fa-solid fa-laptop-code"></i></span>
          <div class="info-box-content">
            <span class="info-box-text">CMS Pages</span>
            <span class="info-box-number"><?php echo $cmsPagesCount; ?></span>
          </div>
        </div>
      </div>
      <!-- Shortened Links -->
      <div class="col-md-3 col-sm-6">
        <div class="info-box">
          <span class="info-box-icon bg-warning-box"><i class="fa-solid fa-link"></i></span>
          <div class="info-box-content">
            <span class="info-box-text">Short Links</span>
            <span class="info-box-number"><?php echo $shortUrlsCount; ?></span>
          </div>
        </div>
      </div>
      <!-- Dynamic QR Codes -->
      <div class="col-md-3 col-sm-6">
        <div class="info-box">
          <span class="info-box-icon bg-danger-box"><i class="fa-solid fa-qrcode"></i></span>
          <div class="info-box-content">
            <span class="info-box-text">QR Codes</span>
            <span class="info-box-number"><?php echo $qrCodesCount; ?></span>
          </div>
        </div>
      </div>
    </div>

    <!-- Code-First CMS Integration Callout Banner -->
    <div class="cms-callout">
      <div class="cms-callout-text">
        <h3><i class="fa-solid fa-code me-2"></i>Code-First CMS Controller</h3>
        <p class="text-muted mb-0">Unify code page payloads. Direct injection of HTML/CSS scripts into active routed endpoints.</p>
      </div>
      <a href="/cms/admin.php" class="btn-launch-cms"><i class="fa-solid fa-rocket me-2"></i>Launch CMS Editor</a>
    </div>

    <!-- Dynamic User Management with Live Search Filters -->
    <div class="admin-card">
      <h3><i class="fa-solid fa-users-gear me-2"></i>User Directory Management</h3>

      <!-- Live Filters Form Panel -->
      <div class="filter-panel">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label text-muted small fw-bold">SEARCH NAME / EMAIL</label>
            <input type="text" id="searchFilter" class="form-control filter-input w-100" placeholder="Type user name or email..." />
          </div>
          <div class="col-md-4">
            <label class="form-label text-muted small fw-bold">FILTER BY ROLE</label>
            <select id="roleFilter" class="form-select filter-input w-100">
              <option value="ALL">Show All Roles</option>
              <option value="ADMIN">ADMIN</option>
              <option value="TENANT">TENANT</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label text-muted small fw-bold">FILTER BY STATUS</label>
            <select id="statusFilter" class="form-select filter-input w-100">
              <option value="ALL">Show All Statuses</option>
              <option value="ACTIVE">ACTIVE</option>
              <option value="SUSPENDED">SUSPENDED</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Action Status Feedback Area -->
      <div id="actionAlertBox" class="alert d-none" role="alert"></div>

      <!-- User List Table -->
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Full Name</th>
              <th>Email Address</th>
              <th>Role Privilege</th>
              <th>Status</th>
              <th class="text-end">Administrative Adjustments</th>
            </tr>
          </thead>
          <tbody id="userTableBody">
            <?php // Render user rows dynamically from databases list
            foreach ($usersList as $user):
                $userId = (int)($user['id'] ?? 0);
                $fullname = htmlspecialchars($user['fullname'] ?? $user['full_name'] ?? '');
                $email = htmlspecialchars($user['email'] ?? '');
                $role = strtoupper(htmlspecialchars($user['role'] ?? 'tenant'));

                // Determine user status: Default to ACTIVE
                $statusVal = strtoupper(htmlspecialchars($user['status'] ?? 'active'));
            ?>
              <tr class="user-row-entry"
                  data-name="<?php echo strtolower($fullname); ?>"
                  data-email="<?php echo strtolower($email); ?>"
                  data-role="<?php echo $role; ?>"
                  data-status="<?php echo $statusVal; ?>">
                <td><?php echo $userId; ?></td>
                <td><strong><?php echo $fullname; ?></strong></td>
                <td><?php echo $email; ?></td>
                <td>
                  <span class="badge-role <?php echo $role === 'ADMIN' ? 'role-admin' : 'role-tenant'; ?>">
                    <?php echo $role; ?>
                  </span>
                </td>
                <td>
                  <span class="badge-status <?php echo $statusVal === 'SUSPENDED' ? 'status-suspended' : 'status-active'; ?>">
                    <?php echo $statusVal; ?>
                  </span>
                </td>
                <td class="text-end">
                  <?php if ($userId !== (int)($_SESSION['user_id'] ?? 0)): ?>
                    <!-- Toggle Role Privilege Action Button -->
                    <button class="btn btn-sm btn-outline-info btn-action me-1 btn-toggle-role" data-id="<?php echo $userId; ?>">
                      <i class="fa-solid fa-arrows-spin me-1"></i>Toggle Role
                    </button>
                    <!-- Toggle Status (Suspend / Reactivate) Action Button -->
                    <button class="btn btn-sm <?php echo $statusVal === 'SUSPENDED' ? 'btn-outline-success' : 'btn-outline-warning'; ?> btn-action me-1 btn-toggle-status" data-id="<?php echo $userId; ?>">
                      <i class="fa-solid <?php echo $statusVal === 'SUSPENDED' ? 'fa-user-check' : 'fa-user-slash'; ?> me-1"></i>
                      <?php echo $statusVal === 'SUSPENDED' ? 'Activate' : 'Suspend'; ?>
                    </button>
                    <!-- Delete User Account Action Button -->
                    <button class="btn btn-sm btn-outline-danger btn-action btn-delete-user" data-id="<?php echo $userId; ?>">
                      <i class="fa-solid fa-trash me-1"></i>Delete
                    </button>
                  <?php else: ?>
                    <span class="text-muted small italic">Logged In (Current)</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <!-- jQuery & Bootstrap Bundle JS libraries -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Interactive Search Filters and Admin AJAX Handlers -->
  <script>
  // Execute upon DOM initialization completeness
  $(document).ready(function() {

      // Perform live filters evaluation
      function performFilter() {
          // Extract normalized search text input value
          const query = $('#searchFilter').val().trim().toLowerCase();
          // Extract active role filters selection
          const role = $('#roleFilter').val();
          // Extract active status filters selection
          const status = $('#statusFilter').val();

          // Loop through each user table row and toggle display states dynamically
          $('.user-row-entry').each(function() {
              // Capture row attributes
              const rName = $(this).attr('data-name');
              const rEmail = $(this).attr('data-email');
              const rRole = $(this).attr('data-role');
              const rStatus = $(this).attr('data-status');

              // Verify Name/Email pattern matches
              const textMatch = rName.includes(query) || rEmail.includes(query);
              // Verify Role filter criteria
              const roleMatch = (role === 'ALL') || (rRole === role);
              // Verify Status filter criteria
              const statusMatch = (status === 'ALL') || (rStatus === status);

              // Toggle visibility based on matching statuses
              if (textMatch && roleMatch && statusMatch) {
                  // Show row
                  $(this).show();
              } else {
                  // Hide row
                  $(this).hide();
              }
          });
      }

      // Bind input events to execute live table filtering
      $('#searchFilter').on('input', performFilter);
      $('#roleFilter, #statusFilter').on('change', performFilter);

      // Helper to output status action messages
      function showFeedback(message, type) {
          // Locate alert box container handle
          const box = $('#actionAlertBox');
          // Format styling class layout
          box.removeClass('d-none alert-success alert-danger')
             .addClass('alert-' + type)
             .text(message);
      }

      // 1. AJAX Toggle Role privileged levels
      $(document).on('click', '.btn-toggle-role', function() {
          // Capture button target ID
          const userId = $(this).attr('data-id');
          // Prompt confirm box
          if (!confirm('Are you sure you want to toggle this user\'s access role privilege?')) return;

          // Dispatch asynchronous POST action
          $.ajax({
              url: '/php/admin_action.php',
              type: 'POST',
              dataType: 'json',
              data: { action: 'toggle_role', user_id: userId },
              success: function(res) {
                  if (res.success) {
                      // Output success notification
                      alert(res.message);
                      // Refresh dashboard layout to show changes
                      window.location.reload();
                  } else {
                      // Show failure alerts
                      showFeedback(res.message || 'Action failed.', 'danger');
                  }
              },
              error: function() {
                  showFeedback('Communication failure with backend administration endpoint.', 'danger');
              }
          });
      });

      // 2. AJAX Toggle User Status (Employ / Suspend)
      $(document).on('click', '.btn-toggle-status', function() {
          // Capture button target ID
          const userId = $(this).attr('data-id');
          // Prompt confirm box
          if (!confirm('Are you sure you want to adjust this user\'s status?')) return;

          // Dispatch asynchronous POST action
          $.ajax({
              url: '/php/admin_action.php',
              type: 'POST',
              dataType: 'json',
              data: { action: 'toggle_status', user_id: userId },
              success: function(res) {
                  if (res.success) {
                      // Output success notification
                      alert(res.message);
                      // Refresh dashboard layout to show changes
                      window.location.reload();
                  } else {
                      // Show failure alerts
                      showFeedback(res.message || 'Action failed.', 'danger');
                  }
              },
              error: function() {
                  showFeedback('Communication failure with backend administration endpoint.', 'danger');
              }
          });
      });

      // 3. AJAX Delete User accounts completely
      $(document).on('click', '.btn-delete-user', function() {
          // Capture button target ID
          const userId = $(this).attr('data-id');
          // Prompt confirm box
          if (!confirm('CRITICAL ACTION: Are you sure you want to delete this user profile completely from JSON database? This action is irreversible.')) return;

          // Dispatch asynchronous POST action
          $.ajax({
              url: '/php/admin_action.php',
              type: 'POST',
              dataType: 'json',
              data: { action: 'delete_user', user_id: userId },
              success: function(res) {
                  if (res.success) {
                      // Output success notification
                      alert(res.message);
                      // Refresh dashboard layout to show changes
                      window.location.reload();
                  } else {
                      // Show failure alerts
                      showFeedback(res.message || 'Action failed.', 'danger');
                  }
              },
              error: function() {
                  showFeedback('Communication failure with backend administration endpoint.', 'danger');
              }
          });
      });

  });
  </script>

</body>
</html>
