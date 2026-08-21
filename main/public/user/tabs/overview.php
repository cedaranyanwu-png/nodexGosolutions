<?php
/**
 * tabs/overview.php
 * Overview Tab - Stats cards and websites directory
 * Can be included in dashboard.php or accessed directly as a full page
 */
$tabId = 'overview';
if (!isset($dashboardContext)) {
    $requestedTab = $tabId;
    require_once __DIR__ . '/../dashboard.php';
    exit;
}
?>
<!-- TAB 1: OVERVIEW PAGE -->
<div id="overview" class="tab-section <?php echo ($activeTab === 'overview') ? 'active-tab' : ''; ?>">
  <div class="row mb-3 flex-shrink-0">
    <div class="col-lg-3 col-md-6 col-12 mb-3 mb-lg-0">
      <div class="bg-white border border-gray-100 shadow-sm rounded-xl p-4 d-flex align-items-center justify-content-between hover-translate">
        <div>
          <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Websites</span>
          <span class="text-2xl font-extrabold text-gray-800 d-block"><?php echo $websitesCount; ?></span>
        </div>
        <div class="w-12 h-12 bg-primary bg-opacity-10 text-primary rounded-xl d-flex align-items-center justify-content-center text-xl"><i class="fas fa-globe"></i></div>
      </div>
    </div>

    <div class="col-lg-3 col-md-6 col-12 mb-3 mb-lg-0">
      <div class="bg-white border border-gray-100 shadow-sm rounded-xl p-4 d-flex align-items-center justify-content-between hover-translate">
        <div>
          <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Storage</span>
          <span class="text-2xl font-extrabold text-gray-800 d-block"><?php echo $formattedStorage; ?></span>
        </div>
        <div class="w-12 h-12 bg-info bg-opacity-10 text-info rounded-xl d-flex align-items-center justify-content-center text-xl"><i class="fas fa-hdd"></i></div>
      </div>
    </div>

    <div class="col-lg-3 col-md-6 col-12 mb-3 mb-lg-0">
      <div class="bg-white border border-gray-100 shadow-sm rounded-xl p-4 d-flex align-items-center justify-content-between hover-translate">
        <div>
          <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Traffic</span>
          <span class="text-2xl font-extrabold text-gray-800 d-block"><?php echo $formattedTraffic; ?></span>
        </div>
        <div class="w-12 h-12 bg-purple-500 bg-opacity-10 text-purple-600 rounded-xl d-flex align-items-center justify-content-center text-xl"><i class="fas fa-chart-line"></i></div>
      </div>
    </div>

    <div class="col-lg-3 col-md-6 col-12 mb-3 mb-lg-0">
      <div class="bg-white border border-gray-100 shadow-sm rounded-xl p-4 d-flex align-items-center justify-content-between hover-translate">
        <div>
          <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Subscription</span>
          <span class="text-sm font-extrabold block text-gray-800">
            <?php if ($subscriptionStatus === 'trial'): ?>
              <span class="text-blue-600">● FREE TRIAL</span>
            <?php elseif ($subscriptionStatus === 'active'): ?>
              <span class="text-green-600">● ACTIVE</span>
            <?php else: ?>
              <span class="text-red-600">● EXPIRED</span>
            <?php endif; ?>
          </span>
        </div>
        <div class="w-12 h-12 bg-warning bg-opacity-10 text-warning rounded-xl d-flex align-items-center justify-content-center text-xl"><i class="fas fa-credit-card"></i></div>
      </div>
    </div>
  </div>

  <!-- Websites Directory -->
  <div class="card border-0 shadow-sm rounded-xl full-page-card">
    <div class="card-header bg-white border-b border-gray-100 py-3 d-flex justify-content-between align-items-center flex-shrink-0">
      <h3 class="text-base font-bold text-gray-800 m-0"><i class="fas fa-globe text-primary me-2"></i> Subdomain Websites</h3>
      <button class="btn btn-sm btn-primary bg-blue-600 text-white font-bold border-0 rounded-lg text-xs py-2 px-3" data-bs-toggle="modal" data-bs-target="#uploadWebsiteModal">+ Create Website</button>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive h-100">
        <table class="table table-hover mb-0 text-xs">
          <thead class="bg-gray-50 text-gray-500 font-bold sticky-top">
            <tr>
              <th class="p-3.5">Website Name</th>
              <th class="p-3.5">Subdomain Target</th>
              <th class="p-3.5">Folder Path</th>
              <th class="p-3.5 text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($websitesCount > 0): ?>
              <?php foreach ($myWebsites as $web):
                  $wSubRaw = (string)($web['subdomain'] ?? '');
                  $wSub = htmlspecialchars($wSubRaw);
                  $wUrl = htmlspecialchars((string)($web['url'] ?? tenantWebsiteUrl($wSubRaw)));
              ?>
                <tr>
                  <td class="p-3.5 font-semibold text-gray-800"><?php echo htmlspecialchars((string)($web['name'] ?? '')); ?></td>
                  <td class="p-3.5 font-mono text-blue-600"><a href="<?php echo $wUrl; ?>" target="_blank" rel="noopener noreferrer"><?php echo $wUrl; ?></a></td>
                  <td class="p-3.5"><span class="badge bg-secondary bg-opacity-10 text-secondary">/public/<?php echo $wSub; ?>/</span></td>
                  <td class="p-3.5 text-right">
                    <button class="btn btn-xs btn-outline-primary rounded-md btn-browse-files" data-subdomain="<?php echo $wSub; ?>">Manage Files</button>
                    <a href="<?php echo $wUrl; ?>" target="_blank" rel="noopener noreferrer" class="btn btn-xs btn-light border rounded-md">Visit</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="4" class="text-center text-gray-400 py-4">No websites active yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>