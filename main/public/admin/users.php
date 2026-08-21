<?php
/**
 * Responsive administrator user directory.
 *
 * The table is intentionally rendered with semantic Bootstrap markup and
 * enhanced by DataTables for pagination, searching, and responsive scrolling.
 * Each row opens one compact Bootstrap action modal so mobile users do not
 * need to interact with a crowded action column.
 */
declare(strict_types=1);

$pageTitle = 'User Directory';
$pageSubtitle = 'Review profiles and manage approved account actions.';
require_once __DIR__ . '/admin_header.php';

$usersList = $conn->select('users') ?: [];
$roleOptions = ['tenant','admin','manager','support','moderator','financial','marketing_head'];
$avatarRoot = '/uploads/avatars/';
?>

<section class="card border-0 shadow-sm rounded-4 overflow-hidden">
  <div class="card-header bg-white border-0 p-3 p-md-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
      <div><h3 class="h5 fw-bold mb-1"><i class="fa-solid fa-users text-primary me-2"></i>Registered Users</h3><p class="text-muted small mb-0">Use the action menu to view a complete profile or manage the account.</p></div>
      <a class="btn btn-primary btn-sm rounded-pill" href="/admin/profile"><i class="fa-solid fa-user-gear me-1"></i>My profile</a>
    </div>
  </div>
  <div class="card-body p-3 p-md-4">
    <div class="table-responsive">
      <table class="table table-hover align-middle w-100" id="usersTable">
        <thead class="table-light"><tr><th>ID</th><th>User</th><th>Role</th><th>Status</th><th>Joined</th><th class="text-end">Action</th></tr></thead>
        <tbody>
        <?php foreach (array_reverse($usersList) as $u):
          $uId = (int)($u['id'] ?? 0);
          $uNameRaw = (string)($u['fullname'] ?? 'Unnamed user');
          $uEmailRaw = (string)($u['email'] ?? '');
          $uRole = strtolower((string)($u['role'] ?? 'tenant'));
          $uStatus = strtolower((string)($u['status'] ?? 'active'));
          $isProtected = in_array($uRole, ['super admin','superadmin'], true) || $uEmailRaw === 'admin@nodexplatform.com.ng';
          $avatar = trim((string)($u['avatar'] ?? ''));
          $avatarUrl = $avatar !== '' ? $avatar : '';
        ?>
          <tr>
            <td class="fw-semibold">#<?php echo $uId; ?></td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <?php if ($avatarUrl): ?><img src="<?php echo htmlspecialchars($avatarUrl); ?>" class="profile-avatar" alt="Profile picture of <?php echo htmlspecialchars($uNameRaw); ?>"><?php else: ?><div class="profile-avatar bg-primary text-white d-flex align-items-center justify-content-center fw-bold"><?php echo htmlspecialchars(strtoupper(substr($uNameRaw, 0, 1))); ?></div><?php endif; ?>
                <div class="min-w-0"><div class="fw-semibold text-truncate" style="max-width:220px"><?php echo htmlspecialchars($uNameRaw); ?></div><div class="small text-muted text-truncate" style="max-width:220px"><?php echo htmlspecialchars($uEmailRaw); ?></div></div>
              </div>
            </td>
            <td><span class="badge text-bg-light border text-uppercase"><?php echo htmlspecialchars(str_replace('_',' ',$uRole)); ?></span></td>
            <td><span class="badge rounded-pill text-bg-<?php echo $uStatus === 'active' ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars(ucfirst($uStatus)); ?></span></td>
            <td data-order="<?php echo htmlspecialchars((string)($u['created_at'] ?? '')); ?>"><?php echo htmlspecialchars(date('M d, Y', strtotime((string)($u['created_at'] ?? 'now')))); ?></td>
            <td class="text-end"><button type="button" class="btn btn-outline-primary btn-sm rounded-pill js-user-actions" data-bs-toggle="modal" data-bs-target="#userActionModal" data-user='<?php echo htmlspecialchars(json_encode(['id'=>$uId,'name'=>$uNameRaw,'email'=>$uEmailRaw,'role'=>$uRole,'status'=>$uStatus,'joined'=>$u['created_at'] ?? '', 'avatar'=>$avatarUrl, 'protected'=>$isProtected], JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8'); ?>'><i class="fa-solid fa-ellipsis"></i><span class="d-none d-sm-inline ms-1">Actions</span></button></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<div class="modal fade" id="userActionModal" tabindex="-1" aria-labelledby="userActionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title h6 fw-bold" id="userActionModalLabel">User actions</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body"><div id="userModalProfile" class="text-center mb-3"></div><div class="d-grid gap-2"><button class="btn btn-primary" id="modalViewProfile"><i class="fa-solid fa-eye me-1"></i>View profile</button><button class="btn btn-outline-secondary" id="modalEditRole"><i class="fa-solid fa-user-tag me-1"></i>Assign role</button><button class="btn btn-outline-warning" id="modalToggleStatus"><i class="fa-solid fa-power-off me-1"></i>Toggle status</button><button class="btn btn-outline-danger" id="modalDeleteUser"><i class="fa-solid fa-trash me-1"></i>Delete user</button></div></div>
  </div></div>
