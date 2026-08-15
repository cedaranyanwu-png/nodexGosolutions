<?php
/**
 * dashboard.php (Admin Core Dashboard)
 *
 * Standalone System Overview Dashboard.
 */

declare(strict_types=1);

$pageTitle = 'System Administration Overview';
$pageSubtitle = 'Review analytical counters, system overview, and platform activities.';

require_once __DIR__ . '/admin_header.php';

// Calculate system metrics
$usersList = $conn->select('users') ?: [];

$conn->createTable('websites');
$websitesList = $conn->select('websites') ?: [];
$totalWebsitesCount = count($websitesList);

$conn->createTable('payments');
$isRevenueAuthorized = in_array($roleClean, ['superadmin', 'admin', 'financial', 'marketinghead'], true);
$paymentsList = $isRevenueAuthorized ? ($conn->select('payments') ?: []) : [];

$totalUsersCount       = 0;
$activeUsersCount      = 0;
$usersOnFreeTrialCount = 0;
$expiredTrialsCount    = 0;
$activeSubscribersCount= 0;
$suspendedUsersCount   = 0;

$totalSuccessfulPaymentsSum = 0;
$monthlyRevenueSum = 0;
$currentMonthYear = date('Y-m');

if ($isRevenueAuthorized) {
    foreach ($paymentsList as $pm) {
        $pmStatus = strtolower((string)($pm['status'] ?? ''));
        if ($pmStatus === 'successful') {
            $amount = (float)($pm['amount'] ?? 0.0);
            $totalSuccessfulPaymentsSum += $amount;
            if (str_starts_with(($pm['created_at'] ?? ''), $currentMonthYear)) {
                $monthlyRevenueSum += $amount;
            }
        }
    }
}

foreach ($usersList as &$u) {
    $uRole = strtolower((string)($u['role'] ?? 'tenant'));
    if ($uRole === 'admin' || $uRole === 'super admin') continue;

    $totalUsersCount++;
    $resolvedStatus = checkAndUpdateSubscription($u, $conn);

    if ($resolvedStatus === 'trial') $usersOnFreeTrialCount++;
    elseif ($resolvedStatus === 'active') $activeSubscribersCount++;
    elseif ($resolvedStatus === 'expired') $expiredTrialsCount++;
    elseif ($resolvedStatus === 'suspended') $suspendedUsersCount++;

    if (strtolower((string)($u['status'] ?? '')) === 'active') $activeUsersCount++;
}
unset($u);

$conn->createTable('tickets');
$openTicketsCount = count($conn->select('tickets', ['status' => 'open']) ?: []);

$conn->createTable('reports');
$pendingReportsCount = count($conn->select('reports', ['status' => 'pending']) ?: []);
?>

<!-- OVERVIEW ANALYTIC METRICS CARDS -->
<div class="row mb-4">
  <div class="col-lg-3 col-md-6 col-12">
    <div class="analytic-card">
      <div class="w-12 h-12 bg-primary bg-opacity-10 text-primary rounded-xl d-flex align-items-center justify-content-center text-xl"><i class="fas fa-users"></i></div>
      <div class="text-right">
        <div class="text-xs uppercase text-gray-400 font-bold" style="font-size: 9px;">Total Users</div>
        <div class="text-xl font-extrabold text-gray-800 mt-1"><?= $totalUsersCount ?></div>
      </div>
    </div>
  </div>
  <div class="col-lg-3 col-md-6 col-12">
    <div class="analytic-card">
      <div class="w-12 h-12 bg-success bg-opacity-10 text-success rounded-xl d-flex align-items-center justify-content-center text-xl"><i class="fas fa-user-check"></i></div>
      <div class="text-right">
        <div class="text-xs uppercase text-gray-400 font-bold" style="font-size: 9px;">Active Users</div>
        <div class="text-xl font-extrabold text-gray-800 mt-1"><?= $activeUsersCount ?></div>
      </div>
    </div>
  </div>
  <div class="col-lg-3 col-md-6 col-12">
    <div class="analytic-card">
      <div class="w-12 h-12 bg-warning bg-opacity-10 text-warning rounded-xl d-flex align-items-center justify-content-center text-xl"><i class="fas fa-globe"></i></div>
      <div class="text-right">
        <div class="text-xs uppercase text-gray-400 font-bold" style="font-size: 9px;">Total Websites</div>
        <div class="text-xl font-extrabold text-gray-800 mt-1"><?= $totalWebsitesCount ?></div>
      </div>
    </div>
  </div>
  <div class="col-lg-3 col-md-6 col-12">
    <?php if ($isRevenueAuthorized): ?>
      <div class="analytic-card">
        <div class="w-12 h-12 bg-emerald-500 bg-opacity-10 text-emerald-600 rounded-xl d-flex align-items-center justify-content-center text-xl"><i class="fas fa-money-bill-trend-up"></i></div>
        <div class="text-right">
          <div class="text-xs uppercase text-gray-400 font-bold" style="font-size: 9px;">Monthly Revenue</div>
          <div class="text-xl font-extrabold text-gray-800 mt-1">₦<?= number_format((float)$monthlyRevenueSum) ?></div>
        </div>
      </div>
    <?php else: ?>
      <div class="analytic-card">
        <div class="w-12 h-12 bg-danger bg-opacity-10 text-danger rounded-xl d-flex align-items-center justify-content-center text-xl"><i class="fas fa-ticket"></i></div>
        <div class="text-right">
          <div class="text-xs uppercase text-gray-400 font-bold" style="font-size: 9px;">Open Support Tickets</div>
          <div class="text-xl font-extrabold text-gray-800 mt-1"><?= $openTicketsCount ?></div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- DYNAMIC DISCOVERED TOOLS CONTAINERS -->
