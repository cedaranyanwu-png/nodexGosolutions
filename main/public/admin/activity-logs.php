<?php
/**
 * activity-logs.php
 *
 * Standalone System Audit Logs page.
 */

declare(strict_types=1);

$pageTitle = 'System Audit Logs';
$pageSubtitle = 'Real-time administrative audit trace and operational logs.';

require_once __DIR__ . '/admin_header.php';

$conn->createTable('activity_logs');
$activityLogsList = $conn->select('activity_logs') ?: [];
?>

<div class="card border-0 shadow-sm rounded-xl">
  <div class="card-header bg-white border-b border-gray-100 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h3 class="text-base font-bold text-gray-800 m-0"><i class="fas fa-rectangle-list text-primary me-2"></i> Audit Activity Feed</h3>
    <input type="text" id="logSearchInput" class="form-control form-control-sm text-xs rounded-md" placeholder="Search logs..." style="width: 220px;">
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0 text-xs">
        <thead class="bg-gray-50 text-gray-600 font-semibold">
          <tr>
            <th class="p-3.5">ID</th>
            <th class="p-3.5">User / Admin Email</th>
            <th class="p-3.5">Action Event</th>
            <th class="p-3.5">Operational Details</th>
            <th class="p-3.5">Timestamp</th>
          </tr>
        </thead>
        <tbody class="text-gray-700">
          <?php if (count($activityLogsList) > 0): ?>
            <?php foreach (array_reverse($activityLogsList) as $lg): ?>
              <tr class="log-row">
                <td class="p-3.5 font-bold">#<?php echo (int)($lg['id'] ?? 0); ?></td>
                <td class="p-3.5 text-gray-600 font-mono"><?php echo htmlspecialchars((string)($lg['email'] ?? 'System')); ?></td>
                <td class="p-3.5">
                  <span class="badge bg-indigo-50 text-indigo-700 border border-indigo-100 font-bold px-2 py-0.5 rounded">
                    <?php echo htmlspecialchars(strtoupper(str_replace('_', ' ', (string)($lg['action'] ?? '')))); ?>
                  </span>
                </td>
                <td class="p-3.5 text-gray-800"><?php echo htmlspecialchars((string)($lg['details'] ?? '')); ?></td>
                <td class="p-3.5 text-gray-400"><?php echo date('M d, Y H:i:s', strtotime($lg['created_at'] ?? 'now')); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" class="text-center text-gray-400 py-6">No activity logs recorded.</td>
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
    $('#logSearchInput').on('input', function() {
        const val = $(this).val().toLowerCase();
        $('.log-row').each(function() {
            const txt = $(this).text().toLowerCase();
            $(this).toggle(txt.includes(val));
        });
    });
});
</script>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
