<?php
/**
 * websites.php
 *
 * Standalone Website Workspace Manager page.
 */

declare(strict_types=1);

$pageTitle = 'Website Manager';
$pageSubtitle = 'Audit, monitor, suspend, or manage multi-tenant hosting subdomains.';

require_once __DIR__ . '/admin_header.php';

$conn->createTable('websites');
$websitesList = $conn->select('websites') ?: [];
?>

<div class="card border-0 shadow-sm rounded-xl">
  <div class="card-header bg-white border-b border-gray-100 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h3 class="text-base font-bold text-gray-800 m-0"><i class="fas fa-globe text-primary me-2"></i> Multi-Tenant Hosted Subdomains</h3>
    <input type="text" id="webSearchInput" class="form-control form-control-sm text-xs rounded-md" placeholder="Search websites..." style="width: 220px;">
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0 text-xs" id="adminWebsitesTable">
        <thead class="bg-gray-50 text-gray-600 font-semibold">
          <tr>
            <th class="p-3.5">ID</th>
            <th class="p-3.5">Website Name</th>
            <th class="p-3.5">Subdomain Target</th>
            <th class="p-3.5">Owner User ID</th>
            <th class="p-3.5">Status</th>
            <th class="p-3.5">Created Date</th>
            <th class="p-3.5 text-end">Actions</th>
          </tr>
        </thead>
        <tbody class="text-gray-700">
          <?php if (count($websitesList) > 0): ?>
            <?php foreach (array_reverse($websitesList) as $wb):
              $wId = (int)($wb['id'] ?? 0);
              $wSub = htmlspecialchars((string)($wb['subdomain'] ?? ''));
              $wUrl = htmlspecialchars((string)($wb['url'] ?? tenantWebsiteUrl((string)($wb['subdomain'] ?? ''))));
              $isSuspended = (int)($wb['is_suspended'] ?? 0) === 1;
            ?>
              <tr class="web-row">
                <td class="p-3.5 font-bold">#<?php echo $wId; ?></td>
                <td class="p-3.5 font-semibold text-gray-800"><?php echo htmlspecialchars((string)($wb['name'] ?? '')); ?></td>
                <td class="p-3.5 font-mono text-blue-600"><a href="<?php echo $wUrl; ?>" target="_blank" rel="noopener noreferrer" class="hover:underline"><?php echo $wUrl; ?></a></td>
                <td class="p-3.5 text-gray-600">User ID: <?php echo (int)($wb['user_id'] ?? 0); ?></td>
                <td class="p-3.5">
                  <?php if ($isSuspended): ?>
                    <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill font-bold px-2 py-0.5">Suspended</span>
                  <?php else: ?>
                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill font-bold px-2 py-0.5">Active</span>
                  <?php endif; ?>
                </td>
                <td class="p-3.5 text-gray-400"><?php echo date('M d, Y', strtotime($wb['created_at'] ?? 'now')); ?></td>
                <td class="p-3.5 text-end">
                  <a href="<?php echo $wUrl; ?>" target="_blank" rel="noopener noreferrer" class="btn btn-xs btn-outline-primary rounded-md me-1">
                    <i class="fas fa-external-link-alt me-1"></i> Visit
                  </a>
                  <button class="btn btn-xs btn-outline-warning rounded-md me-1 btn-toggle-web-suspend" data-id="<?php echo $wId; ?>">
                    <i class="fas fa-ban me-1"></i> <?php echo $isSuspended ? 'Unsuspend' : 'Suspend'; ?>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="text-center text-gray-400 py-6">No websites hosted yet.</td>
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
    $('#webSearchInput').on('input', function() {
        const val = $(this).val().toLowerCase();
        $('.web-row').each(function() {
            const txt = $(this).text().toLowerCase();
            $(this).toggle(txt.includes(val));
        });
    });

    $('.btn-toggle-web-suspend').on('click', function() {
        const webId = $(this).data('id');
        if (!confirm('Toggle suspension status for website #' + webId + '?')) return;
        $.ajax({
            url: '/php/admin_moderation_action.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'toggle_website_suspension', website_id: webId },
            success: function(res) {
                alert(res.message);
                location.reload();
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
