<?php
/**
 * monetization.php (Admin Panel)
 *
 * Comprehensive Admin Monetization Management Panel.
 * Includes Adsterra Configuration, Ad Units, Platform Revenue Sharing Rules,
 * Memberships Audit, User Earnings, and Payout Approvals.
 * Strictly gated by RBAC permissions for authorized roles.
 */

declare(strict_types=1);

$pageTitle = 'Monetization Infrastructure';
$pageSubtitle = 'Manage Adsterra ad networks, membership gateways, platform revenue share, and payouts.';

require_once __DIR__ . '/admin_header.php';

// Access Control: Restrict to authorized admin/financial staff
if (!in_array($roleClean, ['superadmin', 'admin', 'financial'], true)) {
    echo "<div class='alert alert-danger rounded-xl p-4 m-4'>Access Denied: Financial and Monetization administration rights required.</div>";
    require_once __DIR__ . '/admin_footer.php';
    exit;
}

$conn->createTable('monetization_settings');
$conn->createTable('ad_units');
$conn->createTable('user_earnings');
$conn->createTable('platform_revenue');
$conn->createTable('payouts');
$conn->createTable('membership_plans');
$conn->createTable('memberships');

$monSettings = $conn->selectOne('monetization_settings', ['id' => 1]) ?: [
    'ad_revenue_share_percent' => 70.0,
    'membership_platform_fee_percent' => 10.0,
    'adsterra_publisher_id' => '',
    'adsterra_api_key' => '',
    'payout_min_threshold' => 5000.0,
    'is_ad_monetization_enabled' => 1,
    'is_membership_monetization_enabled' => 1
];

$adUnits = $conn->select('ad_units') ?: [];
$payoutsList = $conn->select('payouts') ?: [];
$allEarnings = $conn->select('user_earnings') ?: [];
$platformRevenues = $conn->select('platform_revenue') ?: [];
$membershipsList = $conn->select('memberships') ?: [];

$totalPlatformGross = 0.0;
$totalPlatformNetRevenue = 0.0;
foreach ($platformRevenues as $pr) {
    $totalPlatformGross += (float)($pr['gross_amount'] ?? 0);
    $totalPlatformNetRevenue += (float)($pr['net_platform_fee'] ?? 0);
}

$totalUserPaidOut = 0.0;
$pendingPayoutsSum = 0.0;
foreach ($payoutsList as $p) {
    $st = strtolower((string)($p['status'] ?? ''));
    if ($st === 'completed') {
        $totalUserPaidOut += (float)($p['amount'] ?? 0);
    } elseif ($st === 'pending') {
        $pendingPayoutsSum += (float)($p['amount'] ?? 0);
    }
}
?>

