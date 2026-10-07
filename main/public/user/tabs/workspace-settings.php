<?php
/**
 * tabs/workspace-settings.php
 * Workspace Settings Tab
 * Can be included in dashboard.php or accessed directly as a full page
 */
$tabId = 'workspace-settings';
if (!isset($dashboardContext)) {
    $requestedTab = $tabId;
    require_once __DIR__ . '/../dashboard.php';
    exit;
}
?>
<!-- TAB 8: WORKSPACE SETTINGS PAGE -->
<div id="workspace-settings" class="tab-section <?php echo ($activeTab === 'workspace-settings') ? 'active-tab' : ''; ?>">
  <div class="card border-0 shadow-sm rounded-xl full-page-card">
    <div class="card-header bg-white border-b border-gray-100 py-3 flex-shrink-0">
      <h3 class="text-base font-bold text-gray-800 m-0"><i class="fa-solid fa-sliders text-primary me-2"></i> Workspace Settings</h3>
    </div>
    <div class="card-body p-4">
      <form id="updateWorkspaceForm" class="max-w-md">
        <input type="hidden" name="workspace_id" value="<?php echo $activeWorkspaceId; ?>" />
        <div class="mb-3">
          <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Workspace Name</label>
          <input type="text" name="workspace_name" class="form-control text-xs rounded-lg p-2.5 border-gray-200" value="<?php echo htmlspecialchars((string)($activeWorkspace['name'] ?? '')); ?>" required />
        </div>
        <button type="submit" class="btn btn-primary bg-blue-600 text-white font-bold text-xs py-2 px-4 rounded-lg border-0">Save Changes</button>
      </form>
    </div>
  </div>
</div>