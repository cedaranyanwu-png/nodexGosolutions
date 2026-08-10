<?php
/**
 * dashboard.php
 *
 * Premium, White & Blue themed Admin Control Panel for nodexGosolutions.
 * Fully styled using Tailwind CSS and customized flex container layout.
 * Features:
 * - Shared Sidebar & Modular Navigation.
 * - Dynamic system metrics (Total Users, Active Users, Users on Free Trial, Expired Trials, Active Subscribers, Suspended Users, Total Websites, Monthly Revenue).
 * - Real-time auto-evaluation of all user subscription states.
 * - Enhanced User Moderation Directory Table showing User, Email, Registration, Trial Start, Trial End, Plan, and Subscription Status.
 * - Real-time client-side interactive Search Filtering and Pagination.
 * - Dynamic details modal showing comprehensive user profile and subscription metadata.
 * - All code contains line-by-line comments for optimal readability and scale.
 */

// Enable strict typing for highest quality and runtime reliability
declare(strict_types=1);

// Require our system database connection and security helper layers
require_once __DIR__ . '/../../../php/db.php';

// Instantiate secure session configurations
secureSession();

// Access Control: Ensure only system administrators can access this privileged control panel
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Redirect standard tenant users or guests to login hub
    header('Location: /login');
    // Halt execution
    exit;
}

// Fetch list of registered users dynamically from system JSON tables database
$usersList = $conn->select('users') ?: [];

// Initialize websites table in the system database if not already created
$conn->createTable('websites');

// Fetch total websites across all tenants in the system
$totalWebsitesCount = count($conn->select('websites') ?: []);

// Initialize analytics metrics variables
$totalUsersCount       = 0;
$activeUsersCount      = 0;
$usersOnFreeTrialCount = 0;
$expiredTrialsCount    = 0;
$activeSubscribersCount= 0;
$suspendedUsersCount   = 0;
$monthlyRevenueSum     = 0;

// Loop through each user to check status transitions and aggregate metrics dynamically
foreach ($usersList as &$u) {
    // Skip administrator accounts from tenant analytics
    if (($u['role'] ?? 'tenant') === 'admin') {
        continue;
    }

    // Increment overall tenant user count
    $totalUsersCount++;

    // Force server-side subscription check on every user to update database states dynamically
    $resolvedStatus = checkAndUpdateSubscription($u, $conn);

    // Increment corresponding category counters based on resolved real-time status
    if ($resolvedStatus === 'trial') {
        $usersOnFreeTrialCount++;
    } elseif ($resolvedStatus === 'active') {
        $activeSubscribersCount++;
        // Calculate monthly revenue summation based on active subscriber plan selection
        $planName = $u['subscription_plan'] ?? '';
        if ($planName === 'Starter Space') {
            $monthlyRevenueSum += 5;
        } elseif ($planName === 'Growth Plan') {
            $monthlyRevenueSum += 15;
        } elseif ($planName === 'Enterprise Space') {
            $monthlyRevenueSum += 49;
        }
    } elseif ($resolvedStatus === 'expired') {
        $expiredTrialsCount++;
    } elseif ($resolvedStatus === 'suspended') {
        $suspendedUsersCount++;
    }

    // Check general account status (active vs suspended)
    if (strtolower((string)($u['status'] ?? '')) === 'active') {
        $activeUsersCount++;
    }
}
unset($u); // Release reference to avoid side-effects

// Initialize database instances to calculate sub-app counts and system analytics metrics
$siteCmsDb = new Database(__DIR__ . '/../../../databases', 'site_cms');
$urlDb     = new Database(__DIR__ . '/../../../databases', 'url_shortner');
$qrDb      = new Database(__DIR__ . '/../../../databases', 'qrcode');

