<?php
/**
 * tabs/user-db-manager.php
 * Database Schema Manager Tab
 * Can be included in dashboard.php or accessed directly as a full page
 */
$tabId = 'user-db-manager';
if (!isset($dashboardContext)) {
    $requestedTab = $tabId;
    require_once __DIR__ . '/../dashboard.php';
    exit;
}
?>
<!-- TAB 4: DATABASE SCHEMA MANAGER PAGE -->
<div id="user-db-manager" class="tab-section <?php echo ($activeTab === 'user-db-manager') ? 'active-tab' : ''; ?>">
  <div class="card border-0 shadow-sm rounded-xl full-page-card">
    <div class="card-header bg-white border-b border-gray-100 py-3 flex-shrink-0">
      <h3 class="text-base font-bold text-gray-800 m-0"><i class="fas fa-database text-primary me-2"></i> Database Table Schemas & Storage</h3>
    </div>
    <div class="card-body p-4">
      <div class="row g-3 items-end mb-4 flex-wrap flex-shrink-0">
        <div class="col-md-4">
          <label class="block text-2xs uppercase font-bold text-gray-500 mb-1">New Table Name</label>
          <input type="text" id="newTableName" class="form-control text-sm rounded-md border-gray-200" placeholder="Type name..." />
        </div>
        <div class="col-md-2">
          <button class="btn btn-primary btn-sm rounded-md w-full font-bold py-2 bg-blue-600 border-0" id="btnCreateTable">Create Table</button>
        </div>
      </div>
      <div id="schemaTablesContainer" class="space-y-4">
        <!-- Schema tables load via AJAX -->
      </div>
    </div>
  </div>
</div>