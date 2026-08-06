<?php
/**
 * dashboard.php
 *
 * Premium, White & Blue themed Admin Control Panel for nodexGosolutions.
 * Fully styled using Tailwind CSS and customized flex container.
 * Features:
 * - Shared Sidebar & Modular Nav
 * - Visual analytics counters and registration metrics
 * - Enhanced User Moderation table with search filtering and client-side pagination
 * - Account detail Modals to view account details instantly
 * - Bottom Right Floating Action Button (FAB) opening the Admin Suite Tools Modal
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
$usersList = $conn->select('users') ?: [];

// Initialize database instances to calculate sub-app counts and system analytics metrics
$siteCmsDb = new Database(__DIR__ . '/../../../databases', 'site_cms');
$urlDb     = new Database(__DIR__ . '/../../../databases', 'url_shortner');
$qrDb      = new Database(__DIR__ . '/../../../databases', 'qrcode');

// Count dynamic platform assets across all unified JSON database tables
$cmsPagesCount  = count($siteCmsDb->select('pages') ?: []);
$shortUrlsCount = count($urlDb->select('links') ?: []);
$qrCodesCount   = count($qrDb->select('qrcodes') ?: []);

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
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Control Panel | nodexGosolutions</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>

  <script>
    // Tailwind Configuration to avoid style overrides
    tailwind.config = {
      corePlugins: {
        preflight: false,
      }
    }
  </script>

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
        font-family: 'Source Sans Pro', sans-serif;
        margin: 0;
        padding: 0;
    }
    .dashboard-layout {
        display: flex;
        min-height: 100vh;
    }
    .main-content {
        flex-grow: 1;
        padding: 30px;
    }
    /* Floating Action Button (FAB) */
    .fab-btn {
      position: fixed;
      bottom: 24px;
      right: 24px;
      z-index: 1040;
      width: 56px;
      height: 56px;
      border-radius: 50%;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .fab-btn:hover {
      transform: scale(1.08);
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.35);
    }
    .app-icon-card {
      transition: all 0.2s ease;
    }
    .app-icon-card:hover {
      transform: translateY(-4px);
      background-color: #f8f9fa;
    }
    /* Analytics Card */
    .analytic-card {
        background: var(--white);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
        box-shadow: 0 4px 12px rgba(0, 114, 255, 0.02);
        transition: transform 0.2s;
    }
    .analytic-card:hover {
        transform: translateY(-2px);
    }
    .analytic-icon {
        font-size: 32px;
        color: var(--primary);
    }
    .analytic-data {
        text-align: right;
    }
    .analytic-label {
        font-size: 11px;
        color: var(--charcoal);
        opacity: 0.7;
        text-transform: uppercase;
        font-weight: 600;
    }
    .analytic-value {
        font-size: 22px;
        font-weight: 700;
        margin-top: 4px;
        color: var(--dark);
    }
  </style>
</head>
<body style="background-color: #f8fafc;">

