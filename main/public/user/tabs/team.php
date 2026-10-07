<?php
/**
 * tabs/team.php
 * Workspace Team & Members Tab
 * Can be included in dashboard.php or accessed directly as a full page
 */
$tabId = 'team';
if (!isset($dashboardContext)) {
    $requestedTab = $tabId;
    require_once __DIR__ . '/../dashboard.php';
    exit;
}
?>
<!-- TAB 7: TEAM COLLABORATION PAGE -->
<div id="team" class="tab-section <?php echo ($activeTab === 'team') ? 'active-tab' : ''; ?>">
  <div class="card border-0 shadow-sm rounded-xl full-page-card">
    <div class="card-header bg-white border-b border-gray-100 py-3 d-flex justify-content-between align-items-center flex-shrink-0">
      <h3 class="text-base font-bold text-gray-800 m-0"><i class="fa-solid fa-users text-primary me-2"></i> Workspace Team & Members</h3>
      <button class="btn btn-primary btn-sm bg-blue-600 text-white border-0 font-bold" data-bs-toggle="modal" data-bs-target="#inviteMemberModal">+ Invite Member</button>
    </div>
    <div class="card-body p-4">
      <div class="table-responsive h-100">
        <table class="table table-hover text-xs mb-0">
          <thead class="bg-gray-50 font-bold text-gray-500 sticky-top">
            <tr>
              <th class="p-3">Member Email</th>
              <th class="p-3">Role</th>
              <th class="p-3">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td class="p-3 font-semibold"><?php echo htmlspecialchars((string)($user['email'] ?? '')); ?></td>
              <td class="p-3"><span class="badge bg-primary">Owner</span></td>
              <td class="p-3"><span class="badge bg-success">Active</span></td>
            </tr>
            <?php foreach ($workspaceMembers as $m): ?>
              <tr>
                <td class="p-3"><?php echo htmlspecialchars((string)($m['member_email'] ?? '')); ?></td>
                <td class="p-3"><span class="badge bg-secondary"><?php echo htmlspecialchars((string)($m['role'] ?? 'Member')); ?></span></td>
                <td class="p-3"><span class="badge bg-info">Invited</span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>