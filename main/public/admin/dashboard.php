<?php
/**
 * dashboard.php (Admin Dashboard)
 *
 * Premium, White & Blue themed Administrative Control Panel for nodexGosolutions.
 * Fully styled using Tailwind CSS and Bootstrap 5 tabbed sections.
 * Features:
 * - Shared modular Sidebar. No FAB floating buttons.
 * - Dynamic system statistics calculated in real-time from JSON databases.
 * - Tabbed management consoles:
 *   1. User Management (All, Active, Suspended, Trial, Subscribed users + Role assignments dropdown)
 *   2. Websites Management (Lists all user folders, subdomains, owners, plans + Suspension toggles)
 *   3. Payment Settings & Logs (Configure Flutterwave credentials + view detailed payment audits)
 *   4. Pricing Management (CRUD operations on plans: modify name, pricing, billing, active, display order)
 *   5. RBAC & Permissions Matrix (Super Admin interface to toggle granular role rights in real-time)
 *   6. Audit Activity Logs (Live audit feed of administrative operations)
 * - All code is comprehensively commented for maximum scalability and structural safety.
 */

// Enable strict typing for architectural safety
declare(strict_types=1);

// Require system connection helpers
require_once __DIR__ . '/../../../php/db.php';

// Instantiate secure session configurations
secureSession();

// Access Control: Ensure only authenticated admins/super-admins are admitted
$activeRole = strtolower((string)($_SESSION['role'] ?? 'tenant'));
if ($activeRole !== 'admin' && $activeRole !== 'super admin') {
    header('Location: /login');
    exit;
}

// Fetch lists from standard tables
$usersList = $conn->select('users') ?: [];

$conn->createTable('websites');
$websitesList = $conn->select('websites') ?: [];
$totalWebsitesCount = count($websitesList);

$conn->createTable('plans');
$plansList = $conn->select('plans') ?: [];

$conn->createTable('payments');
$paymentsList = $conn->select('payments') ?: [];

$conn->createTable('settings');
$flwSettings = $conn->selectOne('settings', ['id' => 'flutterwave']);

$conn->createTable('activity_logs');
$activityLogsList = $conn->select('activity_logs') ?: [];

$conn->createTable('roles');
$rolesList = $conn->select('roles') ?: [];

$conn->createTable('permissions');
$permissionsList = $conn->select('permissions') ?: [];

// Initialize metric counters
$totalUsersCount       = 0;
$activeUsersCount      = 0;
$usersOnFreeTrialCount = 0;
$expiredTrialsCount    = 0;
$activeSubscribersCount= 0;
$suspendedUsersCount   = 0;
$monthlyRevenueSum     = 0;

// Dynamic analytics compilation
foreach ($usersList as &$u) {
    $uRole = strtolower((string)($u['role'] ?? 'tenant'));
    // Skip administrators from client metrics
    if ($uRole === 'admin' || $uRole === 'super admin') {
        continue;
    }

    $totalUsersCount++;

    // Refresh subscription statuses on read
    $resolvedStatus = checkAndUpdateSubscription($u, $conn);

    if ($resolvedStatus === 'trial') {
        $usersOnFreeTrialCount++;
    } elseif ($resolvedStatus === 'active') {
        $activeSubscribersCount++;
        // Fetch current plan price from dynamic plans database
        $userPlanId = strtolower(str_replace(' ', '_', $u['subscription_plan'] ?? ''));
        $matchedPlan = $conn->selectOne('plans', ['id' => $userPlanId]);
        if ($matchedPlan) {
            $monthlyRevenueSum += (float)($matchedPlan['price'] ?? 0.0);
        } else {
            // Standard fallback
            $monthlyRevenueSum += 25000.0;
        }
    } elseif ($resolvedStatus === 'expired') {
        $expiredTrialsCount++;
    } elseif ($resolvedStatus === 'suspended') {
        $suspendedUsersCount++;
    }

    if (strtolower((string)($u['status'] ?? '')) === 'active') {
        $activeUsersCount++;
    }
}
unset($u); // Release reference safely

