<?php
/**
 * teams.php
 *
 * Standalone Teams Management page.
 */

declare(strict_types=1);

$pageTitle = 'Teams Management';
$pageSubtitle = 'Create, organize, and manage internal staff teams and team leaders.';

require_once __DIR__ . '/admin_header.php';

$conn->createTable('teams');
$teamsList = $conn->select('teams') ?: [];
?>

<div class="card border-0 shadow-sm rounded-xl">
  <div class="card-header bg-white border-b border-gray-100 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h3 class="text-base font-bold text-gray-800 m-0"><i class="fas fa-users-gear text-primary me-2"></i> Internal Staff Teams</h3>
    <input type="text" id="teamsSearchInput" class="form-control form-control-sm text-xs rounded-md" placeholder="Search teams..." style="width: 220px;">
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0 text-xs" id="adminTeamsTable">
        <thead class="bg-gray-50 text-gray-600 font-semibold">
          <tr>
            <th class="p-3.5">ID</th>
            <th class="p-3.5">Team Name</th>
            <th class="p-3.5">Department</th>
            <th class="p-3.5">Status</th>
            <th class="p-3.5">Created Date</th>
          </tr>
        </thead>
        <tbody class="text-gray-700">
          <?php if (count($teamsList) > 0): ?>
            <?php foreach (array_reverse($teamsList) as $tm):
              $tmId = (int)($tm['id'] ?? 0);
              $tmName = htmlspecialchars((string)($tm['name'] ?? ''));
              $tmType = htmlspecialchars((string)($tm['type'] ?? 'General'));
              $tmStatus = strtolower((string)($tm['status'] ?? 'active'));
            ?>
              <tr class="team-row">
                <td class="p-3.5 font-bold">#<?php echo $tmId; ?></td>
                <td class="p-3.5 font-semibold text-gray-800"><?php echo $tmName; ?></td>
                <td class="p-3.5 text-gray-600"><?php echo $tmType; ?></td>
                <td class="p-3.5">
                  <span class="badge bg-success bg-opacity-10 text-success rounded-pill font-bold px-2.5 py-1">Active</span>
                </td>
                <td class="p-3.5 text-gray-400"><?php echo date('M d, Y', strtotime($tm['created_at'] ?? 'now')); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" class="text-center text-gray-400 py-6">No staff teams created yet.</td>
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
    $('#teamsSearchInput').on('input', function() {
        const val = $(this).val().toLowerCase();
        $('.team-row').each(function() {
            const txt = $(this).text().toLowerCase();
            $(this).toggle(txt.includes(val));
        });
    });
});
</script>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