<div class="dashboard-layout">

  <!-- Include modular Sidebar component -->
  <?php require_once __DIR__ . '/../../modul/sidebar.php'; ?>

  <!-- Content Wrapper -->
  <div class="main-content">

    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="fw-bold text-dark mb-0" style="font-family: 'Orbitron', sans-serif;">System Administration Hub</h2>
        <p class="text-muted small mb-0">Unified operations hub, user credential privilege matrices, and live server resource analytics.</p>
      </div>
      <div>
        <span class="badge bg-primary text-white px-2.5 py-1.5 text-xs rounded-md">
          <i class="fas fa-shield-alt mr-1"></i> Admin Privilege Mode Active
        </span>
      </div>
    </div>

    <!-- Admin Metrics Row -->
    <div class="row mb-4">
      <!-- Active Users -->
      <div class="col-lg-3 col-md-6 col-12">
        <div class="analytic-card">
          <span class="analytic-icon text-blue-600"><i class="fas fa-users"></i></span>
          <div class="analytic-data">
            <div class="analytic-label">Registered Users</div>
            <div class="analytic-value"><?php echo count($usersList); ?></div>
          </div>
        </div>
      </div>

      <!-- Server Uptime -->
      <div class="col-lg-3 col-md-6 col-12">
        <div class="analytic-card">
          <span class="analytic-icon text-emerald-500"><i class="fas fa-server"></i></span>
          <div class="analytic-data">
            <div class="analytic-label">Server Uptime</div>
            <div class="analytic-value">99.99%</div>
          </div>
        </div>
      </div>

      <!-- CMS Pages -->
      <div class="col-lg-3 col-md-6 col-12">
        <div class="analytic-card">
          <span class="analytic-icon text-amber-500"><i class="fas fa-code"></i></span>
          <div class="analytic-data">
            <div class="analytic-label">CMS Endpoints</div>
            <div class="analytic-value"><?php echo $cmsPagesCount; ?></div>
          </div>
        </div>
      </div>

      <!-- Deployed Tools -->
      <div class="col-lg-3 col-md-6 col-12">
        <div class="analytic-card">
          <span class="analytic-icon text-rose-500"><i class="fas fa-rocket"></i></span>
          <div class="analytic-data">
            <div class="analytic-label">Deployed Assets</div>
            <div class="analytic-value"><?php echo $deploymentsCount; ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- USER DIRECTORY MANAGEMENT CARD -->
    <div class="row mt-4">
      <div class="col-12">
        <div class="card border-0 shadow-sm rounded-lg" style="border: 1px solid rgba(0, 114, 255, 0.08);">
          <div class="card-header bg-white border-b border-gray-100 flex justify-between items-center py-3">
            <h3 class="text-base font-bold text-gray-800 m-0"><i class="fas fa-users-gear text-blue-600 mr-2"></i> User Directory</h3>

            <!-- Live Table Filters and Search Area -->
            <div class="flex items-center gap-3">
              <input type="text" id="userSearchInput" class="form-control form-control-sm rounded-pill px-3 py-1 border-gray-200" placeholder="Search by name, email..." style="width: 220px;" />
              <span class="badge bg-blue-100 text-blue-800 text-xs px-2.5 py-1">Realtime Feed</span>
            </div>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover mb-0 text-sm" id="usersTable">
                <thead class="bg-gray-50 text-gray-600 font-semibold">
                  <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email Address</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Verification</th>
                    <th class="text-end px-4">Actions</th>
                  </tr>
                </thead>
                <tbody class="text-gray-700" id="usersTableBody">
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
                      <td class="font-semibold"><?php echo $fullname; ?></td>
                      <td><?php echo $email; ?></td>
                      <td><span class="badge <?php echo $role === 'ADMIN' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'; ?> rounded-md px-2 py-1"><?php echo $role; ?></span></td>
                      <td>
                        <span class="badge <?php echo $statusVal === 'SUSPENDED' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'; ?> rounded-md px-2 py-1">
                          <?php echo $statusVal; ?>
                        </span>
                      </td>
                      <td>
                        <?php if ($isVerified === 1): ?>
                          <span class="text-green-600 font-bold text-xs"><i class="fa-solid fa-circle-check mr-1"></i>Verified</span>
                        <?php else: ?>
                          <span class="text-amber-500 font-bold text-xs"><i class="fa-solid fa-circle-question mr-1"></i>Unverified</span>
                        <?php endif; ?>
                      </td>
                      <td class="text-end px-4">
                        <!-- Action view details trigger button -->
                        <button class="btn btn-xs btn-outline-primary btn-view-profile rounded-md" data-id="<?php echo $userId; ?>"><i class="fa-solid fa-eye mr-1"></i>View</button>

                        <?php if ($userId !== (int)($_SESSION['user_id'] ?? 0)): ?>
                          <button class="btn btn-xs btn-outline-info btn-toggle-role rounded-md" data-id="<?php echo $userId; ?>">Role</button>
                          <button class="btn btn-xs btn-outline-warning btn-toggle-status rounded-md" data-id="<?php echo $userId; ?>">Status</button>
                          <button class="btn btn-xs btn-outline-danger btn-delete-user rounded-md" data-id="<?php echo $userId; ?>"><i class="fa-solid fa-trash-can"></i></button>
                        <?php else: ?>
                          <span class="text-slate-400 text-xs italic">LoggedIn</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Table Pagination Controls -->
          <div class="card-footer bg-white border-t border-gray-100 flex justify-between items-center py-3">
            <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="btnPrevPage">Previous</button>
            <span class="text-xs text-gray-500">Page <span id="currentPageNum">1</span> of <span id="totalPageNum">1</span></span>
            <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="btnNextPage">Next</button>
          </div>

        </div>
      </div>
    </div>

  </div>

  <!-- Bottom Right Floating Action Button (Dots Icon) -->
  <button type="button" class="btn btn-danger fab-btn flex items-center justify-center text-xl text-white" data-bs-toggle="modal" data-bs-target="#adminAppsModal" title="Admin & Suite Apps" style="background-color: #ef4444; border: none;">
    <i class="fas fa-ellipsis-v"></i>
  </button>

  <!-- Apps Launcher Modal for Admins -->
  <div class="modal fade app-launcher-modal" id="adminAppsModal" tabindex="-1" aria-labelledby="adminAppsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content rounded-xl border-0 shadow-2xl">
        <div class="modal-header border-b border-gray-100 pb-3">
          <h5 class="modal-title font-bold text-gray-800 flex items-center" id="adminAppsModalLabel">
            <i class="fas fa-th text-red-600 mr-2"></i> Admin & Suite Tools
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="grid grid-cols-3 gap-3">

            <!-- Admin Tool 1 -->
            <a href="/admin/dashboard" class="app-icon-card flex flex-col items-center p-3 rounded-lg border border-gray-100 text-decoration-none">
              <div class="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center mb-2 text-xl">
                <i class="fas fa-user-shield"></i>
              </div>
              <span class="text-xs font-semibold text-gray-700 text-center">User Roles</span>
            </a>

            <!-- Admin Tool 2 -->
            <a href="/cms/admin" class="app-icon-card flex flex-col items-center p-3 rounded-lg border border-gray-100 text-decoration-none">
              <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center mb-2 text-xl">
                <i class="fas fa-code"></i>
              </div>
              <span class="text-xs font-semibold text-gray-700 text-center">CMS Builder</span>
            </a>

            <!-- Admin Tool 3 -->
            <a href="/profile" class="app-icon-card flex flex-col items-center p-3 rounded-lg border border-gray-100 text-decoration-none">
              <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mb-2 text-xl">
                <i class="fas fa-user-cog"></i>
              </div>
              <span class="text-xs font-semibold text-gray-700 text-center">My Profile</span>
            </a>

          </div>
        </div>
        <div class="modal-footer border-t border-gray-100 bg-gray-50 rounded-b-xl py-2">
          <span class="text-xs text-gray-500 w-full text-center">Privileged Mode Active</span>
        </div>
      </div>
    </div>
  </div>

  <!-- DETAILS ACCOUNT VIEW MODAL -->
  <div class="modal fade" id="userViewModal" tabindex="-1" aria-labelledby="userViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-xl border-0 shadow-2xl">
        <div class="modal-header border-b border-gray-100 bg-blue-600 text-white rounded-t-xl py-3">
          <h5 class="modal-title font-bold flex items-center" id="userViewModalLabel"><i class="fa-solid fa-id-card mr-2"></i>Account Details View</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="text-center mb-4">
            <div class="rounded-circle bg-blue-100 text-blue-600 d-flex align-items-center justify-content-center font-bold text-2xl mx-auto mb-3" style="width: 70px; height: 70px;" id="modalAvatar">
              C
            </div>
            <h4 class="font-bold text-gray-800 mb-0" id="modalFullname">John Doe</h4>
            <span class="badge bg-blue-100 text-blue-800 border border-blue-200 px-3 py-1 rounded-pill small mt-1" id="modalRole">TENANT</span>
          </div>
          <table class="table table-borderless text-sm mb-0">
            <tr>
              <td class="text-gray-400 font-bold" style="width: 130px;">Account ID:</td>
              <td id="modalUserId">12</td>
            </tr>
            <tr>
              <td class="text-gray-400 font-bold">Email Address:</td>
              <td id="modalEmail">user@domain.com</td>
            </tr>
            <tr>
              <td class="text-gray-400 font-bold">Verification:</td>
              <td id="modalVerified">Verified</td>
            </tr>
            <tr>
              <td class="text-gray-400 font-bold">Current Status:</td>
              <td id="modalStatus">ACTIVE</td>
            </tr>
            <tr>
              <td class="text-gray-400 font-bold">Joined On:</td>
              <td id="modalJoined">2026-08-03 18:00:00</td>
            </tr>
          </table>
        </div>
        <div class="modal-footer border-t border-gray-100 bg-gray-50 rounded-b-xl py-2">
          <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