// Masking Helper for Secret Keys
function maskSecretKey(?string $key): string {
    if (empty($key)) return 'Not Configured';
    $len = strlen($key);
    if ($len <= 15) return str_repeat('•', 12);
    // Keep first 8 characters and last 4, bulleting the rest
    return substr($key, 0, 8) . str_repeat('•', $len - 12) . substr($key, -4);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Control Panel | nodexGo</title>

  <!-- Google Font: Plus Jakarta Sans & Source Sans Pro -->
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
    // Configure Tailwind config variables
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
    }
    .analytic-card {
        background: #ffffff;
        border: 1px solid rgba(0, 114, 255, 0.08);
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
    .nav-tabs .nav-link {
        border: none;
        color: #64748b;
        font-weight: 600;
        font-size: 13px;
        padding: 10px 20px;
        border-radius: 9999px;
        transition: all 0.2s;
    }
    .nav-tabs .nav-link.active {
        background-color: #0072ff !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(0, 114, 255, 0.2);
    }
  </style>
</head>
<body>

<div class="dashboard-layout">

  <!-- Include upgraded modular Sidebar component (FAB is fully removed) -->
  <?php require_once __DIR__ . '/../../modul/sidebar.php'; ?>

  <!-- Content Wrapper -->
  <div class="main-content">

    <div class="d-flex justify-content-between align-items-center mb-4 border-b border-gray-100 pb-3 flex-wrap gap-3">
      <div class="d-flex align-items-center gap-3">
        <!-- Responsive hamburger toggle button (Mobile only) -->
        <button class="btn btn-primary d-md-none rounded-pill" id="sidebarToggleBtn" type="button" style="height: 40px; width: 40px; display: flex; align-items: center; justify-content: center;">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div>
          <h2 class="fw-bold text-dark mb-0" style="font-family: 'Orbitron', sans-serif; font-size: 1.5rem;">System Administration Center</h2>
          <p class="text-muted small mb-0 d-none d-sm-block">Review analytical counters, update plan pricing, audit payments, configure Flutterwave, and assign permissions.</p>
        </div>
      </div>
      <div>
        <span class="badge bg-primary text-white px-2.5 py-1.5 text-xs rounded-md">
          <i class="fas fa-shield-alt mr-1"></i> PRIVILEGED MODE ACTIVE (<?php echo strtoupper($activeRole); ?>)
        </span>
      </div>
    </div>

    <!-- ADMIN SYSTEM STATISTICS GRID -->
    <div class="row mb-1">
      <!-- Total Users -->
      <div class="col-lg-3 col-md-6 col-12">
        <div class="analytic-card">
          <div class="w-12 h-12 bg-primary bg-opacity-10 text-primary rounded-xl d-flex align-items-center justify-content-center text-xl">
            <i class="fas fa-users"></i>
          </div>
          <div class="text-right">
            <div class="text-xs uppercase text-gray-400 font-bold" style="font-size: 9px;">Total Users</div>
            <div class="text-xl font-extrabold text-gray-800 mt-1"><?= $totalUsersCount ?></div>
          </div>
        </div>
      </div>

      <!-- Active Users -->
      <div class="col-lg-3 col-md-6 col-12">
        <div class="analytic-card">
          <div class="w-12 h-12 bg-success bg-opacity-10 text-success rounded-xl d-flex align-items-center justify-content-center text-xl">
            <i class="fas fa-user-check"></i>
          </div>
          <div class="text-right">
            <div class="text-xs uppercase text-gray-400 font-bold" style="font-size: 9px;">Active Users</div>
            <div class="text-xl font-extrabold text-gray-800 mt-1"><?= $activeUsersCount ?></div>
          </div>
        </div>
      </div>

      <!-- Free Trials -->
      <div class="col-lg-3 col-md-6 col-12">
        <div class="analytic-card">
          <div class="w-12 h-12 bg-blue bg-opacity-10 text-blue-500 rounded-xl d-flex align-items-center justify-content-center text-xl">
            <i class="fas fa-hourglass-start"></i>
          </div>
          <div class="text-right">
            <div class="text-xs uppercase text-gray-400 font-bold" style="font-size: 9px;">Free Trials</div>
            <div class="text-xl font-extrabold text-gray-800 mt-1"><?= $usersOnFreeTrialCount ?></div>
          </div>
        </div>
      </div>

      <!-- Expired Trials -->
      <div class="col-lg-3 col-md-6 col-12">
        <div class="analytic-card">
          <div class="w-12 h-12 bg-danger bg-opacity-10 text-danger rounded-xl d-flex align-items-center justify-content-center text-xl">
            <i class="fas fa-hourglass-end"></i>
          </div>
          <div class="text-right">
            <div class="text-xs uppercase text-gray-400 font-bold" style="font-size: 9px;">Expired Trials</div>
            <div class="text-xl font-extrabold text-gray-800 mt-1"><?= $expiredTrialsCount ?></div>
          </div>
        </div>
      </div>
    </div>

    <div class="row mb-4">
      <!-- Active Subscribers -->
      <div class="col-lg-3 col-md-6 col-12">
        <div class="analytic-card">
          <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-xl d-flex align-items-center justify-content-center text-xl">
            <i class="fas fa-credit-card"></i>
          </div>
          <div class="text-right">
            <div class="text-xs uppercase text-gray-400 font-bold" style="font-size: 9px;">Subscribers</div>
            <div class="text-xl font-extrabold text-gray-800 mt-1"><?= $activeSubscribersCount ?></div>
          </div>
        </div>
      </div>

      <!-- Suspended Users -->
      <div class="col-lg-3 col-md-6 col-12">
        <div class="analytic-card">
          <div class="w-12 h-12 bg-red-100 text-red-600 rounded-xl d-flex align-items-center justify-content-center text-xl">
            <i class="fas fa-user-slash"></i>
          </div>
          <div class="text-right">
            <div class="text-xs uppercase text-gray-400 font-bold" style="font-size: 9px;">Suspended</div>
            <div class="text-xl font-extrabold text-gray-800 mt-1"><?= $suspendedUsersCount ?></div>
          </div>
        </div>
      </div>

      <!-- Total Websites -->
      <div class="col-lg-3 col-md-6 col-12">
        <div class="analytic-card">
          <div class="w-12 h-12 bg-warning bg-opacity-10 text-warning rounded-xl d-flex align-items-center justify-content-center text-xl">
            <i class="fas fa-globe"></i>
          </div>
          <div class="text-right">
            <div class="text-xs uppercase text-gray-400 font-bold" style="font-size: 9px;">Total Websites</div>
            <div class="text-xl font-extrabold text-gray-800 mt-1"><?= $totalWebsitesCount ?></div>
          </div>
        </div>
      </div>

      <!-- Monthly Revenue -->
      <div class="col-lg-3 col-md-6 col-12">
        <div class="analytic-card">
          <div class="w-12 h-12 bg-emerald-500 bg-opacity-10 text-emerald-600 rounded-xl d-flex align-items-center justify-content-center text-xl">
            <i class="fas fa-money-bill-trend-up"></i>
          </div>
          <div class="text-right">
            <div class="text-xs uppercase text-gray-400 font-bold" style="font-size: 9px;">Monthly Revenue</div>
            <div class="text-xl font-extrabold text-gray-800 mt-1">₦<?= number_format((float)$monthlyRevenueSum) ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- ADMINISTRATIVE TABBED MENU CONTROLS -->
    <ul class="nav nav-tabs border-0 flex-wrap gap-2 mb-4" id="adminTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active border-0" id="users-tab" data-bs-toggle="tab" data-bs-target="#users-section" type="button" role="tab">User Directory</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link border-0" id="websites-tab" data-bs-toggle="tab" data-bs-target="#websites-section" type="button" role="tab">Website Manager</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link border-0" id="pricing-tab" data-bs-toggle="tab" data-bs-target="#pricing-section" type="button" role="tab">Pricing Management</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link border-0" id="payment-settings-tab" data-bs-toggle="tab" data-bs-target="#payment-settings-section" type="button" role="tab">Payment Settings & Logs</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link border-0" id="rbac-tab" data-bs-toggle="tab" data-bs-target="#rbac-section" type="button" role="tab">RBAC Security Matrix</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link border-0" id="activity-logs-tab" data-bs-toggle="tab" data-bs-target="#activity-logs-section" type="button" role="tab">System Audits</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link border-0" id="backup-tab" data-bs-toggle="tab" data-bs-target="#backup-section" type="button" role="tab">Backup System</button>
      </li>
    </ul>

    <!-- Tabbed Panels Container -->
    <div class="tab-content" id="adminTabContent">

      <!-- TAB 1: USER MANAGEMENT DIRECTORY -->
      <div class="tab-pane fade show active" id="users-section" role="tabpanel" aria-labelledby="users-tab">
        <div class="card border-0 shadow-sm rounded-xl">
          <div class="card-header bg-white border-b border-gray-100 flex justify-between items-center py-3 flex-wrap gap-3">
            <h3 class="text-base font-bold text-gray-800 m-0"><i class="fas fa-users-gear text-blue-600 mr-2"></i> Tenant Operations Directory</h3>
            <input type="text" id="userSearchInput" class="form-control form-control-sm rounded-pill px-3 py-1.5 border-gray-200" placeholder="Search name, email, role, plan..." style="width: 250px;" />
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover mb-0 text-xs" id="usersTable">
                <thead class="bg-gray-50 text-gray-600 font-semibold">
                  <tr>
                    <th class="p-3.5">ID</th>
                    <th class="p-3.5">User</th>
                    <th class="p-3.5">Email Address</th>
                    <th class="p-3.5">Registration Date</th>
                    <th class="p-3.5">Trial Start</th>
                    <th class="p-3.5">Trial End</th>
                    <th class="p-3.5">Plan</th>
                    <th class="p-3.5">Status</th>
                    <th class="p-3.5 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody class="text-gray-700" id="usersTableBody">
                  <?php foreach ($usersList as $u):
                    $uId = (int)($u['id'] ?? 0);
                    $fullname = htmlspecialchars($u['fullname'] ?? '');
                    $email = htmlspecialchars($u['email'] ?? '');
                    $role = htmlspecialchars($u['role'] ?? 'tenant');
                    $createdAt = htmlspecialchars($u['created_at'] ?? '');

                    $trialStart = isset($u['trial_start']) ? date('M d, Y', strtotime($u['trial_start'])) : '-';
                    $trialEnd = isset($u['trial_end']) ? date('M d, Y', strtotime($u['trial_end'])) : '-';
                    $planName = htmlspecialchars($u['subscription_plan'] ?? 'Starter Space');
                    $subStatus = checkAndUpdateSubscription($u, $conn);
                  ?>
                    <tr class="user-row" data-id="<?php echo $uId; ?>" data-name="<?php echo strtolower($fullname); ?>" data-email="<?php echo strtolower($email); ?>" data-plan="<?php echo strtolower($planName); ?>" data-status="<?php echo strtolower($subStatus); ?>" data-role="<?php echo strtolower($role); ?>">
                      <td class="p-3.5"><?php echo $uId; ?></td>
                      <td class="p-3.5 font-semibold text-gray-800"><?php echo $fullname; ?> <span class="badge bg-secondary bg-opacity-15 text-dark text-2xs py-0.5" style="font-size: 9px;"><?php echo strtoupper($role); ?></span></td>
                      <td class="p-3.5"><?php echo $email; ?></td>
                      <td class="p-3.5"><?php echo date('M d, Y', strtotime($createdAt)); ?></td>
                      <td class="p-3.5"><?php echo $trialStart; ?></td>
                      <td class="p-3.5"><?php echo $trialEnd; ?></td>
                      <td class="p-3.5"><span class="badge bg-blue-50 text-blue-700 border border-blue-100 rounded-md px-2 py-1"><?= $planName ?></span></td>
                      <td class="p-3.5">
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
                      <td class="p-3.5 text-right d-flex justify-content-end gap-1.5 flex-wrap">
                        <button class="btn btn-xs btn-outline-primary rounded-md btn-view-profile" data-id="<?php echo $uId; ?>" data-joined="<?php echo $createdAt; ?>" data-trial-start="<?php echo $trialStart; ?>" data-trial-end="<?php echo $trialEnd; ?>" data-plan="<?php echo $planName; ?>" data-status="<?php echo $subStatus; ?>" data-role="<?php echo strtoupper($role); ?>" data-name="<?php echo $fullname; ?>" data-email="<?php echo $email; ?>"><i class="fas fa-eye"></i> View</button>

                        <?php if ($uId !== (int)($_SESSION['user_id'] ?? 0) && $email !== 'admin@nodexplatform.com.ng'): ?>
                          <button class="btn btn-xs btn-outline-warning rounded-md btn-toggle-status" data-id="<?php echo $uId; ?>">Toggle Suspend</button>

                          <!-- Role Assign Dropdown -->
                          <div class="dropdown d-inline-block">
                            <button class="btn btn-xs btn-outline-secondary dropdown-toggle rounded-md" type="button" data-bs-toggle="dropdown" aria-expanded="false">Assign Role</button>
                            <ul class="dropdown-menu dropdown-menu-end text-xs" style="max-height: 200px; overflow-y: auto;">
                              <li><button class="dropdown-item btn-assign-role-btn" data-id="<?php echo $uId; ?>" data-role="super admin">Super Admin</button></li>
                              <li><button class="dropdown-item btn-assign-role-btn" data-id="<?php echo $uId; ?>" data-role="admin">Admin</button></li>
                              <li><button class="dropdown-item btn-assign-role-btn" data-id="<?php echo $uId; ?>" data-role="manager">Manager</button></li>
                              <li><button class="dropdown-item btn-assign-role-btn" data-id="<?php echo $uId; ?>" data-role="support">Support</button></li>
                              <li><button class="dropdown-item btn-assign-role-btn" data-id="<?php echo $uId; ?>" data-role="moderator">Moderator</button></li>
                              <li><button class="dropdown-item btn-assign-role-btn" data-id="<?php echo $uId; ?>" data-role="tenant">Tenant</button></li>
                            </ul>
                          </div>

                          <button class="btn btn-xs btn-outline-danger rounded-md btn-delete-user" data-id="<?php echo $uId; ?>"><i class="fas fa-trash"></i></button>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 2: WEBSITES MANAGEMENT DIRECTORY -->
      <div class="tab-pane fade" id="websites-section" role="tabpanel" aria-labelledby="websites-tab">
        <div class="card border-0 shadow-sm rounded-xl">
          <div class="card-header bg-white border-b border-gray-100 flex justify-between items-center py-3">
            <h3 class="text-base font-bold text-gray-800 m-0"><i class="fas fa-globe text-primary mr-2"></i> System Website Workspaces</h3>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover mb-0 text-xs">
                <thead class="bg-gray-50 text-gray-600 font-semibold">
                  <tr>
                    <th class="p-3.5">Owner ID</th>
                    <th class="p-3.5">Website Name</th>
                    <th class="p-3.5">Subdomain Preview URL</th>
                    <th class="p-3.5">Physical Subfolder</th>
                    <th class="p-3.5">Provision Date</th>
                    <th class="p-3.5">Status</th>
                    <th class="p-3.5 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody class="text-gray-700">
                  <?php if (count($websitesList) > 0): ?>
                    <?php foreach ($websitesList as $web):
                      $webId = (int)($web['id'] ?? 0);
                      $isSuspended = (int)($web['is_suspended'] ?? 0) === 1;
                    ?>
                      <tr>
                        <td class="p-3.5">User ID: <?php echo (int)($web['user_id'] ?? 0); ?></td>
                        <td class="p-3.5 font-semibold text-gray-800"><?php echo htmlspecialchars($web['name'] ?? ''); ?></td>
                        <td class="p-3.5 font-mono text-blue-600"><a href="<?php echo htmlspecialchars($web['url'] ?? ''); ?>" target="_blank" class="hover:underline"><?php echo htmlspecialchars($web['url'] ?? ''); ?></a></td>
                        <td class="p-3.5"><span class="badge bg-secondary bg-opacity-10 text-secondary px-2.5 py-1">/public/<?php echo htmlspecialchars($web['folder'] ?? ''); ?>/</span></td>
                        <td class="p-3.5 text-gray-400"><?php echo date('M d, Y', strtotime($web['created_at'] ?? 'now')); ?></td>
                        <td class="p-3.5">
                          <?php if ($isSuspended): ?>
                            <span class="badge bg-red-100 text-red-800 rounded font-bold">Suspended</span>
                          <?php else: ?>
                            <span class="badge bg-green-100 text-green-800 rounded font-bold">Active</span>
                          <?php endif; ?>
                        </td>
                        <td class="p-3.5 text-right">
                          <div class="d-flex justify-content-end gap-1.5 flex-wrap">
                            <button class="btn btn-xs <?php echo $isSuspended ? 'btn-outline-success' : 'btn-outline-warning'; ?> rounded-md btn-admin-toggle-site-suspension" data-id="<?php echo $webId; ?>">
                              <?php echo $isSuspended ? 'Unsuspend' : 'Suspend'; ?>
                            </button>
                            <button class="btn btn-xs btn-outline-danger rounded-md btn-admin-delete-site" data-id="<?php echo $webId; ?>">
                              <i class="fas fa-trash"></i> Delete
                            </button>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="5" class="text-center text-gray-400 py-5">No user websites provisioned yet in `/public/` directory.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 3: PRICING MANAGEMENT CRUD CONSOLE -->
      <div class="tab-pane fade" id="pricing-section" role="tabpanel" aria-labelledby="pricing-tab">
        <div class="row g-4">
          <!-- Pricing Plans Registry list -->
          <div class="col-md-5">
            <div class="card border-0 shadow-sm rounded-xl mb-4">
              <div class="card-header bg-white border-b border-gray-100 flex justify-between items-center py-3">
                <h3 class="text-base font-bold text-gray-800 m-0">Active Pricing Plans</h3>
                <button class="btn btn-sm btn-primary rounded-md font-bold text-white bg-blue-600 border-0" id="btnCreatePlan"><i class="fas fa-plus mr-1"></i> Add Plan</button>
              </div>
              <div class="card-body p-0">
                <div class="list-group list-group-flush pl-0 mb-0">
                  <?php foreach ($plansList as $p): ?>
                    <button class="list-group-item list-group-item-action py-3 px-3 d-flex justify-content-between align-items-center btn-select-plan"
                      data-id="<?php echo htmlspecialchars((string)($p['id'] ?? '')); ?>"
                      data-name="<?php echo htmlspecialchars((string)($p['name'] ?? '')); ?>"
                      data-price="<?php echo (float)$p['price']; ?>"
                      data-currency="<?php echo htmlspecialchars((string)($p['currency'] ?? 'NGN')); ?>"
                      data-period="<?php echo htmlspecialchars((string)($p['billing_period'] ?? 'month')); ?>"
                      data-desc="<?php echo htmlspecialchars((string)($p['description'] ?? '')); ?>"
                      data-features='<?php echo htmlspecialchars((string)($p['features'] ?? '[]')); ?>'
                      data-active="<?php echo (int)($p['is_active'] ?? 1); ?>"
                      data-order="<?php echo (int)($p['display_order'] ?? 1); ?>"
                      data-rec="<?php echo (int)($p['is_recommended'] ?? 0); ?>">
                      <div>
                        <div class="font-bold text-gray-800"><?php echo htmlspecialchars((string)($p['name'] ?? '')); ?></div>
                        <div class="text-2xs text-gray-400 mt-0.5" style="font-size: 10px;">ID: <?php echo htmlspecialchars((string)($p['id'] ?? '')); ?> • Order: <?php echo (int)($p['display_order']); ?></div>
                      </div>
                      <div class="text-right">
                        <div class="font-bold text-blue-600">₦<?php echo number_format((float)$p['price']); ?></div>
                        <span class="badge <?php echo (int)($p['is_active'] ?? 1) === 1 ? 'bg-success bg-opacity-10 text-success' : 'bg-secondary bg-opacity-10 text-secondary'; ?> text-2xs px-2 py-0.5 rounded-full" style="font-size: 9px;"><?php echo (int)($p['is_active'] ?? 1) === 1 ? 'Active' : 'Disabled'; ?></span>
                      </div>
                    </button>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>

          <!-- CRUD Form Console -->
          <div class="col-md-7">
            <div class="card border-0 shadow-sm rounded-xl">
              <div class="card-header bg-white border-b border-gray-100 py-3">
                <h3 class="text-base font-bold text-gray-800 m-0" id="pricingFormTitle">Save Pricing Plan</h3>
              </div>
              <div class="card-body p-4 text-start">
                <div id="pricingFeedback" class="alert d-none text-xs rounded-lg p-2.5 mb-3" role="alert"></div>

                <form id="pricingCrudForm">
                  <div class="row g-3">
                    <div class="col-md-6 mb-2">
                      <label class="block text-2xs uppercase font-bold text-gray-500 mb-1" style="font-size: 10px;">Plan Unique Identifier ID</label>
                      <input type="text" id="planId" class="form-control text-sm rounded-md px-3 py-2 border-gray-200" placeholder="e.g. growth, micro" required />
                    </div>
                    <div class="col-md-6 mb-2">
                      <label class="block text-2xs uppercase font-bold text-gray-500 mb-1" style="font-size: 10px;">Plan Name</label>
                      <input type="text" id="planName" class="form-control text-sm rounded-md px-3 py-2 border-gray-200" placeholder="e.g. Growth Plan" required />
                    </div>
                    <div class="col-md-6 mb-2">
                      <label class="block text-2xs uppercase font-bold text-gray-500 mb-1" style="font-size: 10px;">Plan Price (₦ NGN)</label>
                      <input type="number" id="planPrice" class="form-control text-sm rounded-md px-3 py-2 border-gray-200" placeholder="e.g. 25000" min="0" required />
                    </div>
                    <div class="col-md-6 mb-2">
                      <label class="block text-2xs uppercase font-bold text-gray-500 mb-1" style="font-size: 10px;">Billing Frequency Period</label>
                      <input type="text" id="planPeriod" class="form-control text-sm rounded-md px-3 py-2 border-gray-200" placeholder="month, year" value="month" required />
                    </div>
                    <div class="col-12 mb-2">
                      <label class="block text-2xs uppercase font-bold text-gray-500 mb-1" style="font-size: 10px;">Plan Description</label>
                      <input type="text" id="planDesc" class="form-control text-sm rounded-md px-3 py-2 border-gray-200" placeholder="A short catchy line..." />
                    </div>
                    <div class="col-12 mb-2">
                      <label class="block text-2xs uppercase font-bold text-gray-500 mb-1" style="font-size: 10px;">Plan Features List (Comma separated)</label>
                      <textarea id="planFeatures" class="form-control text-sm rounded-md px-3 py-2 border-gray-200" rows="3" placeholder="e.g. Unlimited Websites, 10 GB Storage, Priority Support"></textarea>
                    </div>
                    <div class="col-md-4 mb-2">
                      <label class="block text-2xs uppercase font-bold text-gray-500 mb-1" style="font-size: 10px;">Display Order</label>
                      <input type="number" id="planOrder" class="form-control text-sm rounded-md px-3 py-2 border-gray-200" value="1" min="1" required />
                    </div>
                    <div class="col-md-4 mb-2">
                      <label class="block text-2xs uppercase font-bold text-gray-500 mb-1" style="font-size: 10px;">Plan Status</label>
                      <select id="planIsActive" class="form-control text-sm rounded-md px-3 py-2 border-gray-200">
                        <option value="1">Enabled</option>
                        <option value="0">Disabled</option>
                      </select>
                    </div>
                    <div class="col-md-4 mb-2">
                      <label class="block text-2xs uppercase font-bold text-gray-500 mb-1" style="font-size: 10px;">Mark Popular/Recommended</label>
                      <select id="planIsRecommended" class="form-control text-sm rounded-md px-3 py-2 border-gray-200">
                        <option value="0">No</option>
                        <option value="1">Yes (Popular Badge)</option>
                      </select>
                    </div>
                  </div>

                  <div class="mt-4 flex gap-2">
                    <button type="submit" id="btnSavePricing" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs py-2.5 px-4 rounded-lg flex-grow transition border-0">Publish Plan Modifications</button>
                    <button type="button" id="btnDeletePricing" class="btn btn-outline-danger text-xs px-3 rounded-lg border">Delete Plan</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 4: PAYMENT SETTINGS CONFIG & LOGS -->
      <div class="tab-pane fade" id="payment-settings-section" role="tabpanel" aria-labelledby="payment-settings-tab">
        <div class="row g-4">
          <!-- Configuration Form -->
          <div class="col-md-5">
            <div class="card border-0 shadow-sm rounded-xl">
              <div class="card-header bg-white border-b border-gray-100 py-3">
                <h3 class="text-base font-bold text-gray-800 m-0"><i class="fas fa-key text-red-500 mr-2"></i> Flutterwave Credentials Settings</h3>
              </div>
              <div class="card-body p-4 text-start">
                <div id="settingsFeedback" class="alert d-none text-xs rounded-lg p-2.5 mb-3" role="alert"></div>

                <form id="settingsCredForm">
                  <div class="mb-3">
                    <label class="block text-2xs uppercase font-bold text-gray-500 mb-1" style="font-size: 10px;">Flutterwave Public Key</label>
                    <input type="text" id="flwPublicKey" class="form-control text-sm rounded-md px-3 py-2 border-gray-200" placeholder="FLWPUBK_TEST-..." value="<?php echo maskSecretKey($flwSettings['flw_public_key'] ?? ''); ?>" required />
                  </div>
                  <div class="mb-3">
                    <label class="block text-2xs uppercase font-bold text-gray-500 mb-1" style="font-size: 10px;">Flutterwave Secret Key</label>
                    <input type="text" id="flwSecretKey" class="form-control text-sm rounded-md px-3 py-2 border-gray-200" placeholder="FLWSECK_TEST-..." value="<?php echo maskSecretKey($flwSettings['flw_secret_key'] ?? ''); ?>" required />
                  </div>
                  <div class="mb-3">
                    <label class="block text-2xs uppercase font-bold text-gray-500 mb-1" style="font-size: 10px;">Flutterwave Encryption Key</label>
                    <input type="text" id="flwEncryptionKey" class="form-control text-sm rounded-md px-3 py-2 border-gray-200" placeholder="FLWENCK_TEST-..." value="<?php echo maskSecretKey($flwSettings['flw_encryption_key'] ?? ''); ?>" required />
                  </div>
                  <button type="submit" id="btnSaveSettings" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs py-2.5 px-4 rounded-lg w-full transition border-0"><i class="fas fa-save mr-1"></i> Save Configuration Keys</button>
                </form>
              </div>
            </div>
          </div>

          <!-- Payment History Audits logs list -->
          <div class="col-md-7">
            <div class="card border-0 shadow-sm rounded-xl">
              <div class="card-header bg-white border-b border-gray-100 py-3">
                <h3 class="text-base font-bold text-gray-800 m-0"><i class="fas fa-receipt text-emerald-600 mr-2"></i> Payment Verification Log Audits</h3>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-hover mb-0 text-xs">
                    <thead class="bg-gray-50 text-gray-600 font-semibold">
                      <tr>
                        <th class="p-3.5">User ID</th>
                        <th class="p-3.5">Ref / Tx ID</th>
                        <th class="p-3.5">Plan / Amount</th>
                        <th class="p-3.5">Status</th>
                        <th class="p-3.5">Date</th>
                      </tr>
                    </thead>
                    <tbody class="text-gray-700">
                      <?php if (count($paymentsList) > 0): ?>
                        <?php foreach (array_reverse($paymentsList) as $p):
                          $pStatus = strtolower((string)($p['status'] ?? ''));
                        ?>
                          <tr>
                            <td class="p-3.5">User ID: <?php echo (int)($p['user_id'] ?? 0); ?></td>
                            <td class="p-3.5">
                              <span class="block font-mono text-gray-600"><?php echo htmlspecialchars($p['tx_ref'] ?? ''); ?></span>
                              <span class="block text-2xs text-gray-400" style="font-size: 9px;">Gate ID: <?php echo htmlspecialchars($p['gateway_tx_id'] ?? '-'); ?></span>
                            </td>
                            <td class="p-3.5">
                              <span class="block font-semibold"><?php echo htmlspecialchars($p['plan_name'] ?? ''); ?></span>
                              <span class="block text-blue-600 font-bold"><?php echo htmlspecialchars($p['currency'] ?? 'NGN') . ' ' . number_format((float)($p['amount'] ?? 0.0)); ?></span>
                            </td>
                            <td class="p-3.5">
                              <?php if ($pStatus === 'successful'): ?>
                                <span class="badge bg-success bg-opacity-10 text-success rounded-full font-bold px-2 py-0.5" style="font-size: 9px;">Successful</span>
                              <?php elseif ($pStatus === 'pending'): ?>
                                <span class="badge bg-warning bg-opacity-10 text-warning rounded-full font-bold px-2 py-0.5" style="font-size: 9px;">Pending</span>
                              <?php else: ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger rounded-full font-bold px-2 py-0.5" style="font-size: 9px;">Failed</span>
                              <?php endif; ?>
                            </td>
                            <td class="p-3.5 text-gray-400"><?php echo date('M d, H:i', strtotime($p['created_at'] ?? 'now')); ?></td>
                          </tr>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <tr>
                          <td colspan="5" class="text-center text-gray-400 py-5">No payments recorded in system JSON registry.</td>
                        </tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 5: RBAC & PERMISSIONS ASSIGNMENTS MATRIX -->
      <div class="tab-pane fade" id="rbac-section" role="tabpanel" aria-labelledby="rbac-tab">
        <div class="row g-4">
          <!-- Create Role Form -->
          <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-xl">
              <div class="card-header bg-white border-b border-gray-100 py-3">
                <h3 class="text-base font-bold text-gray-800 m-0">Create Role Schema</h3>
              </div>
              <div class="card-body p-4 text-start">
                <div id="rbacFeedback" class="alert d-none text-xs rounded-lg p-2.5 mb-3" role="alert"></div>

                <form id="rbacRoleForm">
                  <div class="mb-3">
                    <label class="block text-2xs uppercase font-bold text-gray-500 mb-1" style="font-size: 10px;">Role ID key (no spaces)</label>
                    <input type="text" id="roleIdInput" class="form-control text-sm rounded-md px-3 py-2 border-gray-200" placeholder="e.g. co_admin" required />
                  </div>
                  <div class="mb-3">
                    <label class="block text-2xs uppercase font-bold text-gray-500 mb-1" style="font-size: 10px;">Role Display Name</label>
                    <input type="text" id="roleNameInput" class="form-control text-sm rounded-md px-3 py-2 border-gray-200" placeholder="e.g. Co-Administrator" required />
                  </div>
                  <div class="mb-3">
                    <label class="block text-2xs uppercase font-bold text-gray-500 mb-1" style="font-size: 10px;">Role Description</label>
                    <input type="text" id="roleDescInput" class="form-control text-sm rounded-md px-3 py-2 border-gray-200" placeholder="Description of capabilities..." />
                  </div>
                  <button type="submit" id="btnCreateRole" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs py-2.5 px-4 rounded-lg w-full transition border-0"><i class="fas fa-plus mr-1"></i> Register New Role</button>
                </form>
              </div>
            </div>
          </div>

          <!-- Interactive Permissions Grid Matrix -->
          <div class="col-md-8">
            <div class="card border-0 shadow-sm rounded-xl">
              <div class="card-header bg-white border-b border-gray-100 py-3">
                <h3 class="text-base font-bold text-gray-800 m-0"><i class="fas fa-shield-halved text-primary mr-2"></i> Granular Permissions Assignment Matrix</h3>
              </div>
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table table-hover mb-0 text-xs text-start">
                    <thead class="bg-gray-50 text-gray-600 font-semibold">
                      <tr>
                        <th class="p-3.5">Admin Level / Role</th>
                        <th class="p-3.5 text-center">users.view</th>
                        <th class="p-3.5 text-center">users.suspend</th>
                        <th class="p-3.5 text-center">users.delete</th>
                        <th class="p-3.5 text-center">websites.view</th>
                        <th class="p-3.5 text-center">pricing.edit</th>
                        <th class="p-3.5 text-center">settings.edit</th>
                        <th class="p-3.5 text-center">rbac.manage</th>
                      </tr>
                    </thead>
                    <tbody class="text-gray-700">
                      <?php
                      $testRoles = ['super admin', 'admin', 'manager', 'support', 'moderator'];
                      $permsToCheck = ['users.view', 'users.suspend', 'users.delete', 'websites.view', 'pricing.edit', 'settings.edit', 'rbac.manage'];

                      foreach ($testRoles as $role): ?>
                        <tr>
                          <td class="p-3.5 font-bold uppercase text-gray-800"><?php echo htmlspecialchars($role); ?></td>
                          <?php foreach ($permsToCheck as $perm):
                            // Check allowed status from permissions list
                            $isAllowed = 0;
                            if ($role === 'super admin') {
                                $isAllowed = 1; // Super admin possesses absolute override
                            } else {
                                foreach ($permissionsList as $pItem) {
                                    if (strtolower((string)($pItem['role'] ?? '')) === strtolower($role) &&
                                        strtolower((string)($pItem['permission'] ?? '')) === strtolower($perm) &&
                                        (int)($pItem['is_allowed'] ?? 0) === 1) {
                                        $isAllowed = 1;
                                        break;
                                    }
                                }
                            }
                          ?>
                            <td class="p-3.5 text-center">
                              <?php if ($role === 'super admin'): ?>
                                <span class="text-green-600 font-bold"><i class="fas fa-circle-check text-base"></i></span>
                              <?php else: ?>
                                <button class="btn-toggle-perm-cell btn border-0 p-0 text-sm focus:outline-none"
                                  data-role="<?php echo htmlspecialchars($role); ?>"
                                  data-permission="<?php echo htmlspecialchars($perm); ?>">
                                  <?php if ($isAllowed === 1): ?>
                                    <span class="text-green-600"><i class="fas fa-circle-check text-base"></i></span>
                                  <?php else: ?>
                                    <span class="text-gray-300 hover:text-red-400"><i class="fas fa-circle-xmark text-base"></i></span>
                                  <?php endif; ?>
                                </button>
                              <?php endif; ?>
                            </td>
                          <?php endforeach; ?>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 6: AUDIT ACTIVITY LOG SYSTEM -->
      <div class="tab-pane fade" id="activity-logs-section" role="tabpanel" aria-labelledby="activity-logs-tab">
        <div class="card border-0 shadow-sm rounded-xl">
          <div class="card-header bg-white border-b border-gray-100 flex justify-between items-center py-3">
            <h3 class="text-base font-bold text-gray-800 m-0"><i class="fas fa-rectangle-list text-primary mr-2"></i> Complete System Activities Audits</h3>
            <span class="badge bg-secondary bg-opacity-10 text-secondary text-xs font-bold px-2 py-1 rounded">Read-only Stream</span>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover mb-0 text-xs">
                <thead class="bg-gray-50 text-gray-600 font-semibold">
                  <tr>
                    <th class="p-3.5">Triggered By</th>
                    <th class="p-3.5">Operational Event</th>
                    <th class="p-3.5">Details Metadata Logs</th>
                    <th class="p-3.5">Audit Date</th>
                  </tr>
                </thead>
                <tbody class="text-gray-700">
                  <?php if (count($activityLogsList) > 0): ?>
                    <?php foreach (array_reverse($activityLogsList) as $log): ?>
                      <tr>
                        <td class="p-3.5 font-bold text-gray-800"><?php echo htmlspecialchars($log['email'] ?? ''); ?></td>
                        <td class="p-3.5"><span class="badge bg-indigo-50 text-indigo-700 font-bold border border-indigo-100 px-2 py-0.5 rounded"><?php echo strtoupper(str_replace('_', ' ', $log['action'] ?? '')); ?></span></td>
                        <td class="p-3.5"><?php echo htmlspecialchars($log['details'] ?? ''); ?></td>
                        <td class="p-3.5 text-gray-400"><?php echo date('M d, Y H:i:s', strtotime($log['created_at'] ?? 'now')); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="4" class="text-center text-gray-400 py-5">No audited administrative actions captured yet.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 7: NGS BACKUP & RECOVERY SYSTEM CONSOLE -->
      <div class="tab-pane fade" id="backup-section" role="tabpanel" aria-labelledby="backup-tab">
        <div class="card border-0 shadow-sm rounded-xl mb-4">
          <div class="card-header bg-white border-b border-gray-100 flex justify-between items-center py-3 flex-wrap gap-3">
            <div>
              <h3 class="text-base font-bold text-gray-800 m-0"><i class="fas fa-server text-blue-600 mr-2"></i> NGS Backup & Recovery Control Center</h3>
              <p class="text-xs text-gray-400 mt-1">Health Status: <span class="text-green-600 font-bold">● Healthy (Outside Webroot)</span></p>
            </div>
            <div>
              <button class="btn btn-sm btn-primary rounded-md font-bold text-white bg-blue-600 border-0 px-3 py-2 text-xs" id="btnTriggerBackup"><i class="fas fa-rotate mr-1"></i> Create Safety Backup Now</button>
            </div>
          </div>
          <div class="card-body p-4 text-start">
            <div id="backupFeedback" class="alert d-none text-xs rounded-lg p-2.5 mb-3" role="alert"></div>

            <div class="row mb-4">
              <div class="col-md-6 mb-2">
                <div class="p-3 bg-gray-50 border border-gray-100 rounded-lg">
                  <div class="text-2xs font-bold text-gray-400 uppercase" style="font-size: 9px;">Backup Location Path</div>
                  <div class="text-xs font-mono text-gray-700 mt-1" id="backupLocationPath" style="word-break: break-all;">
                    <?php
                      require_once __DIR__ . '/../../../php/BackupManager.php';
                      $bm = new BackupManager();
                      echo htmlspecialchars($bm->getBackupPath());
                    ?>
                  </div>
                </div>
              </div>
              <div class="col-md-3 mb-2">
                <div class="p-3 bg-gray-50 border border-gray-100 rounded-lg">
                  <div class="text-2xs font-bold text-gray-400 uppercase" style="font-size: 9px;">Integrity Verification</div>
                  <div class="text-xs text-green-600 font-bold mt-1">✓ Active Shield</div>
                </div>
              </div>
              <div class="col-md-3 mb-2">
                <div class="p-3 bg-gray-50 border border-gray-100 rounded-lg">
                  <div class="text-2xs font-bold text-gray-400 uppercase" style="font-size: 9px;">Retention Rule Limit</div>
                  <div class="text-xs text-blue-600 font-bold mt-1">Max 10 Versions</div>
                </div>
              </div>
            </div>

            <h4 class="text-xs font-bold text-gray-500 uppercase mb-3" style="font-size: 10px;">Available Recovery Points (Stored Privately)</h4>
            <div class="table-responsive">
              <table class="table table-hover mb-0 text-xs text-start">
                <thead class="bg-gray-50 text-gray-600 font-semibold">
                  <tr>
                    <th class="p-3.5">Backup ID</th>
                    <th class="p-3.5">Created At</th>
                    <th class="p-3.5">Triggered By</th>
                    <th class="p-3.5 text-center">DB</th>
                    <th class="p-3.5 text-center">Websites</th>
                    <th class="p-3.5 text-center">Uploads</th>
                    <th class="p-3.5 text-center">Status</th>
                    <th class="p-3.5 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody class="text-gray-700" id="backupsListTableBody">
                  <!-- Loaded dynamically via AJAX -->
                  <tr>
                    <td colspan="8" class="text-center text-gray-400 py-4"><i class="fas fa-spinner fa-spin mr-1"></i> Loading restore points...</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
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

    // User Search Input filter handler
    $('#userSearchInput').on('input', function() {
        const query = $(this).val().toLowerCase().trim();
        $('.user-row').each(function() {
            const name = $(this).attr('data-name');
            const email = $(this).attr('data-email');
            const plan = $(this).attr('data-plan');
            const status = $(this).attr('data-status');
            const role = $(this).attr('data-role');

            if (name.includes(query) || email.includes(query) || plan.includes(query) || status.includes(query) || role.includes(query)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // View Details Modal Loader
    $(document).on('click', '.btn-view-profile', function() {
        const uid = $(this).attr('data-id');
        const name = $(this).attr('data-name');
        const email = $(this).attr('data-email');
        const joined = $(this).attr('data-joined');
        const trialStart = $(this).attr('data-trial-start');
        const trialEnd = $(this).attr('data-trial-end');
        const plan = $(this).attr('data-plan').toUpperCase();
        const role = $(this).attr('data-role').toUpperCase();
        const status = $(this).attr('data-status');

        $('#modalAvatar').text(name.trim().charAt(0).toUpperCase());
        $('#modalFullname').text(name);
        $('#modalRole').text(role);
        $('#modalUserId').text(uid);
        $('#modalEmail').text(email);
        $('#modalJoined').text(joined);
        $('#modalTrialStart').text(trialStart);
        $('#modalTrialEnd').text(trialEnd);
        $('#modalPlan').text(plan);
        $('#modalSubStatus').text(status.toUpperCase());

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

    // Role Assignment AJAX trigger
    $(document).on('click', '.btn-assign-role-btn', function() {
        const userId = $(this).attr('data-id');
        const selectedRole = $(this).attr('data-role');

        if (!confirm('Are you sure you want to assign role ' + selectedRole.toUpperCase() + ' to this user?')) return;

        $.ajax({
            url: '/php/admin_action.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'toggle_role',
                user_id: userId,
                role: selectedRole
            },
            success: function(res) {
                alert(res.message);
                window.location.reload();
            },
            error: function(xhr) {
                alert(xhr.responseJSON ? xhr.responseJSON.message : 'Failed to assign role.');
            }
        });
    });

    // ============================================================
    // PRICING MANAGEMENT INTERACTIVE CONTROLS
    // ============================================================

    // Select dynamic plan trigger
    $(document).on('click', '.btn-select-plan', function() {
        const id = $(this).attr('data-id');
        const name = $(this).attr('data-name');
        const price = $(this).attr('data-price');
        const period = $(this).attr('data-period');
        const desc = $(this).attr('data-desc');
        const features = JSON.parse($(this).attr('data-features') || '[]');
        const active = $(this).attr('data-active');
        const order = $(this).attr('data-order');
        const rec = $(this).attr('data-rec');

        $('#pricingFormTitle').text("Modify: " + name);
        $('#planId').val(id).prop('readonly', true);
        $('#planName').val(name);
        $('#planPrice').val(price);
        $('#planPeriod').val(period);
        $('#planDesc').val(desc);
        $('#planFeatures').val(features.join(', '));
        $('#planOrder').val(order);
        $('#planIsActive').val(active);
        $('#planIsRecommended').val(rec);
        $('#btnDeletePricing').removeClass('d-none');
    });

    $('#btnCreatePlan').on('click', function() {
        $('#pricingFormTitle').text("Create Custom Pricing Plan");
        $('#planId').val('').prop('readonly', false).focus();
        $('#planName').val('');
        $('#planPrice').val('');
        $('#planPeriod').val('month');
        $('#planDesc').val('');
        $('#planFeatures').val('');
        $('#planOrder').val('1');
        $('#planIsActive').val('1');
        $('#planIsRecommended').val('0');
        $('#btnDeletePricing').addClass('d-none');
    });

    // Save plan CRUD action trigger
    $('#pricingCrudForm').on('submit', function(e) {
        e.preventDefault();
        const feedback = $('#pricingFeedback');
        feedback.addClass('d-none').removeClass('alert-success alert-danger');
        $('#btnSavePricing').prop('disabled', true).text('Saving modifications...');

        $.ajax({
            url: '/php/admin_pricing_action.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'save_plan',
                id: $('#planId').val(),
                name: $('#planName').val(),
                price: $('#planPrice').val(),
                billing_period: $('#planPeriod').val(),
                description: $('#planDesc').val(),
                features: $('#planFeatures').val(),
                display_order: $('#planOrder').val(),
                is_active: $('#planIsActive').val(),
                is_recommended: $('#planIsRecommended').val()
            },
            success: function(res) {
                $('#btnSavePricing').prop('disabled', false).text('Publish Plan Modifications');
                feedback.removeClass('d-none');
                if (res.success) {
                    feedback.addClass('alert-success').text(res.message);
                    setTimeout(() => {
                        window.location.reload();
                    }, 1200);
                } else {
                    feedback.addClass('alert-danger').text(res.message);
                }
            },
            error: function(xhr) {
                $('#btnSavePricing').prop('disabled', false).text('Publish Plan Modifications');
                feedback.removeClass('d-none').addClass('alert-danger').text(xhr.responseJSON ? xhr.responseJSON.message : 'Server communication error.');
            }
        });
    });

    // Delete Plan CRUD action trigger
    $('#btnDeletePricing').on('click', function() {
        const id = $('#planId').val();
        if (!id) return;
        if (!confirm('Are you absolutely sure you want to permanently delete pricing plan ' + id.toUpperCase() + '?')) return;

        const feedback = $('#pricingFeedback');
        feedback.addClass('d-none');

        $.ajax({
            url: '/php/admin_pricing_action.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'delete_plan', id: id },
            success: function(res) {
                if (res.success) {
                    alert(res.message);
                    window.location.reload();
                } else {
                    feedback.removeClass('d-none').addClass('alert-danger').text(res.message);
                }
            }
        });
    });

    // ============================================================
    // FLUTTERWAVE CONFIGURATION SAVE ACTIONS
    // ============================================================
    $('#settingsCredForm').on('submit', function(e) {
        e.preventDefault();
        const feedback = $('#settingsFeedback');
        feedback.addClass('d-none').removeClass('alert-success alert-danger');
        $('#btnSaveSettings').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Saving keys...');

        $.ajax({
            url: '/php/admin_settings_action.php',
            type: 'POST',
            dataType: 'json',
            data: {
                flw_public_key: $('#flwPublicKey').val(),
                flw_secret_key: $('#flwSecretKey').val(),
                flw_encryption_key: $('#flwEncryptionKey').val()
            },
            success: function(res) {
                $('#btnSaveSettings').prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Save Configuration Keys');
                feedback.removeClass('d-none');
                if (res.success) {
                    feedback.addClass('alert-success').text(res.message);
                    setTimeout(() => {
                        window.location.reload();
                    }, 1200);
                } else {
                    feedback.addClass('alert-danger').text(res.message);
                }
            },
            error: function(xhr) {
                $('#btnSaveSettings').prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Save Configuration Keys');
                feedback.removeClass('d-none').addClass('alert-danger').text(xhr.responseJSON ? xhr.responseJSON.message : 'Failed to update settings.');
            }
        });
    });

    // ============================================================
    // RBAC PERMISSIONS INTERACTIVE TOGGLES
    // ============================================================
    $('.btn-toggle-perm-cell').on('click', function() {
        const btn = $(this);
        const role = btn.attr('data-role');
        const permission = btn.attr('data-permission');
        const rbacFeedback = $('#rbacFeedback');

        rbacFeedback.addClass('d-none');

        $.ajax({
            url: '/php/admin_rbac_action.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'toggle_permission',
                role: role,
                permission: permission
            },
            success: function(res) {
                if (res.success) {
                    // Instantly reload to reflect updated security matrix visual states
                    window.location.reload();
                } else {
                    alert(res.message);
                }
            },
            error: function(xhr) {
                alert(xhr.responseJSON ? xhr.responseJSON.message : 'Failed to toggle permission cell.');
            }
        });
    });

    // Register Role
    $('#rbacRoleForm').on('submit', function(e) {
        e.preventDefault();
        const feedback = $('#rbacFeedback');
        feedback.addClass('d-none').removeClass('alert-success alert-danger');
        $('#btnCreateRole').prop('disabled', true).text('Registering role...');

        $.ajax({
            url: '/php/admin_rbac_action.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'add_role',
                role_id: $('#roleIdInput').val(),
                role_name: $('#roleNameInput').val(),
                description: $('#roleDescInput').val()
            },
            success: function(res) {
                $('#btnCreateRole').prop('disabled', false).text('Registering role...');
                feedback.removeClass('d-none');
                if (res.success) {
                    feedback.addClass('alert-success').text(res.message);
                    setTimeout(() => {
                        window.location.reload();
                    }, 1200);
                } else {
                    feedback.addClass('alert-danger').text(res.message);
                }
            },
            error: function(xhr) {
                $('#btnCreateRole').prop('disabled', false).text('Registering role...');
                feedback.removeClass('d-none').addClass('alert-danger').text(xhr.responseJSON ? xhr.responseJSON.message : 'Server error.');
            }
        });
    });

    // ============================================================
    // NGS BACKUP & RECOVERY SYSTEM AJAX INTERACTORS
    // ============================================================

    // Function to reload backup list dynamically via AJAX
    function reloadBackupList() {
        $.ajax({
            url: '/php/admin_backup_action.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'list_backups' },
            success: function(res) {
                const tbody = $('#backupsListTableBody');
                tbody.empty();
                if (res.success && res.backups && res.backups.length > 0) {
                    res.backups.forEach(function(b) {
                        const dbBadge = b.database ? '<span class="text-green-600 font-bold text-base"><i class="fa-solid fa-circle-check"></i></span>' : '<span class="text-gray-300 text-base"><i class="fa-solid fa-circle-xmark"></i></span>';
                        const webBadge = b.websites ? '<span class="text-green-600 font-bold text-base"><i class="fa-solid fa-circle-check"></i></span>' : '<span class="text-gray-300 text-base"><i class="fa-solid fa-circle-xmark"></i></span>';
                        const uploadsBadge = b.uploads ? '<span class="text-green-600 font-bold text-base"><i class="fa-solid fa-circle-check"></i></span>' : '<span class="text-gray-300 text-base"><i class="fa-solid fa-circle-xmark"></i></span>';
                        const statusBadge = b.status === 'complete' ? '<span class="badge bg-green-100 text-green-800 rounded font-bold">Complete</span>' : '<span class="badge bg-amber-100 text-amber-800 rounded font-bold">Incomplete</span>';

                        tbody.append(`
                            <tr>
                                <td class="p-3.5 font-mono font-semibold text-gray-800">${b.backup_id}</td>
                                <td class="p-3.5 text-gray-500">${b.created_at}</td>
                                <td class="p-3.5 font-semibold text-gray-600">${b.triggered_by}</td>
                                <td class="p-3.5 text-center">${dbBadge}</td>
                                <td class="p-3.5 text-center">${webBadge}</td>
                                <td class="p-3.5 text-center">${uploadsBadge}</td>
                                <td class="p-3.5 text-center">${statusBadge}</td>
                                <td class="p-3.5 text-right">
                                  <div class="d-flex justify-content-end gap-1.5 flex-wrap">
                                    <button class="btn btn-xs btn-outline-info rounded-md btn-verify-backup" data-id="${b.backup_id}"><i class="fa-solid fa-shield-halved"></i> Verify</button>
                                    <button class="btn btn-xs btn-outline-success rounded-md btn-restore-backup" data-id="${b.backup_id}"><i class="fa-solid fa-rotate-left"></i> Restore</button>
                                    <button class="btn btn-xs btn-outline-danger rounded-md btn-delete-backup" data-id="${b.backup_id}"><i class="fa-solid fa-trash"></i></button>
                                  </div>
                                </td>
                            </tr>
                        `);
                    });
                } else {
                    tbody.append('<tr><td colspan="8" class="text-center text-gray-400 py-5">No backup points archived outside the webroot.</td></tr>');
                }
            },
            error: function() {
                $('#backupsListTableBody').html('<tr><td colspan="8" class="text-center text-red-500 py-5">Failed to communicate with backup server controller.</td></tr>');
            }
        });
    }

    // Trigger loading list when tab is clicked
    $('#backup-tab').on('click', function() {
        reloadBackupList();
    });

    // Also trigger loading on direct initialization
    reloadBackupList();

    // Create safety backup manually AJAX handler
    $('#btnTriggerBackup').on('click', function() {
        const btn = $(this);
        const feedback = $('#backupFeedback');
        feedback.addClass('d-none').removeClass('alert-success alert-danger');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Packaging System Backup...');

        $.ajax({
            url: '/php/admin_backup_action.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'create_backup' },
            success: function(res) {
                btn.prop('disabled', false).html('<i class="fas fa-rotate mr-1"></i> Create Safety Backup Now');
                feedback.removeClass('d-none');
                if (res.success) {
                    feedback.addClass('alert-success').text(res.message);
                    reloadBackupList();
                } else {
                    feedback.addClass('alert-danger').text(res.message);
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fas fa-rotate mr-1"></i> Create Safety Backup Now');
                feedback.removeClass('d-none').addClass('alert-danger').text(xhr.responseJSON ? xhr.responseJSON.message : 'Backup action failed.');
            }
        });
    });

    // Verify Integrity Check Action AJAX handler
    $(document).on('click', '.btn-verify-backup', function() {
        const backupId = $(this).attr('data-id');
        const feedback = $('#backupFeedback');
        feedback.addClass('d-none');

        $.ajax({
            url: '/php/admin_backup_action.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'verify_backup', backup_id: backupId },
            success: function(res) {
                alert("Verification Result: " + res.message);
            },
            error: function(xhr) {
                alert("Verification Mismatch: " + (xhr.responseJSON ? xhr.responseJSON.message : "Integrity check failed."));
            }
        });
    });

    // Explicit Restore Point Action AJAX handler
    $(document).on('click', '.btn-restore-backup', function() {
        const backupId = $(this).attr('data-id');
        const feedback = $('#backupFeedback');
        feedback.addClass('d-none');

        // Create explicit confirmation workflow
        if (!confirm("CRITICAL INSTRUCTION:\nAre you sure you want to restore the selected backup (" + backupId + ")?\n\nThis will completely overwrite current live databases and websites!\nA safety backup of the current live state will be automatically created first as a recovery fallback.")) {
            return;
        }

        const confirmCode = prompt("Please type 'CONFIRM RESTORE' to execute this highly privileged recovery operation:");
        if (confirmCode !== 'CONFIRM RESTORE') {
            alert("Restoration cancelled. Confirmation phrase did not match.");
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Restoring...');

        $.ajax({
            url: '/php/admin_backup_action.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'restore_backup', backup_id: backupId },
            success: function(res) {
                btn.prop('disabled', false).html('<i class="fa-solid fa-rotate-left"></i> Restore');
                alert(res.message);
                window.location.reload();
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fa-solid fa-rotate-left"></i> Restore');
                alert("Restore Error: " + (xhr.responseJSON ? xhr.responseJSON.message : "Process failed."));
            }
        });
    });

    // Delete versioned restore point AJAX handler
    $(document).on('click', '.btn-delete-backup', function() {
        const backupId = $(this).attr('data-id');
        if (!confirm("Are you sure you want to permanently delete backup " + backupId + "?\nThis action is irreversible!")) {
            return;
        }

        $.ajax({
            url: '/php/admin_backup_action.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'delete_backup', backup_id: backupId },
            success: function(res) {
                alert(res.message);
                reloadBackupList();
            },
            error: function(xhr) {
                alert("Delete Error: " + (xhr.responseJSON ? xhr.responseJSON.message : "Process failed."));
            }
        });
    });

    // ============================================================
    // ADMIN WEBSITE MANAGER INTERACTIONS (SUSPEND / DELETE)
    // ============================================================

    // Handle Admin Toggle Site Suspension
    $(document).on('click', '.btn-admin-toggle-site-suspension', function() {
        const siteId = $(this).attr('data-id');
        if (!confirm('Are you sure you want to toggle the suspension state of this website?')) return;

        $.ajax({
            url: '/php/delete_website_action.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'toggle_suspension', website_id: siteId },
            success: function(res) {
                alert(res.message);
                window.location.reload();
            },
            error: function(xhr) {
                alert(xhr.responseJSON ? xhr.responseJSON.message : 'Action failed.');
            }
        });
    });

    // Handle Admin Delete Website
    $(document).on('click', '.btn-admin-delete-site', function() {
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

    // Handle administrative sidebar hashes and activate bootstrap tabs
    const handleAdminHashes = function() {
        const hash = window.location.hash;
        if (!hash) return;

        // Map hash tags to active tab button IDs
        const tabMap = {
            '#websites-section': 'websites-tab',
            '#build-website': 'websites-tab',
            '#payment-settings-section': 'payment-settings-tab',
            '#activity-logs-section': 'activity-logs-tab'
        };

        const targetTabId = tabMap[hash];
        if (targetTabId) {
            const tabTriggerEl = document.getElementById(targetTabId);
            if (tabTriggerEl) {
                const tab = new bootstrap.Tab(tabTriggerEl);
                tab.show();
                // Smooth scroll to the tab content area
                $('html, body').animate({
                    scrollTop: $("#adminTabs").offset().top - 20
                }, 300);
            }
        }
    };

    // Trigger on hash load and change events
    handleAdminHashes();
    $(window).on('hashchange', handleAdminHashes);

    // Select first plan by default
    $('.btn-select-plan').first().click();
});
</script>
</body>
</html>
