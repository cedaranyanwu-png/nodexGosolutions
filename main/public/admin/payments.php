<?php
/**
 * payments.php
 *
 * Standalone Payment Verification Log Audits page.
 */

declare(strict_types=1);

$pageTitle = 'Payments';
$pageSubtitle = 'Audit payment transactions, transaction references, and Flutterwave verification logs.';

require_once __DIR__ . '/admin_header.php';

$conn->createTable('payments');
$paymentsList = $conn->select('payments') ?: [];
?>

<div class="card border-0 shadow-sm rounded-xl">
  <div class="card-header bg-white border-b border-gray-100 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h3 class="text-base font-bold text-gray-800 m-0"><i class="fas fa-receipt text-emerald-600 me-2"></i> Payment Verification Log Audits</h3>
    <input type="text" id="paymentSearchInput" class="form-control form-control-sm text-xs rounded-md" placeholder="Search payments..." style="width: 220px;">
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0 text-xs" id="adminPaymentsTable">
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
              <tr class="payment-row">
                <td class="p-3.5 font-bold">User ID: <?php echo (int)($p['user_id'] ?? 0); ?></td>
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
                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill font-bold px-2.5 py-1">Successful</span>
                  <?php elseif ($pStatus === 'pending'): ?>
                    <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill font-bold px-2.5 py-1">Pending</span>
                  <?php else: ?>
                    <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill font-bold px-2.5 py-1">Failed</span>
                  <?php endif; ?>
                </td>
                <td class="p-3.5 text-gray-400"><?php echo date('M d, Y H:i', strtotime($p['created_at'] ?? 'now')); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" class="text-center text-gray-400 py-6">No payments recorded in system registry.</td>
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
    $('#paymentSearchInput').on('input', function() {
        const val = $(this).val().toLowerCase();
        $('.payment-row').each(function() {
            const txt = $(this).text().toLowerCase();
            $(this).toggle(txt.includes(val));
        });
    });
});
</script>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