</div>

<!-- Required Scripts: jQuery, Bootstrap 5 -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Search Filters and Pagination Scripts -->
<script>
$(document).ready(function() {
    const rowsPerPage = 5;
    let currentPage = 1;
    let filteredRows = [];

    function paginateTable() {
        const query = $('#userSearchInput').val().trim().toLowerCase();

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

        const totalRows = filteredRows.length;
        const totalPages = Math.ceil(totalRows / rowsPerPage) || 1;

        if (currentPage > totalPages) currentPage = totalPages;

        const startIndex = (currentPage - 1) * rowsPerPage;
        const endIndex = startIndex + rowsPerPage;

        filteredRows.forEach(function(row, idx) {
            if (idx >= startIndex && idx < endIndex) {
                row.show();
            } else {
                row.hide();
            }
        });

        $('#currentPageNum').text(currentPage);
        $('#totalPageNum').text(totalPages);
    }

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

    paginateTable();

    // View Details Modal
    $(document).on('click', '.btn-view-profile', function() {
        const row = $(this).closest('.user-row');
        const uid = row.attr('data-id');
        const name = row.find('td:nth-child(2)').text();
        const email = row.find('td:nth-child(3)').text();
        const role = row.attr('data-role');
        const status = row.attr('data-status');
        const verified = row.attr('data-verified');
        const joined = row.attr('data-created');

        $('#modalAvatar').text(name.trim().charAt(0).toUpperCase());
        $('#modalFullname').text(name);
        $('#modalRole').text(role);
        $('#modalUserId').text(uid);
        $('#modalEmail').text(email);
        $('#modalVerified').text(verified);
        $('#modalStatus').text(status);
        $('#modalJoined').text(joined);

        const userModal = new bootstrap.Modal(document.getElementById('userViewModal'));
        userModal.show();
    });

    // AJAX Action Handlers
    $(document).on('click', '.btn-toggle-role', function() {
        const userId = $(this).attr('data-id');
        if (!confirm('Are you sure you want to toggle this user\'s access role privilege?')) return;

        $.ajax({
            url: '/php/admin_action.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'toggle_role', user_id: userId },
            success: function(res) {
                alert(res.message);
                window.location.reload();
            }
        });
    });

    $(document).on('click', '.btn-toggle-status', function() {
        const userId = $(this).attr('data-id');
        if (!confirm('Are you sure you want to adjust this user\'s status?')) return;

        $.ajax({
            url: '/php/admin_action.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'toggle_status', user_id: userId },
            success: function(res) {
                alert(res.message);
                window.location.reload();
            }
        });
    });

    $(document).on('click', '.btn-delete-user', function() {
        const userId = $(this).attr('data-id');
        if (!confirm('Are you sure you want to delete this user profile?')) return;

        $.ajax({
            url: '/php/admin_action.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'delete_user', user_id: userId },
            success: function(res) {
                alert(res.message);
                window.location.reload();
            }
        });
    });
});
</script>
</body>
</html>
