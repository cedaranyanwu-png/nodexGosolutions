<?php
/**
 * tabs/monetization.php
 * Monetization & Ad Manager Tab
 * Can be included in dashboard.php or accessed directly as a full page
 */
$tabId = 'monetization';
if (!isset($dashboardContext)) {
    $requestedTab = $tabId;
    require_once __DIR__ . '/../dashboard.php';
    exit;
}
?>
<!-- TAB 5: MONETIZATION PAGE -->
<div id="monetization" class="tab-section <?php echo ($activeTab === 'monetization') ? 'active-tab' : ''; ?>">
  <div class="card border-0 shadow-sm rounded-xl full-page-card">
    <div class="card-header bg-white border-b border-gray-100 py-3 flex-shrink-0">
      <h3 class="text-base font-bold text-gray-800 m-0"><i class="fa-solid fa-rectangle-ad text-primary me-2"></i> Website Monetization & Ad Manager</h3>
    </div>
    <div class="card-body p-4">
      <div class="row g-4">
        <?php foreach ($myWebsites as $web): ?>
          <div class="col-md-4">
            <div class="bg-white border rounded-2xl p-4 shadow-sm">
              <h4 class="text-sm font-bold text-gray-800 mb-1"><?php echo htmlspecialchars((string)($web['name'] ?? '')); ?></h4>
              <p class="text-2xs text-gray-400 font-mono mb-3"><?php echo htmlspecialchars((string)($web['subdomain'] ?? '')); ?>.<?php echo $hostDomain; ?></p>
              <button class="btn btn-primary btn-xs font-bold bg-blue-600 text-white rounded-md w-100 border-0 btn-manage-ads" data-subdomain="<?php echo htmlspecialchars((string)($web['subdomain'] ?? '')); ?>" data-bs-toggle="modal" data-bs-target="#manageAdsModal">Manage Ad Slots</button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>