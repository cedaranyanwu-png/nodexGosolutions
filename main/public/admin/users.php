<?php
/**
 * users.php
 *
 * Standalone User Directory Management page.
 */

declare(strict_types=1);

$pageTitle = 'User Directory';
$pageSubtitle = 'Manage user profiles, toggle account statuses, adjust roles, and review details.';

require_once __DIR__ . '/admin_header.php';

$usersList = $conn->select('users') ?: [];
?>

<div class="card border-0 shadow-sm rounded-xl">
  <div class="card-header bg-white border-b border-gray-100 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h3 class="text-base font-bold text-gray-800 m-0"><i class="fas fa-users text-primary me-2"></i> System Registered Users</h3>
    <input type="text" id="usersSearchInput" class="form-control form-control-sm text-xs rounded-md" placeholder="Search users by name, email..." style="width: 220px;">
  </div>
  <div class="card-body p-0">
    <div id="usersActionFeedback" class="alert d-none text-xs rounded-lg p-2.5 m-3" role="alert"></div>
    <div class="table-responsive">
      <table class="table table-hover mb-0 text-xs" id="usersTable">
        <thead class="bg-gray-50 text-gray-600 font-semibold">
          <tr>
            <th class="p-3.5">ID</th>
            <th class="p-3.5">User Profile</th>
            <th class="p-3.5">Assigned Role</th>
            <th class="p-3.5">Status</th>
            <th class="p-3.5">Joined Date</th>
            <th class="p-3.5 text-end">Actions</th>
          </tr>
        </thead>
        <tbody class="text-gray-700" id="usersTableBody">
          <?php if (count($usersList) > 0): ?>
            <?php foreach (array_reverse($usersList) as $u):
              $uId = (int)($u['id'] ?? 0);
              $uName = htmlspecialchars((string)($u['fullname'] ?? ''));
              $uEmail = htmlspecialchars((string)($u['email'] ?? ''));
              $uRole = strtolower((string)($u['role'] ?? 'tenant'));
              $uStatus = strtolower((string)($u['status'] ?? 'active'));
            ?>
              <tr class="user-row">
                <td class="p-3.5 font-bold">#<?php echo $uId; ?></td>
                <td class="p-3.5">
                  <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-blue-100 text-blue-600 font-bold d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                      <?php echo strtoupper(substr($uName ?: 'U', 0, 1)); ?>
                    </div>
                    <div>
                      <span class="block font-semibold text-gray-800"><?php echo $uName; ?></span>
                      <span class="block text-2xs text-gray-400 font-mono"><?php echo $uEmail; ?></span>
                    </div>
                  </div>
                </td>
                <td class="p-3.5">
                  <span class="badge bg-blue-50 text-blue-700 border border-blue-100 uppercase px-2 py-1 rounded font-bold" style="font-size: 9px;">
                    <?php echo htmlspecialchars(strtoupper($uRole)); ?>
                  </span>
                </td>
                <td class="p-3.5">
                  <?php if ($uStatus === 'active'): ?>
                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill font-bold px-2 py-0.5">Active</span>
                  <?php else: ?>
                    <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill font-bold px-2 py-0.5">Suspended</span>
                  <?php endif; ?>
                </td>
                <td class="p-3.5 text-gray-400"><?php echo date('M d, Y', strtotime($u['created_at'] ?? 'now')); ?></td>
                <td class="p-3.5 text-end">
                  <button class="btn btn-xs btn-outline-primary rounded-md me-1 btn-view-user-details"
                    data-id="<?php echo $uId; ?>"
                    data-fullname="<?php echo $uName; ?>"
                    data-email="<?php echo $uEmail; ?>"
                    data-role="<?php echo strtoupper($uRole); ?>"
                    data-status="<?php echo strtoupper($uStatus); ?>"
                    data-joined="<?php echo htmlspecialchars($u['created_at'] ?? ''); ?>">
                    <i class="fa-solid fa-eye me-1"></i> Details
                  </button>
                  <button class="btn btn-xs btn-outline-warning rounded-md me-1 btn-toggle-user-status" data-id="<?php echo $uId; ?>">
                    <i class="fa-solid fa-power-off me-1"></i> <?php echo ($uStatus === 'active') ? 'Suspend' : 'Unsuspend'; ?>
                  </button>
                  <button class="btn btn-xs btn-outline-danger rounded-md btn-delete-user" data-id="<?php echo $uId; ?>">
                    <i class="fa-solid fa-trash me-1"></i> Delete
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="text-center text-gray-400 py-6">No users found in directory.</td>
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
    $('#usersSearchInput').on('input', function() {
        const val = $(this).val().toLowerCase();
        $('.user-row').each(function() {
            const txt = $(this).text().toLowerCase();
            $(this).toggle(txt.includes(val));
        });
    });

    $('.btn-toggle-user-status').on('click', function() {
        const userId = $(this).data('id');
        if (!confirm('Toggle account status for user #' + userId + '?')) return;
        $.ajax({
            url: '/php/admin_action.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'toggle_status', user_id: userId },
            success: function(res) {
                alert(res.message);
                location.reload();
            }
        });
    });

    $('.btn-delete-user').on('click', function() {
        const userId = $(this).data('id');
        if (!confirm('Are you sure you want to permanently delete user #' + userId + '?')) return;
        $.ajax({
            url: '/php/admin_action.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'delete_user', user_id: userId },
            success: function(res) {
                alert(res.message);
                location.reload();
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
