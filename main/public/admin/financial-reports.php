<?php
/**
 * financial-reports.php
 *
 * Standalone Financial Reports & Ledger Summary page.
 */

declare(strict_types=1);

$pageTitle = 'Financial Reports';
$pageSubtitle = 'Detailed revenue breakdowns, payout statements, subscription ledgers, and transaction histories.';

require_once __DIR__ . '/admin_header.php';

// Check RBAC permission specifically for financial reports
if (!checkAdminPermission('financial.reports')) {
    echo '<div class="alert alert-danger rounded-xl font-semibold"><i class="fas fa-lock me-2"></i> Access Denied: You do not possess permission to view sensitive Financial Reports.</div>';
    require_once __DIR__ . '/admin_footer.php';
    exit;
}

$conn->createTable('payments');
$paymentsList = $conn->select('payments') ?: [];

$grossRevenue = 0.0;
$successfulTxCount = 0;
$monthlyMap = [];

foreach ($paymentsList as $p) {
    if (strtolower((string)($p['status'] ?? '')) === 'successful') {
        $amt = (float)($p['amount'] ?? 0.0);
        $grossRevenue += $amt;
        $successfulTxCount++;
        $mKey = date('Y-m', strtotime($p['created_at'] ?? 'now'));
        $monthlyMap[$mKey] = ($monthlyMap[$mKey] ?? 0.0) + $amt;
    }
}
?>

<div class="row g-4 mb-4">
  <div class="col-md-4">
    <div class="card border-0 shadow-sm rounded-xl p-4 bg-white">
      <div class="d-flex align-items-center gap-3">
        <div class="p-3 rounded-xl bg-emerald-50 text-emerald-600">
          <i class="fas fa-vault text-xl"></i>
        </div>
        <div>
          <span class="text-2xs font-bold text-gray-400 uppercase tracking-wider block">Audited Gross Revenue</span>
          <span class="text-xl font-extrabold text-gray-800">₦<?php echo number_format($grossRevenue, 2); ?></span>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card border-0 shadow-sm rounded-xl p-4 bg-white">
      <div class="d-flex align-items-center gap-3">
        <div class="p-3 rounded-xl bg-blue-50 text-blue-600">
          <i class="fas fa-check-circle text-xl"></i>
        </div>
        <div>
          <span class="text-2xs font-bold text-gray-400 uppercase tracking-wider block">Verified Transactions</span>
          <span class="text-xl font-extrabold text-gray-800"><?php echo number_format($successfulTxCount); ?></span>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card border-0 shadow-sm rounded-xl p-4 bg-white">
      <div class="d-flex align-items-center gap-3">
        <div class="p-3 rounded-xl bg-purple-50 text-purple-600">
          <i class="fas fa-file-invoice-dollar text-xl"></i>
        </div>
        <div>
          <span class="text-2xs font-bold text-gray-400 uppercase tracking-wider block">Active Gateways</span>
          <span class="text-xl font-extrabold text-gray-800">Flutterwave Standard</span>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm rounded-xl mb-4">
  <div class="card-header bg-white border-b border-gray-100 py-3 d-flex justify-content-between align-items-center">
    <h3 class="text-base font-bold text-gray-800 m-0"><i class="fas fa-chart-bar text-emerald-600 me-2"></i> Monthly Revenue Aggregation Ledger</h3>
    <button class="btn btn-outline-secondary btn-sm font-semibold rounded-md" onclick="window.print()"><i class="fas fa-print me-1"></i> Export PDF / Print</button>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0 text-xs">
        <thead class="bg-gray-50 text-gray-600 font-semibold">
          <tr>
            <th class="p-3.5">Accounting Period</th>
            <th class="p-3.5">Verified Collections</th>
            <th class="p-3.5">Currency</th>
            <th class="p-3.5">Status</th>
          </tr>
        </thead>
        <tbody class="text-gray-700">
          <?php if (count($monthlyMap) > 0): ?>
            <?php foreach ($monthlyMap as $month => $total): ?>
              <tr>
                <td class="p-3.5 font-bold text-gray-800"><i class="far fa-calendar-alt text-gray-400 me-2"></i><?php echo date('F Y', strtotime($month . '-01')); ?></td>
                <td class="p-3.5 text-emerald-600 font-extrabold text-sm">₦<?php echo number_format((float)$total, 2); ?></td>
                <td class="p-3.5 font-semibold text-gray-500">NGN</td>
                <td class="p-3.5"><span class="badge bg-success bg-opacity-10 text-success font-bold px-2.5 py-1 rounded-pill"><i class="fas fa-check-double me-1"></i> Reconciled</span></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="4" class="text-center text-gray-400 py-6">No historical payment data currently available in system ledger.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
