<?php
/**
 * tabs/analytics.php
 * Analytics Directory Tab
 * Can be included in dashboard.php or accessed directly as a full page
 */
$tabId = 'analytics';
if (!isset($dashboardContext)) {
    $requestedTab = $tabId;
    require_once __DIR__ . '/../dashboard.php';
    exit;
}
?>
<!-- TAB 6: ANALYTICS PAGE -->
<div id="analytics" class="tab-section <?php echo ($activeTab === 'analytics') ? 'active-tab' : ''; ?>">
  <div class="card border-0 shadow-sm rounded-xl full-page-card">
    <div class="card-header bg-white border-b border-gray-100 py-3 flex-shrink-0">
      <h3 class="text-base font-bold text-gray-800 m-0"><i class="fas fa-chart-line text-primary me-2"></i> Analytics Directory</h3>
    </div>
    <div class="card-body p-4">
      <p class="text-xs text-gray-500 mb-3 flex-shrink-0">Track real-time visitors, impressions, and engagement metrics across subdomains.</p>
      <div class="row g-3">
        <div class="col-md-6">
          <div class="border rounded-xl p-3 bg-gray-50">
            <span class="text-2xs font-bold text-gray-400 uppercase">Total Impressions</span>
            <h2 class="text-xl font-bold text-gray-800 m-0">12,450</h2>
          </div>
        </div>
        <div class="col-md-6">
          <div class="border rounded-xl p-3 bg-gray-50">
            <span class="text-2xs font-bold text-gray-400 uppercase">Unique Visitors</span>
            <h2 class="text-xl font-bold text-gray-800 m-0">3,890</h2>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>