<!-- OVERVIEW ANALYTICS STATS -->
<div class="row g-3 mb-4">
  <div class="col-lg-3 col-md-6">
    <div class="card border-0 shadow-sm rounded-xl p-3 bg-white">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <span class="text-xs uppercase font-bold text-gray-400 block mb-1" style="font-size: 10px;">Platform Gross Volume</span>
          <h4 class="text-xl font-extrabold text-gray-800 m-0">₦<?php echo number_format($totalPlatformGross); ?></h4>
        </div>
        <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl d-flex align-items-center justify-content-center fs-5"><i class="fas fa-chart-line"></i></div>
      </div>
    </div>
  </div>
  <div class="col-lg-3 col-md-6">
    <div class="card border-0 shadow-sm rounded-xl p-3 bg-white">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <span class="text-xs uppercase font-bold text-gray-400 block mb-1" style="font-size: 10px;">Net Platform Revenue</span>
          <h4 class="text-xl font-extrabold text-emerald-600 m-0">₦<?php echo number_format($totalPlatformNetRevenue); ?></h4>
        </div>
        <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl d-flex align-items-center justify-content-center fs-5"><i class="fas fa-vault"></i></div>
      </div>
    </div>
  </div>
  <div class="col-lg-3 col-md-6">
    <div class="card border-0 shadow-sm rounded-xl p-3 bg-white">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <span class="text-xs uppercase font-bold text-gray-400 block mb-1" style="font-size: 10px;">Completed Payouts</span>
          <h4 class="text-xl font-extrabold text-gray-800 m-0">₦<?php echo number_format($totalUserPaidOut); ?></h4>
        </div>
        <div class="w-10 h-10 bg-purple-50 text-purple-600 rounded-xl d-flex align-items-center justify-content-center fs-5"><i class="fas fa-hand-holding-dollar"></i></div>
      </div>
    </div>
  </div>
  <div class="col-lg-3 col-md-6">
    <div class="card border-0 shadow-sm rounded-xl p-3 bg-white">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <span class="text-xs uppercase font-bold text-gray-400 block mb-1" style="font-size: 10px;">Pending Payout Queue</span>
          <h4 class="text-xl font-extrabold text-amber-600 m-0">₦<?php echo number_format($pendingPayoutsSum); ?></h4>
        </div>
        <div class="w-10 h-10 bg-amber-50 text-amber-600 rounded-xl d-flex align-items-center justify-content-center fs-5"><i class="fas fa-clock-rotate-left"></i></div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <!-- CONFIGURATION & REVENUE SHARING FORM -->
  <div class="col-lg-5">
    <div class="card border-0 shadow-sm rounded-xl mb-4">
      <div class="card-header bg-white border-b border-gray-100 py-3">
        <h3 class="text-base font-bold text-gray-800 m-0"><i class="fas fa-sliders text-primary me-2"></i> Revenue Share & Adsterra Settings</h3>
      </div>
      <div class="card-body p-4 text-xs">
        <form id="monetizationSettingsForm">
          <input type="hidden" name="action" value="save_settings">
          <div class="mb-3">
            <label class="form-label font-bold text-gray-700">User Ad Revenue Share (%)</label>
            <input type="number" step="0.1" name="ad_revenue_share_percent" class="form-control text-xs" value="<?php echo htmlspecialchars((string)$monSettings['ad_revenue_share_percent']); ?>" required>
            <span class="text-gray-400" style="font-size: 10px;">Percentage of Adsterra earnings paid to the website owner.</span>
          </div>
          <div class="mb-3">
            <label class="form-label font-bold text-gray-700">Platform Membership Commission Fee (%)</label>
            <input type="number" step="0.1" name="membership_platform_fee_percent" class="form-control text-xs" value="<?php echo htmlspecialchars((string)$monSettings['membership_platform_fee_percent']); ?>" required>
            <span class="text-gray-400" style="font-size: 10px;">Platform cut deducted from user paid membership sales.</span>
          </div>
          <div class="mb-3">
            <label class="form-label font-bold text-gray-700">Adsterra Publisher ID</label>
            <input type="text" name="adsterra_publisher_id" class="form-control text-xs" value="<?php echo htmlspecialchars((string)$monSettings['adsterra_publisher_id']); ?>" placeholder="Publisher ID...">
          </div>
          <div class="mb-3">
            <label class="form-label font-bold text-gray-700">Minimum User Payout Threshold (₦)</label>
            <input type="number" name="payout_min_threshold" class="form-control text-xs" value="<?php echo htmlspecialchars((string)$monSettings['payout_min_threshold']); ?>" required>
          </div>
          <button type="submit" class="btn btn-primary btn-sm rounded-lg w-100 font-bold py-2"><i class="fas fa-save me-1"></i> Save Monetization Rules</button>
        </form>
      </div>
    </div>
  </div>

  <!-- AD UNITS & PAYOUT QUEUE -->
  <div class="col-lg-7">
    <!-- CREATE AD UNIT FORM -->
    <div class="card border-0 shadow-sm rounded-xl mb-4">
      <div class="card-header bg-white border-b border-gray-100 py-3 d-flex justify-content-between align-items-center">
        <h3 class="text-base font-bold text-gray-800 m-0"><i class="fas fa-rectangle-ad text-amber-500 me-2"></i> Adsterra Ad Placements</h3>
        <button class="btn btn-xs btn-primary rounded-md" data-bs-toggle="collapse" data-bs-target="#newAdUnitCollapse"><i class="fas fa-plus me-1"></i> New Ad Unit</button>
      </div>
      <div class="collapse p-4 border-b bg-gray-50" id="newAdUnitCollapse">
        <form id="createAdUnitForm" class="text-xs">
          <input type="hidden" name="action" value="create_ad_unit">
          <div class="row g-2 mb-2">
            <div class="col-md-6">
              <label class="font-semibold text-gray-700">Ad Title</label>
              <input type="text" name="title" class="form-control text-xs" placeholder="e.g. Header Banner Ad 728x90" required>
            </div>
            <div class="col-md-3">
              <label class="font-semibold text-gray-700">Format</label>
              <select name="format" class="form-select text-xs">
                <option value="banner">Banner</option>
                <option value="native">Native</option>
                <option value="popunder">Popunder</option>
                <option value="socialbar">Social Bar</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="font-semibold text-gray-700">Placement</label>
              <select name="placement" class="form-select text-xs">
                <option value="header">Header</option>
                <option value="footer">Footer</option>
                <option value="sidebar">Sidebar</option>
                <option value="inline">Inline Content</option>
              </select>
            </div>
          </div>
          <div class="mb-2">
            <label class="font-semibold text-gray-700">Adsterra Script / Embed Code</label>
            <textarea name="ad_code" class="form-control text-xs font-mono" rows="3" placeholder="<script ...></script>" required></textarea>
          </div>
          <button type="submit" class="btn btn-xs btn-success font-bold px-3"><i class="fas fa-check me-1"></i> Create Ad Unit</button>
        </form>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0 text-xs">
            <thead class="bg-gray-50">
              <tr>
                <th class="p-3">ID</th>
                <th class="p-3">Ad Unit Title</th>
                <th class="p-3">Format</th>
                <th class="p-3">Placement</th>
                <th class="p-3">Status</th>
                <th class="p-3 text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($adUnits) > 0): ?>
                <?php foreach ($adUnits as $au): ?>
                  <tr>
                    <td class="p-3 font-bold">#<?php echo $au['id']; ?></td>
                    <td class="p-3 font-semibold text-gray-800"><?php echo htmlspecialchars($au['title']); ?></td>
                    <td class="p-3"><span class="badge bg-blue-50 text-blue-700 uppercase" style="font-size: 9px;"><?php echo htmlspecialchars($au['format']); ?></span></td>
                    <td class="p-3 text-gray-500"><?php echo htmlspecialchars($au['placement']); ?></td>
                    <td class="p-3">
                      <?php if ((int)($au['is_active'] ?? 0) === 1): ?>
                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill">Active</span>
                      <?php else: ?>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill">Disabled</span>
                      <?php endif; ?>
                    </td>
                    <td class="p-3 text-end">
                      <button class="btn btn-xs btn-outline-danger btn-delete-ad-unit" data-id="<?php echo $au['id']; ?>"><i class="fas fa-trash"></i></button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="6" class="text-center text-gray-400 py-4">No Adsterra ad units created yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- PENDING PAYOUTS QUEUE -->
    <div class="card border-0 shadow-sm rounded-xl">
      <div class="card-header bg-white border-b border-gray-100 py-3">
        <h3 class="text-base font-bold text-gray-800 m-0"><i class="fas fa-money-bill-transfer text-emerald-600 me-2"></i> User Payout Requests</h3>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0 text-xs">
            <thead class="bg-gray-50">
              <tr>
                <th class="p-3">User ID</th>
                <th class="p-3">Amount</th>
                <th class="p-3">Bank Details</th>
                <th class="p-3">Status</th>
                <th class="p-3 text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($payoutsList) > 0): ?>
                <?php foreach (array_reverse($payoutsList) as $po):
                  $st = strtolower((string)($po['status'] ?? 'pending'));
                ?>
                  <tr>
                    <td class="p-3 font-bold">User #<?php echo $po['user_id']; ?></td>
                    <td class="p-3 font-extrabold text-emerald-600">₦<?php echo number_format((float)$po['amount']); ?></td>
                    <td class="p-3">
                      <span class="block font-semibold"><?php echo htmlspecialchars($po['bank_name']); ?></span>
                      <span class="block text-2xs text-gray-400 font-mono"><?php echo htmlspecialchars($po['account_number']); ?> (<?php echo htmlspecialchars($po['account_name']); ?>)</span>
                    </td>
                    <td class="p-3">
                      <?php if ($st === 'completed'): ?>
                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill">Completed</span>
                      <?php elseif ($st === 'rejected'): ?>
                        <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill">Rejected</span>
                      <?php else: ?>
                        <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill">Pending</span>
                      <?php endif; ?>
                    </td>
                    <td class="p-3 text-end">
                      <?php if ($st === 'pending'): ?>
                        <button class="btn btn-xs btn-success me-1 btn-process-payout" data-id="<?php echo $po['id']; ?>" data-status="completed"><i class="fas fa-check"></i> Approve</button>
                        <button class="btn btn-xs btn-outline-danger btn-process-payout" data-id="<?php echo $po['id']; ?>" data-status="rejected"><i class="fas fa-xmark"></i> Reject</button>
                      <?php else: ?>
                        <span class="text-gray-400 font-mono text-2xs"><?php echo htmlspecialchars($po['processed_at'] ?? 'Done'); ?></span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="5" class="text-center text-gray-400 py-4">No payout requests submitted yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function() {
    $('#monetizationSettingsForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '/php/monetization_admin_action.php',
            type: 'POST',
            dataType: 'json',
            data: $(this).serialize(),
            success: function(res) {
                alert(res.message);
                if (res.success) location.reload();
            }
        });
    });

    $('#createAdUnitForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '/php/monetization_admin_action.php',
            type: 'POST',
            dataType: 'json',
            data: $(this).serialize(),
            success: function(res) {
                alert(res.message);
                if (res.success) location.reload();
            }
        });
    });

    $('.btn-delete-ad-unit').on('click', function() {
        const id = $(this).data('id');
        if (!confirm('Delete ad unit #' + id + '?')) return;
        $.ajax({
            url: '/php/monetization_admin_action.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'delete_ad_unit', unit_id: id },
            success: function(res) {
                alert(res.message);
                if (res.success) location.reload();
            }
        });
    });

    $('.btn-process-payout').on('click', function() {
        const id = $(this).data('id');
        const st = $(this).data('status');
        if (!confirm('Mark payout #' + id + ' as ' + st.toUpperCase() + '?')) return;
        $.ajax({
            url: '/php/monetization_admin_action.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'process_payout', payout_id: id, status: st },
            success: function(res) {
                alert(res.message);
                if (res.success) location.reload();
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/admin_footer.php'; ?>