</div>

<div class="modal fade" id="userProfileModal" tabindex="-1" aria-labelledby="userProfileModalLabel" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title h6 fw-bold" id="userProfileModalLabel">User profile</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body" id="userProfileContent"></div></div></div></div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
/* Keep page-specific behavior isolated so the shared admin shell stays reusable. */
$(function(){
  const table = new DataTable('#usersTable', { responsive: true, pageLength: 10, order: [[0, 'desc']], language: { emptyTable: 'No users found.' } });
  let selected = null;
  const alertBox = (title, icon='info') => window.Swal ? Swal.fire({title, icon, confirmButtonColor:'#0d6efd'}) : window.alert(title);
  const avatar = u => u.avatar ? `<img src="${u.avatar}" class="profile-avatar mb-2" alt="Profile picture">` : `<div class="profile-avatar bg-primary text-white d-inline-flex align-items-center justify-content-center fw-bold mb-2">${(u.name||'U').charAt(0).toUpperCase()}</div>`;
  const setButtons = () => { const locked = !selected || selected.protected; $('#modalEditRole,#modalToggleStatus,#modalDeleteUser').prop('disabled', locked); };
  $(document).on('click','.js-user-actions',function(){ selected = $(this).data('user'); $('#userActionModalLabel').text('Actions: '+selected.name); $('#userModalProfile').html(avatar(selected)+`<div class="fw-semibold">${selected.name}</div><div class="small text-muted">${selected.email}</div><span class="badge text-bg-light border mt-2">${selected.role}</span>`); setButtons(); });
  $('#modalViewProfile').on('click',function(){ if(!selected)return; bootstrap.Modal.getOrCreateInstance(document.getElementById('userActionModal')).hide(); $('#userProfileContent').html(`<div class="text-center">${avatar(selected)}<h5>${selected.name}</h5><p class="text-muted mb-1">${selected.email}</p></div><dl class="row small mt-3 mb-0"><dt class="col-5">User ID</dt><dd class="col-7">#${selected.id}</dd><dt class="col-5">Role</dt><dd class="col-7">${selected.role}</dd><dt class="col-5">Status</dt><dd class="col-7">${selected.status}</dd><dt class="col-5">Joined</dt><dd class="col-7">${selected.joined||'-'}</dd></dl>`); bootstrap.Modal.getOrCreateInstance(document.getElementById('userProfileModal')).show(); });
  $('#modalEditRole').on('click',function(){ if(!selected)return; const role = prompt('Approved role (tenant, admin, manager, support, moderator, financial, marketing_head):', selected.role); if(!role)return; $.post('/php/admin_action.php',{action:'toggle_role',user_id:selected.id,role:role},function(r){alertBox(r.message,r.success?'success':'error').then(()=>{if(r.success)location.reload();});},'json').fail(()=>alertBox('Unable to update role.','error')); });
  $('#modalToggleStatus').on('click',function(){ if(!selected)return; $.post('/php/admin_action.php',{action:'toggle_status',user_id:selected.id},function(r){alertBox(r.message,r.success?'success':'error').then(()=>{if(r.success)location.reload();});},'json').fail(()=>alertBox('Unable to update status.','error')); });
  $('#modalDeleteUser').on('click',function(){ if(!selected)return; Swal.fire({title:'Delete this user?',text:'This action cannot be undone.',icon:'warning',showCancelButton:true,confirmButtonText:'Delete',confirmButtonColor:'#dc3545'}).then(result=>{if(!result.isConfirmed)return;$.post('/php/admin_action.php',{action:'delete_user',user_id:selected.id},function(r){alertBox(r.message,r.success?'success':'error').then(()=>{if(r.success)location.reload();});},'json');}); });
});
</script>
<?php require_once __DIR__ . '/admin_footer.php'; ?>
