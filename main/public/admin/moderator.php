<?php
/**
 * moderator.php
 *
 * Standalone Content Moderation & Abuse Reports Queue page.
 */

declare(strict_types=1);

$pageTitle = 'Content Moderation Queue';
$pageSubtitle = 'Review abuse reports, inspect flagged tenant website contents, dismiss reports, or toggle website suspensions.';

require_once __DIR__ . '/admin_header.php';

$conn->createTable('reports');
$reportsList = $conn->select('reports') ?: [];
?>

<div class="card border-0 shadow-sm rounded-xl">
  <div class="card-header bg-white border-b border-gray-100 py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <h3 class="text-base font-bold text-gray-800 m-0"><i class="fas fa-shield-halved text-primary me-2"></i> Content Moderation & Abuse Reports Queue</h3>
    <div class="d-flex align-items-center gap-2">
      <input type="text" id="reportSearchInput" class="form-control form-control-sm text-xs rounded-md" placeholder="Search reports..." style="width: 180px;">
      <select id="reportStatusFilter" class="form-select form-select-sm text-xs rounded-md" style="width: 140px;">
        <option value="all">All Statuses</option>
        <option value="pending">Pending</option>
        <option value="reviewed">Reviewed</option>
        <option value="action_taken">Action Taken</option>
        <option value="dismissed">Dismissed</option>
      </select>
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0 text-xs" id="reportsTable">
        <thead class="bg-gray-50 text-gray-600 font-semibold">
          <tr>
            <th class="p-3.5">ID</th>
            <th class="p-3.5">Target Website</th>
            <th class="p-3.5">Reporter</th>
            <th class="p-3.5">Reason</th>
            <th class="p-3.5">Status</th>
            <th class="p-3.5">Reported Date</th>
            <th class="p-3.5 text-end">Actions</th>
          </tr>
        </thead>
        <tbody class="text-gray-700" id="reportsTableBody">
          <?php if (count($reportsList) > 0): ?>
            <?php foreach (array_reverse($reportsList) as $rp):
              $rStatus = strtolower((string)($rp['status'] ?? 'pending'));
            ?>
              <tr class="report-row" data-status="<?php echo htmlspecialchars($rStatus); ?>">
                <td class="p-3.5 font-bold">#<?php echo (int)($rp['id'] ?? 0); ?></td>
                <td class="p-3.5 font-semibold text-blue-600">
                  <i class="fa-solid fa-globe me-1 text-gray-400"></i><?php echo htmlspecialchars($rp['website_url'] ?? 'Workspace'); ?>
                </td>
                <td class="p-3.5 text-gray-600"><?php echo htmlspecialchars($rp['reporter'] ?? 'Anonymous'); ?></td>
                <td class="p-3.5 font-medium text-gray-800"><?php echo htmlspecialchars($rp['reason'] ?? 'Content Violation'); ?></td>
                <td class="p-3.5">
                  <?php if ($rStatus === 'pending'): ?>
                    <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill font-bold px-2 py-1">Pending Review</span>
                  <?php elseif ($rStatus === 'reviewed'): ?>
                    <span class="badge bg-info bg-opacity-10 text-info rounded-pill font-bold px-2 py-1">Reviewed</span>
                  <?php elseif ($rStatus === 'action_taken'): ?>
                    <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill font-bold px-2 py-1">Action Taken (Suspended)</span>
                  <?php else: ?>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill font-bold px-2 py-1">Dismissed</span>
                  <?php endif; ?>
                </td>
                <td class="p-3.5 text-gray-400"><?php echo date('M d, Y H:i', strtotime($rp['created_at'] ?? 'now')); ?></td>
                <td class="p-3.5 text-end">
                  <button class="btn btn-xs btn-outline-primary rounded-md btn-view-report-details"
                    data-id="<?php echo (int)($rp['id'] ?? 0); ?>"
                    data-url="<?php echo htmlspecialchars($rp['website_url'] ?? ''); ?>"
                    data-reporter="<?php echo htmlspecialchars($rp['reporter'] ?? ''); ?>"
                    data-reason="<?php echo htmlspecialchars($rp['reason'] ?? ''); ?>"
                    data-details="<?php echo htmlspecialchars($rp['details'] ?? ''); ?>"
                    data-status="<?php echo htmlspecialchars($rStatus); ?>"
                    data-website-id="<?php echo (int)($rp['website_id'] ?? 0); ?>">
                    <i class="fa-solid fa-eye me-1"></i> Review
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="text-center text-gray-400 py-8">
                <i class="fas fa-circle-check text-3xl mb-2 text-emerald-400 block"></i>
                No active content abuse reports in queue.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- MODERATION REPORT DETAILS MODAL -->
