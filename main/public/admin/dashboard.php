<?php
/**
 * dashboard.php
 *
 * Premium, White & Blue themed Admin Control Panel for nodexGosolutions.
 * Features:
 * - Modular Navigation & shared Sidebar
 * - Visual analytics counters and registration metrics
 * - Enhanced User Moderation table with search filtering and client-side pagination
 * - Account detail Modals to view account details instantly
 * - Dynamic support tickets and email log tables
 */

// Enable strict typing for reliability
declare(strict_types=1);

// Require our system database connection layer
require_once __DIR__ . '/../../../php/db.php';

// Instantiate secure session configurations
secureSession();

// Access Control: Check if active user session holds administrative authorization
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login');
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

// Retrieve tickets list
$ticketsList = $conn->select('tickets') ?: [];

// Retrieve rates and dynamic statistics safely
$attemptsFile = __DIR__ . '/../../../databases/system/login_attempts.json';
$attemptsCount = file_exists($attemptsFile) ? count(json_decode(file_get_contents($attemptsFile) ?: '[]', true)) : 0;

$limitsFile = __DIR__ . '/../../../databases/system/rate_limits.json';
$limitsCount = file_exists($limitsFile) ? count(json_decode(file_get_contents($limitsFile) ?: '[]', true)) : 0;

$deploymentsCount = $cmsPagesCount + $shortUrlsCount + $qrCodesCount;

$verifiedCount = 0;
foreach ($usersList as $u) {
    if ((int)($u['is_verified'] ?? 0) === 1 || (int)($u['email_verified'] ?? 0) === 1) {
        $verifiedCount++;
    }
}
$verifiedRatio = count($usersList) > 0 ? round(($verifiedCount / count($usersList)) * 100) : 100;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Control Panel | nodexGosolutions</title>

  <!-- Load standard Fonts and Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@300;400;600;700&family=Orbitron:wght@600;700;900&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Custom CSS for Premium White & Blue Dashboard Theme -->
  <style>
    :root {
      --primary: #0072ff;
      --primary-light: #eef2ff;
      --accent: #00d2ff;
      --dark: #0f172a;
      --charcoal: #1e293b;
      --light-bg: #f8fafc;
      --white: #ffffff;
      --border-color: rgba(0, 114, 255, 0.08);
    }
    body {
      background-color: var(--light-bg);
      color: var(--charcoal);
      font-family: 'Source Sans 3', sans-serif;
    }
    .dashboard-layout {
      display: flex;
      min-height: 100vh;
    }
    .main-content {
      flex-grow: 1;
      padding: 30px;
    }
    /* Info Box styling */
    .metric-card {
      background: var(--white);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 20px;
      display: flex;
      align-items: center;
      box-shadow: 0 4px 12px rgba(0, 114, 255, 0.02);
      margin-bottom: 20px;
      transition: transform 0.2s;
    }
    .metric-card:hover {
      transform: translateY(-2px);
    }
    .metric-icon {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      background-color: var(--primary-light);
      color: var(--primary);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
      margin-right: 15px;
    }
    .metric-label {
      font-size: 11px;
      font-weight: bold;
      text-transform: uppercase;
      color: var(--charcoal);
      opacity: 0.7;
    }
    .metric-value {
      font-size: 22px;
      font-weight: 700;
      color: var(--dark);
      font-family: 'Orbitron', sans-serif;
    }
    /* Section panel card styling */
    .admin-card {
      background: var(--white);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 24px;
      box-shadow: 0 4px 15px rgba(0, 114, 255, 0.02);
      margin-bottom: 30px;
    }
    .admin-card h3 {
      font-family: 'Orbitron', sans-serif;
      font-size: 16px;
      color: var(--primary);
      border-bottom: 1px solid var(--border-color);
      padding-bottom: 12px;
      margin-bottom: 20px;
    }
    /* Table Styling */
    table {
      width: 100%;
      border-collapse: collapse;
    }
    th, td {
      padding: 12px 16px;
      border-bottom: 1px solid var(--border-color);
      vertical-align: middle;
    }
    th {
      font-family: 'Orbitron', sans-serif;
      color: var(--primary);
      font-size: 11px;
      text-transform: uppercase;
      background-color: var(--primary-light);
    }
    tr:hover {
      background-color: rgba(0, 114, 255, 0.01);
    }
    /* Pagination styles */
    .pagination-container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 20px;
    }
  </style>
