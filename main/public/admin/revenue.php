<?php
/**
 * revenue.php
 *
 * Standalone Revenue & Financial Analytics page.
 */

declare(strict_types=1);

$pageTitle = 'Revenue & Financial Analytics';
$pageSubtitle = 'Monitor platform revenue, total verified subscriptions, and financial metrics.';

require_once __DIR__ . '/admin_header.php';

$conn->createTable('payments');
$paymentsList = $conn->select('payments') ?: [];

$totalSuccessfulPaymentsSum = 0;
$monthlyRevenueSum = 0;
$currentMonthYear = date('Y-m');

foreach ($paymentsList as $pm) {
    $pmStatus = strtolower((string)($pm['status'] ?? ''));
    if ($pmStatus === 'successful') {
        $amount = (float)($pm['amount'] ?? 0.0);
        $totalSuccessfulPaymentsSum += $amount;

        $createdAt = $pm['created_at'] ?? '';
        if (str_starts_with($createdAt, $currentMonthYear)) {
            $monthlyRevenueSum += $amount;
        }
    }
}
?>

<div class="row mb-4">
  <div class="col-lg-4 col-md-6 col-12">
    <div class="analytic-card">
      <div class="w-12 h-12 bg-emerald-500 bg-opacity-10 text-emerald-600 rounded-xl d-flex align-items-center justify-content-center text-xl"><i class="fas fa-money-bill-wave"></i></div>
      <div class="text-right">
        <div class="text-xs uppercase text-gray-400 font-bold" style="font-size: 9px;">Total Gross Revenue</div>
        <div class="text-xl font-extrabold text-gray-800 mt-1">₦<?php echo number_format((float)$totalSuccessfulPaymentsSum); ?></div>
      </div>
    </div>
  </div>
  <div class="col-lg-4 col-md-6 col-12">
    <div class="analytic-card">
      <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-xl d-flex align-items-center justify-content-center text-xl"><i class="fas fa-calendar-alt"></i></div>
      <div class="text-right">
        <div class="text-xs uppercase text-gray-400 font-bold" style="font-size: 9px;">Current Month Revenue</div>
        <div class="text-xl font-extrabold text-gray-800 mt-1">₦<?php echo number_format((float)$monthlyRevenueSum); ?></div>
      </div>
    </div>
  </div>
  <div class="col-lg-4 col-md-6 col-12">
    <div class="analytic-card">
      <div class="w-12 h-12 bg-success bg-opacity-10 text-success rounded-xl d-flex align-items-center justify-content-center text-xl"><i class="fas fa-circle-check"></i></div>
      <div class="text-right">
        <div class="text-xs uppercase text-gray-400 font-bold" style="font-size: 9px;">Successful Transactions</div>
        <div class="text-xl font-extrabold text-gray-800 mt-1"><?php echo count($paymentsList); ?></div>
      </div>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm rounded-xl">
  <div class="card-header bg-white border-b border-gray-100 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h3 class="text-base font-bold text-gray-800 m-0"><i class="fas fa-chart-line text-emerald-600 me-2"></i> Verified Transactions Summary</h3>
    <input type="text" id="revSearchInput" class="form-control form-control-sm text-xs rounded-md" placeholder="Search transactions..." style="width: 220px;">
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0 text-xs">
        <thead class="bg-gray-50 text-gray-600 font-semibold">
          <tr>
            <th class="p-3.5">User ID</th>
            <th class="p-3.5">Reference</th>
            <th class="p-3.5">Plan Name</th>
            <th class="p-3.5">Amount</th>
            <th class="p-3.5">Status</th>
            <th class="p-3.5">Date</th>
          </tr>
        </thead>
        <tbody class="text-gray-700">
          <?php if (count($paymentsList) > 0): ?>
            <?php foreach (array_reverse($paymentsList) as $pm):
              $pStatus = strtolower((string)($pm['status'] ?? ''));
            ?>
              <tr class="rev-row">
                <td class="p-3.5 font-bold">User #<?php echo (int)($pm['user_id'] ?? 0); ?></td>
                <td class="p-3.5 font-mono text-gray-600"><?php echo htmlspecialchars($pm['tx_ref'] ?? ''); ?></td>
                <td class="p-3.5 font-semibold text-gray-800"><?php echo htmlspecialchars($pm['plan_name'] ?? 'Plan'); ?></td>
                <td class="p-3.5 font-bold text-blue-600">₦<?php echo number_format((float)($pm['amount'] ?? 0.0)); ?></td>
                <td class="p-3.5">
                  <span class="badge bg-success bg-opacity-10 text-success rounded-pill font-bold px-2.5 py-1">Successful</span>
                </td>
                <td class="p-3.5 text-gray-400"><?php echo date('M d, Y H:i', strtotime($pm['created_at'] ?? 'now')); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="text-center text-gray-400 py-6">No verified revenue records found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function() {
    $('#revSearchInput').on('input', function() {
        const val = $(this).val().toLowerCase();
        $('.rev-row').each(function() {
            const txt = $(this).text().toLowerCase();
            $(this).toggle(txt.includes(val));
        });
    });
});
</script>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