// Count dynamic platform assets across all unified JSON database tables
$cmsPagesCount  = count($siteCmsDb->select('pages') ?: []);
$shortUrlsCount = count($urlDb->select('links') ?: []);
$qrCodesCount   = count($qrDb->select('qrcodes') ?: []);
$deploymentsCount = $cmsPagesCount + $shortUrlsCount + $qrCodesCount;
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
        padding: 16px;
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
        font-size: 28px;
        color: var(--primary);
    }
    .analytic-data {
        text-align: right;
    }
    .analytic-label {
        font-size: 10px;
        color: var(--charcoal);
        opacity: 0.7;
        text-transform: uppercase;
        font-weight: 600;
    }
    .analytic-value {
        font-size: 20px;
        font-weight: 700;
        margin-top: 2px;
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

    <!-- ADMIN SYSTEM STATISTICS GRID -->
    <div class="row mb-4">
      <!-- Total Users -->
      <div class="col-lg-3 col-md-6 col-12">
        <div class="analytic-card">
          <span class="analytic-icon text-blue-600"><i class="fas fa-users"></i></span>
          <div class="analytic-data">
            <div class="analytic-label">Total Users</div>
            <div class="analytic-value"><?= $totalUsersCount ?></div>
          </div>
        </div>
      </div>

      <!-- Active Users -->
      <div class="col-lg-3 col-md-6 col-12">
        <div class="analytic-card">
          <span class="analytic-icon text-green-500"><i class="fas fa-user-check"></i></span>
          <div class="analytic-data">
            <div class="analytic-label">Active Users</div>
            <div class="analytic-value"><?= $activeUsersCount ?></div>
          </div>
        </div>
      </div>

      <!-- Free Trials -->
      <div class="col-lg-3 col-md-6 col-12">
        <div class="analytic-card">
          <span class="analytic-icon text-indigo-500"><i class="fas fa-hourglass-start"></i></span>
          <div class="analytic-data">
            <div class="analytic-label">Users on Free Trial</div>
            <div class="analytic-value"><?= $usersOnFreeTrialCount ?></div>
          </div>
        </div>
      </div>

      <!-- Expired Trials -->
      <div class="col-lg-3 col-md-6 col-12">
        <div class="analytic-card">
          <span class="analytic-icon text-rose-500"><i class="fas fa-hourglass-end"></i></span>
          <div class="analytic-data">
            <div class="analytic-label">Expired Trials</div>
            <div class="analytic-value"><?= $expiredTrialsCount ?></div>
          </div>
        </div>
      </div>
    </div>

    <div class="row mb-4">
      <!-- Active Subscribers -->
      <div class="col-lg-3 col-md-6 col-12">
        <div class="analytic-card">
          <span class="analytic-icon text-emerald-600"><i class="fas fa-wallet"></i></span>
          <div class="analytic-data">
            <div class="analytic-label">Active Subscribers</div>
            <div class="analytic-value"><?= $activeSubscribersCount ?></div>
          </div>
        </div>
      </div>

      <!-- Suspended Users -->
      <div class="col-lg-3 col-md-6 col-12">
        <div class="analytic-card">
          <span class="analytic-icon text-red-600"><i class="fas fa-user-slash"></i></span>
          <div class="analytic-data">
            <div class="analytic-label">Suspended Users</div>
            <div class="analytic-value"><?= $suspendedUsersCount ?></div>
          </div>
        </div>
      </div>

      <!-- Total Websites -->
      <div class="col-lg-3 col-md-6 col-12">
        <div class="analytic-card">
          <span class="analytic-icon text-amber-500"><i class="fas fa-globe"></i></span>
          <div class="analytic-data">
            <div class="analytic-label">Total Websites</div>
            <div class="analytic-value"><?= $totalWebsitesCount ?></div>
          </div>
        </div>
      </div>

      <!-- Monthly Revenue -->
      <div class="col-lg-3 col-md-6 col-12">
        <div class="analytic-card">
          <span class="analytic-icon text-emerald-500"><i class="fas fa-dollar-sign"></i></span>
          <div class="analytic-data">
            <div class="analytic-label">Monthly Revenue</div>
            <div class="analytic-value">$<?= number_format((float)$monthlyRevenueSum, 2) ?></div>
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
              <input type="text" id="userSearchInput" class="form-control form-control-sm rounded-pill px-3 py-1 border-gray-200" placeholder="Search name, email, plan..." style="width: 240px;" />
              <span class="badge bg-blue-100 text-blue-800 text-xs px-2.5 py-1">Realtime Feed</span>
            </div>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover mb-0 text-xs" id="usersTable">
                <thead class="bg-gray-50 text-gray-600 font-semibold">
                  <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Email Address</th>
                    <th>Registration Date</th>
                    <th>Trial Start</th>
                    <th>Trial End</th>
                    <th>Plan</th>
                    <th>Subscription Status</th>
                    <th class="text-end px-4">Actions</th>
                  </tr>
                </thead>
                <tbody class="text-gray-700" id="usersTableBody">
                  <?php foreach ($usersList as $user):
                    // Isolate user role
                    $role = strtolower(htmlspecialchars($user['role'] ?? 'tenant'));
                    // Skip displaying administrator account inside standard directories table
                    if ($role === 'admin') continue;

                    $userId = (int)($user['id'] ?? 0);
                    $fullname = htmlspecialchars($user['fullname'] ?? $user['full_name'] ?? '');
                    $email = htmlspecialchars($user['email'] ?? '');
                    $createdAt = htmlspecialchars($user['created_at'] ?? '2026-08-03 18:00:00');

                    // Safe defaults/migrations on load
                    $trialStart = isset($user['trial_start']) ? date('M d, Y', strtotime($user['trial_start'])) : '-';
                    $trialEnd = isset($user['trial_end']) ? date('M d, Y', strtotime($user['trial_end'])) : '-';
                    $plan = htmlspecialchars($user['subscription_plan'] ?? 'Starter Space');

                    // Compute dynamic status matching
                    $subStatus = checkAndUpdateSubscription($user, $conn);
                  ?>
                    <tr class="user-row"
                        data-id="<?php echo $userId; ?>"
                        data-name="<?php echo strtolower($fullname); ?>"
                        data-email="<?php echo strtolower($email); ?>"
                        data-plan="<?php echo strtolower($plan); ?>"
                        data-sub-status="<?php echo strtolower($subStatus); ?>"
                        data-trial-start="<?php echo $trialStart; ?>"
                        data-trial-end="<?php echo $trialEnd; ?>"
                        data-created="<?php echo $createdAt; ?>">
                      <td><?php echo $userId; ?></td>
                      <td class="font-semibold"><?php echo $fullname; ?></td>
                      <td><?php echo $email; ?></td>
                      <td><?php echo date('M d, Y', strtotime($createdAt)); ?></td>
                      <td><?php echo $trialStart; ?></td>
                      <td><?php echo $trialEnd; ?></td>
                      <td><span class="badge bg-blue-50 text-blue-800 border border-blue-100 rounded-md px-2 py-1"><?= $plan ?></span></td>
                      <td>
                        <?php if ($subStatus === 'active'): ?>
                          <span class="badge bg-green-100 text-green-800 rounded-md px-2.5 py-1 font-bold">● Active</span>
                        <?php elseif ($subStatus === 'trial'): ?>
                          <span class="badge bg-blue-100 text-blue-800 rounded-md px-2.5 py-1 font-bold">● Trial</span>
                        <?php elseif ($subStatus === 'suspended'): ?>
                          <span class="badge bg-red-100 text-red-800 rounded-md px-2.5 py-1 font-bold">● Suspended</span>
                        <?php else: ?>
                          <span class="badge bg-amber-100 text-amber-800 rounded-md px-2.5 py-1 font-bold">● Expired</span>
                        <?php endif; ?>
                      </td>
                      <td class="text-end px-4">
                        <!-- Action view details trigger button -->
                        <button class="btn btn-xs btn-outline-primary btn-view-profile rounded-md" data-id="<?php echo $userId; ?>"><i class="fa-solid fa-eye mr-1"></i>View</button>

                        <?php if ($userId !== (int)($_SESSION['user_id'] ?? 0)): ?>
                          <button class="btn btn-xs btn-outline-warning btn-toggle-status rounded-md" data-id="<?php echo $userId; ?>">Suspend/Unsuspend</button>
                          <button class="btn btn-xs btn-outline-danger btn-delete-user rounded-md" data-id="<?php echo $userId; ?>"><i class="fa-solid fa-trash-can"></i></button>
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
          <table class="table table-borderless text-xs mb-0">
            <tr>
              <td class="text-gray-400 font-bold" style="width: 150px;">Account ID:</td>
              <td id="modalUserId">12</td>
            </tr>
            <tr>
              <td class="text-gray-400 font-bold">Email Address:</td>
              <td id="modalEmail">user@domain.com</td>
            </tr>
            <tr>
              <td class="text-gray-400 font-bold">Registration Date:</td>
              <td id="modalJoined">2026-08-03 18:00:00</td>
            </tr>
            <tr>
              <td class="text-gray-400 font-bold">Trial Start:</td>
              <td id="modalTrialStart">-</td>
            </tr>
            <tr>
              <td class="text-gray-400 font-bold">Trial End:</td>
              <td id="modalTrialEnd">-</td>
            </tr>
            <tr>
              <td class="text-gray-400 font-bold">Selected Plan:</td>
              <td id="modalPlan">-</td>
            </tr>
            <tr>
              <td class="text-gray-400 font-bold">Subscription Status:</td>
              <td id="modalSubStatus">Active</td>
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
            const rPlan = $(this).attr('data-plan').toLowerCase();
            const rSubStatus = $(this).attr('data-sub-status').toLowerCase();

            const match = rName.includes(query) || rEmail.includes(query) || rPlan.includes(query) || rSubStatus.includes(query);
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
        const joined = row.attr('data-created');
        const trialStart = row.attr('data-trial-start');
        const trialEnd = row.attr('data-trial-end');
        const plan = row.attr('data-plan').toUpperCase();
        const subStatus = row.find('td:nth-child(8)').text().trim();

        $('#modalAvatar').text(name.trim().charAt(0).toUpperCase());
        $('#modalFullname').text(name);
        $('#modalRole').text('TENANT');
        $('#modalUserId').text(uid);
        $('#modalEmail').text(email);
        $('#modalJoined').text(joined);
        $('#modalTrialStart').text(trialStart);
        $('#modalTrialEnd').text(trialEnd);
        $('#modalPlan').text(plan);
        $('#modalSubStatus').text(subStatus);

        const userModal = new bootstrap.Modal(document.getElementById('userViewModal'));
        userModal.show();
    });

    // Toggle Suspend/Unsuspend status AJAX action handler
    $(document).on('click', '.btn-toggle-status', function() {
        const userId = $(this).attr('data-id');
        if (!confirm('Are you sure you want to adjust this user\'s suspension state?')) return;

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

    // Delete standard tenant account AJAX action handler
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