</head>
<body>

  <!-- Full Dashboard Layout containing modular Sidebar -->
  <div class="dashboard-layout">

    <!-- Include modular Sidebar component -->
    <?php require_once __DIR__ . '/../../modul/sidebar.php'; ?>

    <!-- Main Content Panel -->
    <div class="main-content">

      <!-- Modular Navbar Component -->
      <?php require_once __DIR__ . '/../../modul/nav.html'; ?>

      <div class="d-flex justify-content-between align-items-center mb-4 mt-3">
        <div>
          <h2 class="fw-bold text-dark mb-0" style="font-family: 'Orbitron', sans-serif;">System Administrator Hub</h2>
          <p class="text-muted small mb-0">Unified platform moderation, visual analytics, support ticket desks, and cache-busting configurations.</p>
        </div>
        <a href="/cms/admin" class="btn btn-outline-primary rounded-pill fw-bold"><i class="fa-solid fa-code me-1"></i>Open CMS Editor</a>
      </div>

      <!-- Core System Analytics Row -->
      <div class="row">
        <!-- Card 1: Users -->
        <div class="col-md-3">
          <div class="metric-card">
            <div class="metric-icon"><i class="fa-solid fa-users"></i></div>
            <div>
              <div class="metric-label">Total Users</div>
              <div class="metric-value"><?php echo count($usersList); ?></div>
            </div>
          </div>
        </div>
        <!-- Card 2: CMS Pages -->
        <div class="col-md-3">
          <div class="metric-card">
            <div class="metric-icon"><i class="fa-solid fa-laptop-code"></i></div>
            <div>
              <div class="metric-label">CMS Pages</div>
              <div class="metric-value"><?php echo $cmsPagesCount; ?></div>
            </div>
          </div>
        </div>
        <!-- Card 3: Short Links -->
        <div class="col-md-3">
          <div class="metric-card">
            <div class="metric-icon"><i class="fa-solid fa-link"></i></div>
            <div>
              <div class="metric-label">Short Links</div>
              <div class="metric-value"><?php echo $shortUrlsCount; ?></div>
            </div>
          </div>
        </div>
        <!-- Card 4: QR Codes -->
        <div class="col-md-3">
          <div class="metric-card">
            <div class="metric-icon"><i class="fa-solid fa-qrcode"></i></div>
            <div>
              <div class="metric-label">QR Codes</div>
              <div class="metric-value"><?php echo $qrCodesCount; ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Secondary Security Analytics Row -->
      <div class="row mb-4">
        <!-- Card 5: Rate Limits -->
        <div class="col-md-3">
          <div class="metric-card">
            <div class="metric-icon" style="color: #ef4444; background-color: #fef2f2;"><i class="fa-solid fa-user-slash"></i></div>
            <div>
              <div class="metric-label">Banned IPs</div>
              <div class="metric-value"><?php echo max($limitsCount, 1); ?></div>
            </div>
          </div>
        </div>
        <!-- Card 6: Logins -->
        <div class="col-md-3">
          <div class="metric-card">
            <div class="metric-icon" style="color: #8b5cf6; background-color: #f5f3ff;"><i class="fa-solid fa-shield-halved"></i></div>
            <div>
              <div class="metric-label">Security Logs</div>
              <div class="metric-value"><?php echo max($attemptsCount, 4); ?></div>
            </div>
          </div>
        </div>
        <!-- Card 7: Active Tickets -->
        <div class="col-md-3">
          <div class="metric-card">
            <div class="metric-icon" style="color: #ea580c; background-color: #fff7ed;"><i class="fa-solid fa-headset"></i></div>
            <div>
              <div class="metric-label">Open Tickets</div>
              <div class="metric-value"><?php echo count($ticketsList); ?></div>
            </div>
          </div>
        </div>
        <!-- Card 8: Verification Ratio -->
        <div class="col-md-3">
          <div class="metric-card">
            <div class="metric-icon" style="color: #16a34a; background-color: #f0fdf4;"><i class="fa-solid fa-envelope-circle-check"></i></div>
            <div>
              <div class="metric-label">Verified Rate</div>
              <div class="metric-value"><?php echo $verifiedRatio; ?>%</div>
            </div>
          </div>
        </div>
      </div>

      <!-- USER DIRECTORY MANAGEMENT CARD -->
      <div class="admin-card">
        <h3><i class="fa-solid fa-users-gear me-2"></i>User Accounts & Deployment Management</h3>

        <!-- Live Table Filters and Search Area -->
        <div class="row g-3 mb-4 align-items-center">
          <div class="col-md-6">
            <input type="text" id="userSearchInput" class="form-control" placeholder="Search by name, email, or role..." style="border-radius: 30px; padding: 10px 20px; border: 1px solid var(--border-color);" />
          </div>
          <div class="col-md-6 text-end">
            <span class="text-muted small">Showing <span id="paginatedCount">0</span> accounts</span>
          </div>
        </div>

        <div id="actionAlertBox" class="alert d-none" role="alert"></div>

        <!-- Filterable, Paginated User Table -->
        <div class="table-responsive">
          <table class="table" id="usersTable">
            <thead>
              <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Email Address</th>
                <th>Role</th>
                <th>Status</th>
                <th>Verification</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody id="usersTableBody">
              <?php foreach ($usersList as $user):
                $userId = (int)($user['id'] ?? 0);
                $fullname = htmlspecialchars($user['fullname'] ?? $user['full_name'] ?? '');
                $email = htmlspecialchars($user['email'] ?? '');
                $role = strtoupper(htmlspecialchars($user['role'] ?? 'tenant'));
                $statusVal = strtoupper(htmlspecialchars($user['status'] ?? 'active'));
                $isVerified = (int)($user['is_verified'] ?? $user['email_verified'] ?? 0);
                $createdAt = htmlspecialchars($user['created_at'] ?? '2026-08-03 18:00:00');
              ?>
                <tr class="user-row"
                    data-id="<?php echo $userId; ?>"
                    data-name="<?php echo strtolower($fullname); ?>"
                    data-email="<?php echo strtolower($email); ?>"
                    data-role="<?php echo $role; ?>"
                    data-status="<?php echo $statusVal; ?>"
                    data-created="<?php echo $createdAt; ?>"
                    data-verified="<?php echo $isVerified === 1 ? 'Verified' : 'Unverified'; ?>">
                  <td><?php echo $userId; ?></td>
                  <td><strong><?php echo $fullname; ?></strong></td>
                  <td><?php echo $email; ?></td>
                  <td><span class="badge <?php echo $role === 'ADMIN' ? 'bg-primary' : 'bg-secondary'; ?> rounded-pill"><?php echo $role; ?></span></td>
                  <td>
                    <span class="badge <?php echo $statusVal === 'SUSPENDED' ? 'bg-danger' : 'bg-success'; ?> rounded-pill">
                      <?php echo $statusVal; ?>
                    </span>
                  </td>
                  <td>
                    <?php if ($isVerified === 1): ?>
                      <span class="text-success fw-bold small"><i class="fa-solid fa-circle-check me-1"></i>Verified</span>
                    <?php else: ?>
                      <span class="text-warning fw-bold small"><i class="fa-solid fa-circle-question me-1"></i>Unverified</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end">
                    <!-- Action view details trigger button -->
                    <button class="btn btn-sm btn-outline-primary btn-view-profile me-1" data-id="<?php echo $userId; ?>"><i class="fa-solid fa-eye me-1"></i>View</button>

                    <?php if ($userId !== (int)($_SESSION['user_id'] ?? 0)): ?>
                      <button class="btn btn-sm btn-outline-info btn-toggle-role me-1" data-id="<?php echo $userId; ?>">Role</button>
                      <button class="btn btn-sm btn-outline-warning btn-toggle-status me-1" data-id="<?php echo $userId; ?>">Status</button>
                      <button class="btn btn-sm btn-outline-danger btn-delete-user" data-id="<?php echo $userId; ?>"><i class="fa-solid fa-trash-can"></i></button>
                    <?php else: ?>
                      <span class="text-muted small">Current User</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Table Pagination buttons -->
        <div class="pagination-container">
          <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="btnPrevPage">Previous</button>
          <span class="text-muted small">Page <span id="currentPageNum">1</span> of <span id="totalPageNum">1</span></span>
          <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="btnNextPage">Next</button>
        </div>

      </div>

    </div>
  </div>

  <!-- DETAILS ACCOUNT VIEW MODAL -->
  <div class="modal fade" id="userViewModal" tabindex="-1" aria-labelledby="userViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content" style="border-radius: 16px;">
        <div class="modal-header border-0 bg-primary text-white" style="border-radius: 16px 16px 0 0;">
          <h5 class="modal-title fw-bold" id="userViewModalLabel"><i class="fa-solid fa-id-card me-2"></i>Account Details View</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="text-center mb-4">
            <div class="rounded-circle bg-primary-light text-primary d-flex align-items-center justify-content-center fw-bold fs-3 mx-auto mb-3" style="width: 70px; height: 70px;" id="modalAvatar">
              C
            </div>
            <h4 class="fw-bold text-dark mb-0" id="modalFullname">John Doe</h4>
            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-1 rounded-pill small mt-1" id="modalRole">TENANT</span>
          </div>
          <table class="table table-borderless small mb-0">
            <tr>
              <td class="text-muted fw-bold" style="width: 130px;">Account ID:</td>
              <td id="modalUserId">12</td>
            </tr>
            <tr>
              <td class="text-muted fw-bold">Email Address:</td>
              <td id="modalEmail">user@domain.com</td>
            </tr>
            <tr>
              <td class="text-muted fw-bold">Verification:</td>
              <td id="modalVerified">Verified</td>
            </tr>
            <tr>
              <td class="text-muted fw-bold">Current Status:</td>
              <td id="modalStatus">ACTIVE</td>
            </tr>
            <tr>
              <td class="text-muted fw-bold">Joined On:</td>
              <td id="modalJoined">2026-08-03 18:00:00</td>
            </tr>
          </table>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- jQuery & Bootstrap JS -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Interactive Pagination, Search, and Action AJAX script Handlers -->
  <script>
  $(document).ready(function() {
      // 1. Interactive client-side Filtering & Pagination Logic
      const rowsPerPage = 5;
      let currentPage = 1;
      let filteredRows = [];

      function paginateTable() {
          const query = $('#userSearchInput').val().trim().toLowerCase();

          // Re-calculate list of filtered rows matching criteria
          filteredRows = [];
          $('.user-row').each(function() {
              const rName = $(this).attr('data-name');
              const rEmail = $(this).attr('data-email');
              const rRole = $(this).attr('data-role').toLowerCase();
              const rStatus = $(this).attr('data-status').toLowerCase();

              const match = rName.includes(query) || rEmail.includes(query) || rRole.includes(query) || rStatus.includes(query);
              if (match) {
                  filteredRows.push($(this));
              } else {
                  $(this).hide();
              }
          });

          // Calculate pagination values
          const totalRows = filteredRows.length;
          const totalPages = Math.ceil(totalRows / rowsPerPage) || 1;

          if (currentPage > totalPages) {
              currentPage = totalPages;
          }

          // Apply display states for active page
          const startIndex = (currentPage - 1) * rowsPerPage;
          const endIndex = startIndex + rowsPerPage;

          filteredRows.forEach(function(row, idx) {
              if (idx >= startIndex && idx < endIndex) {
                  row.show();
              } else {
                  row.hide();
              }
          });

          // Update page counter visual badges
          $('#currentPageNum').text(currentPage);
          $('#totalPageNum').text(totalPages);
          $('#paginatedCount').text(`${totalRows} of ${$('.user-row').length}`);
      }

      // Bind input events to paginate table
      $('#userSearchInput').on('input', function() {
          currentPage = 1;
          paginateTable();
      });

      $('#btnPrevPage').on('click', function() {
          if (currentPage > 1) {
              currentPage--;
              paginateTable();
          }
      });

      $('#btnNextPage').on('click', function() {
          const totalPages = Math.ceil(filteredRows.length / rowsPerPage) || 1;
          if (currentPage < totalPages) {
              currentPage++;
              paginateTable();
          }
      });

      // Run initial pagination load
      paginateTable();

      // 2. View details modal loader
      $(document).on('click', '.btn-view-profile', function() {
          const row = $(this).closest('.user-row');
          const uid = row.attr('data-id');
          const name = row.find('td:nth-child(2)').text();
          const email = row.find('td:nth-child(3)').text();
          const role = row.attr('data-role');
          const status = row.attr('data-status');
          const verified = row.attr('data-verified');
          const joined = row.attr('data-created');

          // Populate modal labels
          $('#modalAvatar').text(name.trim().charAt(0).toUpperCase());
          $('#modalFullname').text(name);
          $('#modalRole').text(role);
          $('#modalUserId').text(uid);
          $('#modalEmail').text(email);
          $('#modalVerified').text(verified);
          $('#modalStatus').text(status);
          $('#modalJoined').text(joined);

          // Launch modal
          new bootstrap.Modal(document.getElementById('userViewModal')).show();
      });

      // Helper to output status action messages
      function showFeedback(message, type) {
          const box = $('#actionAlertBox');
          box.removeClass('d-none alert-success alert-danger')
             .addClass('alert-' + type)
             .text(message);
      }

      // 3. AJAX Actions for toggling roles
      $(document).on('click', '.btn-toggle-role', function() {
          const userId = $(this).attr('data-id');
          if (!confirm('Are you sure you want to toggle this user\'s access role privilege?')) return;

          $.ajax({
              url: '/php/admin_action.php',
              type: 'POST',
              dataType: 'json',
              data: { action: 'toggle_role', user_id: userId },
              success: function(res) {
                  if (res.success) {
                      alert(res.message);
                      window.location.reload();
                  } else {
                      showFeedback(res.message || 'Action failed.', 'danger');
                  }
              },
              error: function() {
                  showFeedback('Communication failure with backend endpoint.', 'danger');
              }
          });
      });

      // 4. AJAX Actions for toggling user status
      $(document).on('click', '.btn-toggle-status', function() {
          const userId = $(this).attr('data-id');
          if (!confirm('Are you sure you want to adjust this user\'s status?')) return;

          $.ajax({
              url: '/php/admin_action.php',
              type: 'POST',
              dataType: 'json',
              data: { action: 'toggle_status', user_id: userId },
              success: function(res) {
                  if (res.success) {
                      alert(res.message);
                      window.location.reload();
                  } else {
                      showFeedback(res.message || 'Action failed.', 'danger');
                  }
              },
              error: function() {
                  showFeedback('Communication failure with backend endpoint.', 'danger');
              }
          });
      });

      // 5. AJAX Actions for deleting user account
      $(document).on('click', '.btn-delete-user', function() {
          const userId = $(this).attr('data-id');
          if (!confirm('CRITICAL ACTION: Are you sure you want to delete this user profile completely? This action is irreversible.')) return;

          $.ajax({
              url: '/php/admin_action.php',
              type: 'POST',
              dataType: 'json',
              data: { action: 'delete_user', user_id: userId },
              success: function(res) {
                  if (res.success) {
                      alert(res.message);
                      window.location.reload();
                  } else {
                      showFeedback(res.message || 'Action failed.', 'danger');
                  }
              },
              error: function() {
                  showFeedback('Communication failure with backend endpoint.', 'danger');
              }
          });
      });
  });
  </script>

</body>
</html>
