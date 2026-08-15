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
                  <button class="btn btn-xs btn-outline-info rounded-md me-1 btn-change-user-role"
                    data-id="<?php echo $uId; ?>"
                    data-email="<?php echo $uEmail; ?>"
                    data-role="<?php echo $uRole; ?>">
                    <i class="fa-solid fa-user-shield me-1"></i> Change Role
                  </button>
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

<!-- Change Role Modal -->
<div class="modal fade" id="changeRoleModal" tabindex="-1" aria-labelledby="changeRoleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow rounded-xl">
      <div class="modal-header bg-white border-b border-gray-100 py-3">
        <h5 class="modal-title text-sm font-bold text-gray-800" id="changeRoleModalLabel">
          <i class="fa-solid fa-user-shield text-blue-600 me-2"></i>Change User Role
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="changeRoleForm">
        <div class="modal-body p-4 text-xs">
          <input type="hidden" id="modalUserId" name="user_id" value="">
          <div class="mb-3">
            <label class="form-label font-semibold text-gray-700">Target User Email</label>
            <input type="text" id="modalUserEmail" class="form-control text-xs" readonly>
          </div>
          <div class="mb-3">
            <label class="form-label font-semibold text-gray-700">Select Assignable Role</label>
            <select id="modalUserRole" class="form-select text-xs" name="role" required>
              <option value="tenant">TENANT (Standard User)</option>
              <option value="superadmin">SUPER ADMIN</option>
              <option value="admin">ADMIN (Administrator)</option>
              <option value="manager">MANAGER</option>
              <option value="support">SUPPORT</option>
              <option value="moderator">MODERATOR</option>
              <option value="financial">FINANCIAL OFFICER</option>
              <option value="marketing_head">MARKETING HEAD</option>
            </select>
          </div>
        </div>
        <div class="modal-footer bg-gray-50 py-2.5 px-4">
          <button type="button" class="btn btn-sm btn-light rounded-md text-xs" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" id="btnSaveRole" class="btn btn-sm btn-primary rounded-md text-xs font-bold px-3">
            <i class="fa-solid fa-save me-1"></i> Update Role
          </button>
        </div>
      </form>
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

    $('.btn-change-user-role').on('click', function() {
        const uId = $(this).data('id');
        const uEmail = $(this).data('email');
        const uRole = $(this).data('role');

        $('#modalUserId').val(uId);
        $('#modalUserEmail').val(uEmail);
        $('#modalUserRole').val(uRole);

        const modalEl = document.getElementById('changeRoleModal');
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.show();
        } else {
            $(modalEl).modal('show');
        }
    });

    $('#changeRoleForm').on('submit', function(e) {
        e.preventDefault();
        const uId = $('#modalUserId').val();
        const newRole = $('#modalUserRole').val();

        $('#btnSaveRole').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Updating...');

        $.ajax({
            url: '/php/admin_action.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'toggle_role',
                user_id: uId,
                role: newRole
            },
            success: function(res) {
                $('#btnSaveRole').prop('disabled', false).html('<i class="fa-solid fa-save me-1"></i> Update Role');
                alert(res.message);
                if (res.success) {
                    location.reload();
                }
            },
            error: function(xhr) {
                $('#btnSaveRole').prop('disabled', false).html('<i class="fa-solid fa-save me-1"></i> Update Role');
                alert(xhr.responseJSON ? xhr.responseJSON.message : 'Failed to update role.');
            }
        });
    });

    $('.btn-view-user-details').on('click', function() {
        const id = $(this).data('id');
        const name = $(this).data('fullname');
        const email = $(this).data('email');
        const role = $(this).data('role');
        const status = $(this).data('status');
        const joined = $(this).data('joined');
        alert(`User #${id}\nName: ${name}\nEmail: ${email}\nRole: ${role}\nStatus: ${status}\nJoined: ${joined}`);
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
