<?php
/**
 * tabs/build-website.php
 * Website Builder & Templates Tab
 * Can be included in dashboard.php or accessed directly as a full page
 */
$tabId = 'build-website';
if (!isset($dashboardContext)) {
    $requestedTab = $tabId;
    require_once __DIR__ . '/../dashboard.php';
    exit;
}
?>
<!-- TAB 2: WEBSITE BUILDER PAGE -->
<div id="build-website" class="tab-section <?php echo ($activeTab === 'build-website') ? 'active-tab' : ''; ?>">
  <div class="card border-0 shadow-sm rounded-xl full-page-card">
    <div class="card-header bg-white border-b border-gray-100 py-3.5 px-4 d-flex justify-content-between align-items-center flex-shrink-0">
      <h3 class="text-base font-bold text-gray-800 m-0"><i class="fas fa-layer-group text-primary me-2"></i> Full Page Website Builder & Templates</h3>
    </div>
    <div class="card-body p-4">
      <div class="row g-4" id="templateCardsContainer">
        <?php foreach ($activeTemplates as $tpl): ?>
          <div class="col-md-6 col-lg-3">
            <div class="card border border-gray-100 shadow-sm rounded-xl overflow-hidden h-100 bg-white">
              <img src="<?php echo htmlspecialchars((string)($tpl['preview_img'] ?? '')); ?>" class="w-100 h-36 object-cover" alt="Preview">
              <div class="card-body p-3.5 d-flex flex-column justify-content-between">
                <div>
                  <h4 class="text-sm font-bold text-gray-800 mb-1"><?php echo htmlspecialchars((string)($tpl['name'] ?? '')); ?></h4>
                  <p class="text-2xs text-gray-500 mb-3"><?php echo htmlspecialchars((string)($tpl['description'] ?? '')); ?></p>
                </div>
                <button class="btn btn-primary btn-xs text-xs font-bold rounded-md py-1.5 bg-blue-600 text-white border-0 btn-use-template" data-id="<?php echo (int)($tpl['id'] ?? 0); ?>" data-folder="<?php echo htmlspecialchars((string)($tpl['folder'] ?? '')); ?>" data-name="<?php echo htmlspecialchars((string)($tpl['name'] ?? '')); ?>">Use Template</button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>