<?php
/**
 * marketing.php
 *
 * Standalone Marketing & Campaigns Management page.
 */

declare(strict_types=1);

$pageTitle = 'Marketing Campaigns';
$pageSubtitle = 'Create promotional offers, track free trial signups, and monitor marketing conversion channels.';

require_once __DIR__ . '/admin_header.php';

// Load marketing stats or promo logs
$usersList = $conn->select('users') ?: [];
$trialUsersCount = 0;
$activePaidCount = 0;

foreach ($usersList as $u) {
    $plan = strtolower((string)($u['plan'] ?? ''));
    $status = strtolower((string)($u['status'] ?? ''));
    if ($status === 'trial' || strpos($plan, 'starter') !== false) {
        $trialUsersCount++;
    } elseif ($status === 'active' || strpos($plan, 'pro') !== false || strpos($plan, 'agency') !== false) {
        $activePaidCount++;
    }
}
?>

<div class="row g-4 mb-4">
  <div class="col-md-4">
    <div class="card border-0 shadow-sm rounded-xl p-4 bg-white">
      <div class="d-flex align-items-center gap-3">
        <div class="p-3 rounded-xl bg-purple-50 text-purple-600">
          <i class="fas fa-gift text-xl"></i>
        </div>
        <div>
          <span class="text-2xs font-bold text-gray-400 uppercase tracking-wider block">Free Trial Users</span>
          <span class="text-xl font-extrabold text-gray-800"><?php echo number_format($trialUsersCount); ?></span>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card border-0 shadow-sm rounded-xl p-4 bg-white">
      <div class="d-flex align-items-center gap-3">
        <div class="p-3 rounded-xl bg-emerald-50 text-emerald-600">
          <i class="fas fa-chart-line text-xl"></i>
        </div>
        <div>
          <span class="text-2xs font-bold text-gray-400 uppercase tracking-wider block">Paid Conversions</span>
          <span class="text-xl font-extrabold text-gray-800"><?php echo number_format($activePaidCount); ?></span>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card border-0 shadow-sm rounded-xl p-4 bg-white">
      <div class="d-flex align-items-center gap-3">
        <div class="p-3 rounded-xl bg-blue-50 text-blue-600">
          <i class="fas fa-bullhorn text-xl"></i>
        </div>
        <div>
          <span class="text-2xs font-bold text-gray-400 uppercase tracking-wider block">Active Promo Campaigns</span>
          <span class="text-xl font-extrabold text-gray-800">2 Active</span>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm rounded-xl">
  <div class="card-header bg-white border-b border-gray-100 py-3 d-flex justify-content-between align-items-center">
    <h3 class="text-base font-bold text-gray-800 m-0"><i class="fas fa-bullhorn text-purple-600 me-2"></i> Promotional Campaigns & Discount Offers</h3>
    <button class="btn btn-purple btn-sm font-bold text-white rounded-md" style="background-color: #7c3aed;" onclick="alert('Create campaign modal initialized.')"><i class="fas fa-plus me-1"></i> New Campaign</button>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0 text-xs">
        <thead class="bg-gray-50 text-gray-600 font-semibold">
          <tr>
            <th class="p-3.5">Campaign Name</th>
            <th class="p-3.5">Promo Code</th>
            <th class="p-3.5">Discount</th>
            <th class="p-3.5">Redemptions</th>
            <th class="p-3.5">Status</th>
          </tr>
        </thead>
        <tbody class="text-gray-700">
          <tr>
            <td class="p-3.5 font-bold">14-Day Free Starter Trial</td>
            <td class="p-3.5"><span class="font-mono bg-gray-100 px-2 py-0.5 rounded text-gray-700">AUTOTRIAL</span></td>
            <td class="p-3.5 text-purple-600 font-bold">100% OFF (1 Month)</td>
            <td class="p-3.5 font-semibold"><?php echo number_format($trialUsersCount); ?> Users</td>
            <td class="p-3.5"><span class="badge bg-success bg-opacity-10 text-success font-bold px-2.5 py-1 rounded-pill">Active</span></td>
          </tr>
          <tr>
            <td class="p-3.5 font-bold">Growth Launch Promo</td>
            <td class="p-3.5"><span class="font-mono bg-gray-100 px-2 py-0.5 rounded text-gray-700">LAUNCH2026</span></td>
            <td class="p-3.5 text-purple-600 font-bold">25% OFF Annual</td>
            <td class="p-3.5 font-semibold">48 Users</td>
            <td class="p-3.5"><span class="badge bg-success bg-opacity-10 text-success font-bold px-2.5 py-1 rounded-pill">Active</span></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