<div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-xl border-0 shadow-2xl">
      <div class="modal-header border-b border-gray-100 bg-slate-900 text-white rounded-t-xl py-3">
        <h5 class="modal-title font-bold flex items-center text-sm" id="reportModalLabel"><i class="fa-solid fa-shield-halved me-2 text-red-400"></i> Abuse Report Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4 text-xs">
        <input type="hidden" id="reportModalId" value="">
        <input type="hidden" id="reportModalWebsiteId" value="">

        <div class="mb-3">
          <span class="text-gray-400 block font-bold text-2xs uppercase">Target Website:</span>
          <span class="font-bold text-blue-600 text-sm" id="reportModalUrl"></span>
        </div>
        <div class="mb-3">
          <span class="text-gray-400 block font-bold text-2xs uppercase">Reporter:</span>
          <span class="font-medium text-gray-800" id="reportModalReporter"></span>
        </div>
        <div class="mb-3">
          <span class="text-gray-400 block font-bold text-2xs uppercase">Violation Reason:</span>
          <span class="font-bold text-red-600" id="reportModalReason"></span>
        </div>
        <div class="mb-3">
          <span class="text-gray-400 block font-bold text-2xs uppercase">Description / Evidence:</span>
          <div class="bg-gray-50 border border-gray-200 rounded-md p-2.5 mt-1 text-gray-700" id="reportModalDetails"></div>
        </div>
        <div class="mb-3">
          <span class="text-gray-400 block font-bold text-2xs uppercase">Current Status:</span>
          <span class="badge bg-primary px-2.5 py-1 text-xs mt-1" id="reportModalStatusBadge">Pending</span>
        </div>
      </div>
      <div class="modal-footer bg-gray-50 rounded-b-xl border-t border-gray-100 p-3 flex justify-between">
        <button type="button" class="btn btn-sm btn-outline-secondary text-xs rounded-md" id="btnDismissReport"><i class="fa-solid fa-xmark me-1"></i> Dismiss Report</button>
        <button type="button" class="btn btn-sm btn-danger text-xs rounded-md" id="btnToggleSuspendReportSite"><i class="fa-solid fa-ban me-1"></i> Suspend Website</button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function() {
    $(document).on('click', '.btn-view-report-details', function() {
        const id = $(this).data('id');
        const url = $(this).data('url');
        const reporter = $(this).data('reporter');
        const reason = $(this).data('reason');
        const details = $(this).data('details');
        const status = $(this).data('status');
        const websiteId = $(this).data('website-id');

        $('#reportModalId').val(id);
        $('#reportModalWebsiteId').val(websiteId);
        $('#reportModalUrl').text(url);
        $('#reportModalReporter').text(reporter);
        $('#reportModalReason').text(reason);
        $('#reportModalDetails').text(details);
        $('#reportModalStatusBadge').text(status.toUpperCase());

        const reportModal = new bootstrap.Modal(document.getElementById('reportModal'));
        reportModal.show();
    });

    $('#btnDismissReport').on('click', function() {
        const reportId = $('#reportModalId').val();
        $.ajax({
            url: '/php/admin_moderation_action.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'update_status', report_id: reportId, status: 'dismissed' },
            success: function(res) {
                if (res.success) {
                    alert(res.message);
                    location.reload();
                } else {
                    alert(res.message || 'Failed to update report.');
                }
            }
        });
    });

    $('#btnToggleSuspendReportSite').on('click', function() {
        const reportId = $('#reportModalId').val();
        const websiteId = $('#reportModalWebsiteId').val();
        $.ajax({
            url: '/php/admin_moderation_action.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'toggle_website_suspension', report_id: reportId, website_id: websiteId },
            success: function(res) {
                if (res.success) {
                    alert(res.message);
                    location.reload();
                } else {
                    alert(res.message || 'Failed to toggle website suspension.');
                }
            }
        });
    });

    $('#reportSearchInput, #reportStatusFilter').on('input change', function() {
        const search = $('#reportSearchInput').val().toLowerCase();
        const status = $('#reportStatusFilter').val().toLowerCase();

        $('.report-row').each(function() {
            const rowStatus = $(this).data('status').toLowerCase();
            const rowText = $(this).text().toLowerCase();

            const matchesSearch = rowText.includes(search);
            const matchesStatus = (status === 'all' || rowStatus === status);

            if (matchesSearch && matchesStatus) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