<?php
global $toolManager;
if (isset($toolManager) && $toolManager instanceof ToolManager):
    $accessibleAdminTools = array_filter($toolManager->enabled(), function($t) use ($toolManager, $roleClean) {
        return $toolManager->canAccess($t['slug'], $roleClean);
    });
    foreach ($accessibleAdminTools as $aTool):
        $dSlug = htmlspecialchars((string)($aTool['slug'] ?? ''));
?>
    <div class="row mb-4 d-none dynamic-admin-tool-container" id="tool-<?php echo $dSlug; ?>">
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

<script>
document.addEventListener("DOMContentLoaded", function() {
    function handleAdminToolHashes() {
        const hash = window.location.hash;
        if (!hash || !hash.startsWith('#tool-')) return;
        const containers = document.querySelectorAll('.dynamic-admin-tool-container');
        containers.forEach(el => el.classList.add('d-none'));
        const target = document.querySelector(hash);
        if (target) {
            target.classList.remove('d-none');
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
    handleAdminToolHashes();
    window.addEventListener("hashchange", handleAdminToolHashes);
});
</script>

<!-- QUICK ACCESS LINKS & RECENT ACTIVITY FEED -->
<div class="row">
  <div class="col-lg-8 mb-4">
    <div class="card border-0 shadow-sm rounded-xl">
      <div class="card-header bg-white border-b border-gray-100 py-3">
        <h3 class="text-base font-bold text-gray-800 m-0"><i class="fas fa-compass text-blue-600 me-2"></i> Standalone Workspaces Quick Launch</h3>
      </div>
      <div class="card-body p-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-center">
          <a href="/admin/users.php" class="p-3 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-xl text-xs font-bold block no-underline transition">
            <i class="fas fa-users text-lg block mb-1"></i> User Directory
          </a>
          <a href="/admin/websites.php" class="p-3 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-xl text-xs font-bold block no-underline transition">
            <i class="fas fa-globe text-lg block mb-1"></i> Websites
          </a>
          <a href="/admin/support.php" class="p-3 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-xl text-xs font-bold block no-underline transition">
            <i class="fas fa-headset text-lg block mb-1"></i> Support Queue
          </a>
          <a href="/admin/settings.php" class="p-3 bg-purple-50 hover:bg-purple-100 text-purple-700 rounded-xl text-xs font-bold block no-underline transition">
            <i class="fas fa-gears text-lg block mb-1"></i> System Settings
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4 mb-4">
    <div class="card border-0 shadow-sm rounded-xl">
      <div class="card-header bg-white border-b border-gray-100 py-3">
        <h3 class="text-base font-bold text-gray-800 m-0"><i class="fas fa-list-check text-primary me-2"></i> System Health</h3>
      </div>
      <div class="card-body p-4 text-xs">
        <div class="d-flex justify-between items-center mb-2.5 pb-2 border-b">
          <span class="text-gray-500 font-semibold">Active Subdomains:</span>
          <span class="font-bold text-blue-600"><?php echo $totalWebsitesCount; ?> Active</span>
        </div>
        <div class="d-flex justify-between items-center mb-2.5 pb-2 border-b">
          <span class="text-gray-500 font-semibold">Open Support Tickets:</span>
          <span class="font-bold text-amber-600"><?php echo $openTicketsCount; ?> Open</span>
        </div>
        <div class="d-flex justify-between items-center">
          <span class="text-gray-500 font-semibold">Pending Abuse Reports:</span>
          <span class="font-bold text-red-600"><?php echo $pendingReportsCount; ?> Pending</span>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
