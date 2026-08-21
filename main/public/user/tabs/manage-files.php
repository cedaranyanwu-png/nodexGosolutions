<?php
/**
 * tabs/manage-files.php
 *
 * Full Web Filesystem Manager Console Tab
 * Provides folder browsing, file & folder creation, file uploads, text/code editing,
 * file rename, deletion, and path-traversal bounded security.
 */
$tabId = 'manage-files';
if (!isset($dashboardContext)) {
    $requestedTab = $tabId;
    require_once __DIR__ . '/../dashboard.php';
    exit;
}
?>

<style>
.fm-fullscreen-workspace.active-tab{position:fixed;inset:0;z-index:2000;background:#f8fafc;padding:18px;overflow:auto}.fm-fullscreen-workspace.active-tab .full-page-card{height:calc(100vh - 36px);min-height:560px}.fm-workspace-rail{width:220px;background:#0f172a;color:#e2e8f0;padding:14px}.fm-workspace-rail .btn{width:100%;text-align:left;margin-bottom:8px}.fm-workspace-rail .btn i{margin-right:8px}
</style>
<div id="manage-files" class="tab-section fm-fullscreen-workspace <?php echo ($activeTab === 'manage-files') ? 'active-tab' : ''; ?>">
  <div class="card border-0 shadow-sm rounded-2xl full-page-card overflow-hidden">

    <!-- Header with Website Selector & Action Bar -->
    <div class="card-header bg-white border-b border-gray-200 p-4 flex-shrink-0">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <h3 class="text-base font-bold text-gray-800 m-0"><i class="fas fa-folder-tree text-blue-600 me-2"></i> File Manager</h3>
          <select id="fmWebsiteSelect" class="form-select text-xs font-bold rounded-xl border-gray-200 py-1.5 px-3 max-w-xs">
            <?php if (!empty($myWebsites)): ?>
              <?php foreach ($myWebsites as $web): ?>
                <option value="<?php echo htmlspecialchars((string)$web['subdomain']); ?>">
                  <?php echo htmlspecialchars((string)$web['name']); ?> (<?php echo htmlspecialchars((string)$web['subdomain']); ?>)
                </option>
              <?php endforeach; ?>
            <?php else: ?>
              <option value="">No website workspaces created yet</option>
            <?php endif; ?>
          </select>
        </div>

          <div class="d-flex flex-wrap gap-2 align-items-center">
          <button type="button" class="btn btn-sm btn-light text-xs font-bold rounded-xl border border-gray-200" id="fmBtnCloseWorkspace"><i class="fas fa-xmark me-1"></i> Close</button>
          <button type="button" class="btn btn-sm btn-light text-xs font-bold rounded-xl border border-gray-200" id="fmBtnNewFile">
            <i class="fas fa-file-circle-plus text-blue-600 me-1"></i> New File
          </button>
          <button type="button" class="btn btn-sm btn-light text-xs font-bold rounded-xl border border-gray-200" id="fmBtnNewFolder">
            <i class="fas fa-folder-plus text-amber-500 me-1"></i> New Folder
          </button>
          <button type="button" class="btn btn-sm btn-light text-xs font-bold rounded-xl border border-gray-200" id="fmBtnRename"><i class="fas fa-i-cursor me-1"></i> Rename</button>
          <button type="button" class="btn btn-sm btn-light text-xs font-bold rounded-xl border border-gray-200" id="fmBtnCopy"><i class="fas fa-copy me-1"></i> Copy</button>
          <button type="button" class="btn btn-sm btn-light text-xs font-bold rounded-xl border border-gray-200" id="fmBtnMove"><i class="fas fa-arrows-up-down-left-right me-1"></i> Move</button>
          <label class="btn btn-sm btn-primary bg-blue-600 text-white font-bold text-xs rounded-xl border-0 m-0 cursor-pointer">
            <i class="fas fa-cloud-arrow-up me-1"></i> Upload / ZIP
            <input type="file" id="fmFileInput" class="d-none" multiple accept=".zip,.html,.htm,.css,.js,.json,.txt,.jpg,.jpeg,.png,.gif,.svg,.webp" />
          </label>
        </div>
      </div>

      <!-- Current Path Breadcrumbs -->
      <div class="d-flex align-items-center gap-2 mt-3 pt-2 border-t border-gray-100 text-xs text-gray-600">
        <span class="font-bold text-gray-400">Path:</span>
        <div id="fmBreadcrumbs" class="d-flex align-items-center gap-1 font-mono text-xs">
          <span class="badge bg-blue-50 text-blue-700 px-2 py-1 rounded cursor-pointer fm-crumb-root">/</span>
        </div>
      </div>
    </div>

    <!-- Main Explorer & Code Editor Split Pane -->
    <div class="card-body p-0 bg-gray-50 flex-grow d-flex min-h-0">
      <div class="row g-0 w-100 h-100">
        <aside class="col-auto fm-workspace-rail d-none d-lg-block"><div class="small text-uppercase fw-bold text-slate-400 mb-3">Workspace tools</div><button type="button" class="btn btn-sm btn-dark" id="fmBtnRoot"><i class="fas fa-house"></i> Root</button><button type="button" class="btn btn-sm btn-dark" id="fmBtnRefreshRail"><i class="fas fa-rotate"></i> Refresh tree</button><button type="button" class="btn btn-sm btn-dark" id="fmBtnUploadZipRail"><i class="fas fa-file-zipper"></i> Upload ZIP</button><div class="small text-slate-400 mt-3">Every operation is limited to the selected tenant workspace.</div></aside>

        <!-- Left: File List Column -->
        <div class="col-md-5 col-lg-4 border-r border-gray-200 bg-white d-flex flex-column h-100 min-h-0">
          <div class="p-3 border-b border-gray-100 d-flex justify-content-between align-items-center flex-shrink-0">
            <span class="text-2xs uppercase font-extrabold tracking-wider text-gray-400">Directory Items</span>
            <button class="btn btn-xs btn-link text-gray-400 hover:text-gray-600 p-0" id="fmBtnRefresh"><i class="fas fa-rotate"></i> Refresh</button>
          </div>

          <div class="overflow-y-auto flex-grow p-2" id="fmItemsContainer">
            <div class="text-center text-gray-400 py-10 text-xs"><i class="fas fa-spinner fa-spin me-2"></i> Loading workspace files...</div>
          </div>
        </div>

        <!-- Right: Code / Text Editor Panel -->
        <div class="col-md-7 col-lg-8 bg-slate-900 text-slate-100 d-flex flex-column h-100 min-h-0">
          <div id="fmEditorActivePanel" class="d-none flex-column h-100 p-4 min-h-0">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-b border-slate-800 flex-shrink-0 flex-wrap gap-2">
              <div class="d-flex align-items-center gap-2">
                <i class="fas fa-file-code text-blue-400 text-sm"></i>
                <span class="font-mono text-xs font-bold text-blue-200" id="fmActiveFileName">index.html</span>
              </div>

              <!-- Mode Switcher -->
              <div class="btn-group btn-group-sm bg-slate-800 p-0.5 rounded-lg border border-slate-700" role="group">
                <button type="button" class="btn btn-xs text-2xs font-bold text-white bg-blue-600 rounded-md border-0 py-1 px-2.5" id="fmBtnModeCode"><i class="fas fa-code me-1"></i> Code</button>
                <button type="button" class="btn btn-xs text-2xs font-bold text-slate-400 hover:text-white rounded-md border-0 py-1 px-2.5" id="fmBtnModeVisual"><i class="fas fa-wand-magic-sparkles me-1"></i> Visual (GrapesJS)</button>
              </div>

              <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-xs btn-danger text-2xs py-1 px-2.5 rounded-lg border-0" id="fmBtnDeleteActive"><i class="fas fa-trash me-1"></i> Delete</button>
                <button type="button" class="btn btn-xs btn-primary bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs py-1.5 px-3 rounded-xl border-0" id="fmBtnSaveFile"><i class="fas fa-floppy-disk me-1"></i> Save Changes</button>
              </div>
            </div>

            <textarea id="fmCodeArea" class="form-control code-editor-textarea flex-grow w-100 border-0 mb-3" placeholder="// Edit HTML, CSS, JS, or text content here..."></textarea>
            <div id="gjs-file-container" class="flex-grow w-100 bg-white text-slate-900 rounded-xl overflow-hidden mb-3 d-none" style="min-height:400px;"></div>
          </div>

          <div id="fmEditorEmptyState" class="d-flex flex-column align-items-center justify-content-center text-slate-500 my-auto py-20">
            <i class="fas fa-code text-5xl mb-3 text-slate-700"></i>
            <p class="text-xs font-medium m-0">Select any text file from the left pane to edit code.</p>
          </div>
        </div>

      </div>
    </div>

  </div>
</div>
