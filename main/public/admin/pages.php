<?php
/**
 * pages.php (Admin Page Editor)
 *
 * Highly secured, ADMIN-ONLY GrapesJS Visual Page Editor for platform pages in /main/public/.
 * Strictly protected server-side via RBAC. Normal users and guests cannot access or execute this editor.
 * Supports: Page selection from approved whitelist, GrapesJS visual editing, Save Draft, Preview Draft,
 * Publish Live with automatic version backup, and Version History Restoration.
 */

declare(strict_types=1);

$pageTitle = 'Platform Page Editor';
$pageSubtitle = 'Visually edit and manage public landing pages in /main/public/ using GrapesJS.';

require_once __DIR__ . '/admin_header.php';

// Server-Side RBAC Protection Guard: Enforce admin page permission
if (!isStaff() || !hasAdminPagePermission($activeRole, '/admin/pages')) {
    http_response_code(403);
    echo '<div class="alert alert-danger font-bold text-xs p-4 m-4"><i class="fas fa-lock me-2"></i> 403 Access Denied: You do not possess administrative permissions to access the Page Editor.</div>';
    require_once __DIR__ . '/admin_footer.php';
    exit;
}

// Fetch approved public platform pages whitelist from /main/public/
$approvedPages = getApprovedPublicPages();

// Fetch drafts and versions info from site_cms
$cmsDb = new Database(__DIR__ . '/../../../databases', 'site_cms');
$cmsDb->createTable('pages');
$cmsDb->createTable('page_versions');

$draftsList = $cmsDb->select('pages', ['user_id' => 0]) ?: [];
$draftsMap  = array_column($draftsList, null, 'slug');

$allVersions = $cmsDb->select('page_versions') ?: [];
$versionCountMap = [];
foreach ($allVersions as $v) {
    $fn = $v['filename'] ?? '';
    if (!isset($versionCountMap[$fn])) {
        $versionCountMap[$fn] = 0;
    }
    $versionCountMap[$fn]++;
}
?>

<!-- GrapesJS Library CDN Assets -->
<link rel="stylesheet" href="https://unpkg.com/grapesjs/dist/css/grapes.min.css">
<script src="https://unpkg.com/grapesjs"></script>

<!-- Alert Feedback Hub -->
<div id="pagesFeedbackAlert" class="alert d-none text-xs rounded-lg p-3 mb-4" role="alert"></div>

<!-- GRAPESJS ADMIN VISUAL EDITOR WORKSPACE -->
<div class="card border-0 shadow-lg rounded-2xl overflow-hidden mb-4 d-none" id="admin-editor-workspace">
  <div class="card-header bg-slate-900 text-white py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div class="d-flex align-items-center gap-3">
      <span class="fw-bold text-sm text-blue-400 d-flex align-items-center">
        <i class="fa-solid fa-file-pen me-2"></i> GrapesJS Admin Page Editor
      </span>
      <span id="editorActivePageTitle" class="badge bg-slate-800 text-blue-300 font-mono text-xs px-2.5 py-1 rounded-md"></span>
    </div>

    <!-- Device Switcher Buttons -->
    <div class="btn-group btn-group-sm bg-slate-800 p-1 rounded-lg" role="group">
      <button type="button" class="btn btn-dark text-slate-300 border-0 active btn-admin-device" data-device="desktop" title="Desktop View"><i class="fa-solid fa-desktop"></i></button>
      <button type="button" class="btn btn-dark text-slate-300 border-0 btn-admin-device" data-device="tablet" title="Tablet View"><i class="fa-solid fa-tablet-screen-button"></i></button>
      <button type="button" class="btn btn-dark text-slate-300 border-0 btn-admin-device" data-device="mobile" title="Mobile View"><i class="fa-solid fa-mobile-screen-button"></i></button>
    </div>

    <!-- Actions Toolbar -->
    <div class="d-flex align-items-center gap-2">
      <button type="button" id="btnAdminPreviewDraft" class="btn btn-xs btn-outline-light font-semibold rounded-md py-1.5 px-3">
        <i class="fa-solid fa-eye me-1"></i> Preview Draft
      </button>
      <button type="button" id="btnAdminSaveDraft" class="btn btn-xs btn-warning text-dark font-bold rounded-md py-1.5 px-3 border-0">
        <i class="fa-solid fa-floppy-disk me-1"></i> Save Draft
      </button>
      <button type="button" id="btnAdminPublishLive" class="btn btn-xs btn-success bg-emerald-600 text-white font-bold rounded-md py-1.5 px-3 border-0">
        <i class="fa-solid fa-paper-plane me-1"></i> Publish Live
      </button>
      <button type="button" id="btnAdminCloseEditor" class="btn btn-xs btn-secondary rounded-md py-1.5 px-3">
        <i class="fa-solid fa-arrow-left me-1"></i> Close Editor
      </button>
    </div>
  </div>

  <div class="card-body p-0 position-relative bg-slate-100" style="min-height: 680px;">
    <div id="admin-gjs-editor" style="height: 680px; width: 100%;"></div>
  </div>
</div>

<!-- APPROVED PLATFORM PAGES TABLE LIST -->
<div class="card border-0 shadow-sm rounded-xl">
  <div class="card-header bg-white border-b border-gray-100 py-3.5 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h3 class="text-base font-bold text-gray-800 m-0 d-flex align-items-center">
        <i class="fa-solid fa-file-code text-primary me-2"></i> Platform Approved Public Pages Directory
      </h3>
      <p class="text-2xs text-gray-400 m-0 mt-0.5" style="font-size: 11px;">Whitelisted public pages in <code class="bg-gray-100 px-1.5 py-0.5 rounded">/main/public/</code> available for visual editing.</p>
    </div>
    <span class="badge bg-blue-50 text-blue-700 font-bold px-2.5 py-1 rounded-pill text-2xs"><?php echo count($approvedPages); ?> Approved Pages</span>
  </div>

  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0 text-xs">
        <thead class="bg-gray-50 text-gray-500 font-bold">
          <tr>
            <th class="p-3.5">Filename</th>
            <th class="p-3.5">Relative Path</th>
            <th class="p-3.5">Status</th>
            <th class="p-3.5">Backup Versions</th>
            <th class="p-3.5">Last Modified</th>
            <th class="p-3.5 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 text-gray-700">
          <?php foreach ($approvedPages as $page):
            $fn = $page['filename'];
            $pPath = $page['path'];
            $hasDraft = isset($draftsMap[$fn]) && (int)($draftsMap[$fn]['is_draft'] ?? 0) === 1;
            $vCount = $versionCountMap[$fn] ?? 0;
          ?>
            <tr>
              <td class="p-3.5 font-bold text-gray-800 d-flex align-items-center">
                <i class="fa-regular fa-file-lines text-primary me-2 font-xl"></i> <?php echo htmlspecialchars($fn); ?>
              </td>
              <td class="p-3.5 font-mono text-gray-500"><?php echo htmlspecialchars($pPath); ?></td>
              <td class="p-3.5">
                <?php if ($hasDraft): ?>
                  <span class="badge bg-warning bg-opacity-10 text-warning-800 font-bold px-2.5 py-1 rounded-full">● Draft Saved</span>
                <?php else: ?>
                  <span class="badge bg-emerald-50 text-emerald-700 font-bold px-2.5 py-1 rounded-full">● Live Published</span>
                <?php endif; ?>
              </td>
              <td class="p-3.5 font-bold text-gray-600">
                <span class="badge bg-gray-100 text-gray-700 px-2 py-1 rounded"><?php echo $vCount; ?> Versions</span>
              </td>
              <td class="p-3.5 text-gray-400"><?php echo date('M d, Y H:i', strtotime($page['last_modified'])); ?></td>
              <td class="p-3.5 text-right">
                <button class="btn btn-xs btn-primary bg-blue-600 text-white rounded-md me-1 font-bold py-1 px-2.5 btn-open-admin-editor" data-filename="<?php echo htmlspecialchars($fn); ?>">
                  <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Edit Page
                </button>
                <button class="btn btn-xs btn-outline-secondary rounded-md me-1 font-semibold py-1 px-2 btn-view-versions" data-filename="<?php echo htmlspecialchars($fn); ?>">
                  <i class="fa-solid fa-clock-rotate-left me-1"></i> Versions
                </button>
                <a href="<?php echo htmlspecialchars('/' . str_replace('.php', '', $fn)); ?>" target="_blank" class="btn btn-xs btn-light border rounded-md py-1 px-2 text-gray-600">
                  <i class="fa-solid fa-arrow-up-right-from-square"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- DRAFT PREVIEW MODAL -->
<div class="modal fade" id="adminDraftPreviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content rounded-2xl border-0 shadow-2xl overflow-hidden">
      <div class="modal-header bg-slate-900 text-white py-3 px-4">
        <h5 class="modal-title font-bold text-sm d-flex align-items-center">
          <i class="fas fa-eye me-2 text-blue-400"></i> Draft Sandbox Preview: <span id="previewDraftFilename" class="text-blue-300 ms-1 font-bold"></span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0 bg-white" style="height: 600px;">
        <iframe id="previewDraftIframe" src="about:blank" class="w-100 h-100 border-0"></iframe>
      </div>
      <div class="modal-footer bg-gray-50 border-t border-gray-100 py-2.5 px-4 d-flex justify-content-between align-items-center">
        <span class="text-2xs text-gray-400" style="font-size: 11px;"><i class="fas fa-shield-halved me-1"></i> Isolated sandbox preview. Live visitors continue seeing published version.</span>
        <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Close Preview</button>
      </div>
    </div>
  </div>
</div>

<!-- PAGE VERSIONS & RESTORE MODAL -->
<div class="modal fade" id="adminVersionsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content rounded-2xl border-0 shadow-2xl">
      <div class="modal-header bg-slate-900 text-white py-3 px-4 rounded-t-2xl">
        <h5 class="modal-title font-bold text-sm d-flex align-items-center">
          <i class="fa-solid fa-clock-rotate-left me-2 text-blue-400"></i> Backup Versions History: <span id="versionsModalFilename" class="text-blue-300 ms-1 font-bold"></span>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4 text-xs">
        <div id="versionsModalFeedback" class="alert d-none text-xs rounded-lg p-2.5 mb-3" role="alert"></div>

        <div class="table-responsive">
          <table class="table table-hover mb-0 text-xs">
            <thead class="bg-gray-50 text-gray-500 font-bold">
              <tr>
                <th class="p-3">Version</th>
                <th class="p-3">Published By Admin</th>
                <th class="p-3">Backup Date</th>
                <th class="p-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody id="versionsTableBody" class="divide-y divide-gray-100 text-gray-700">
              <!-- Versions loaded via AJAX -->
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    let adminGrapesEditor = null;
    let activeEditingFilename = '';

    // 1. Open Page in GrapesJS Editor
    $('.btn-open-admin-editor').on('click', function() {
        const fn = $(this).attr('data-filename');
        activeEditingFilename = fn;

        $('#editorActivePageTitle').text(fn);
        $('#admin-editor-workspace').removeClass('d-none');
        $('html, body').animate({ scrollTop: $("#admin-editor-workspace").offset().top - 20 }, 300);

        // Fetch page content via AJAX
        $.ajax({
            url: '/php/admin_action.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'admin_load_page', filename: fn },
            success: function(res) {
                if (res.success) {
                    const contentToLoad = res.has_draft ? res.draft_content : res.live_content;
                    initAdminGrapesjsInstance(contentToLoad);
                } else {
                    alert(res.message || 'Failed to load page content.');
                }
            },
            error: function(xhr) {
                alert(xhr.responseJSON ? xhr.responseJSON.message : 'Error communicating with server.');
            }
        });
    });

    function initAdminGrapesjsInstance(initialContent) {
        if (adminGrapesEditor) {
            adminGrapesEditor.destroy();
            $('#admin-gjs-editor').empty();
        }

        adminGrapesEditor = grapesjs.init({
            container: '#admin-gjs-editor',
            fromElement: false,
            height: '680px',
            width: 'auto',
            storageManager: false,
            components: initialContent
        });
    }

    // Device switchers
    $('.btn-admin-device').on('click', function() {
        $('.btn-admin-device').removeClass('active bg-primary');
        $(this).addClass('active bg-primary');
        const device = $(this).attr('data-device');
        if (adminGrapesEditor) {
            if (device === 'mobile') {
                adminGrapesEditor.setDevice('Mobile');
            } else if (device === 'tablet') {
                adminGrapesEditor.setDevice('Tablet');
            } else {
                adminGrapesEditor.setDevice('Desktop');
            }
        }
    });

    // Save Draft
    $('#btnAdminSaveDraft').on('click', function() {
        if (!adminGrapesEditor || !activeEditingFilename) return;
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Saving Draft...');

        const fullCode = adminGrapesEditor.getHtml() + '<style>' + adminGrapesEditor.getCss() + '</style>';

        $.ajax({
            url: '/php/admin_action.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'admin_save_draft',
                filename: activeEditingFilename,
                content: fullCode
            },
            success: function(res) {
                btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Save Draft');
                if (res.success) {
                    alert(res.message);
                } else {
                    alert(res.message || 'Failed to save draft.');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Save Draft');
                alert(xhr.responseJSON ? xhr.responseJSON.message : 'Error saving draft.');
            }
        });
    });

    // Preview Draft
    $('#btnAdminPreviewDraft').on('click', function() {
        if (!adminGrapesEditor || !activeEditingFilename) return;

        const html = adminGrapesEditor.getHtml();
        const css  = adminGrapesEditor.getCss();
        const fullDoc = '<!DOCTYPE html><html><head><style>' + css + '</style></head><body>' + html + '</body></html>';

        $('#previewDraftFilename').text(activeEditingFilename + ' (Draft Preview)');
        const iframe = document.getElementById('previewDraftIframe');
        iframe.srcdoc = fullDoc;

        const modal = new bootstrap.Modal(document.getElementById('adminDraftPreviewModal'));
        modal.show();
    });

    // Publish Live
    $('#btnAdminPublishLive').on('click', function() {
        if (!adminGrapesEditor || !activeEditingFilename) return;
        if (!confirm('Are you sure you want to PUBLISH this page live to visitors?\nA backup version of the current live page will be created automatically.')) return;

        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Publishing Live...');

        const fullCode = adminGrapesEditor.getHtml() + '<style>' + adminGrapesEditor.getCss() + '</style>';

        $.ajax({
            url: '/php/admin_action.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'admin_publish_page',
                filename: activeEditingFilename,
                content: fullCode
            },
            success: function(res) {
                btn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane me-1"></i> Publish Live');
                if (res.success) {
                    alert(res.message);
                    location.reload();
                } else {
                    alert(res.message || 'Publishing failed.');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fa-solid fa-paper-plane me-1"></i> Publish Live');
                alert(xhr.responseJSON ? xhr.responseJSON.message : 'Error publishing page.');
            }
        });
    });

    // Close Editor
    $('#btnAdminCloseEditor').on('click', function() {
        $('#admin-editor-workspace').addClass('d-none');
    });

    // View Version History Modal
    $('.btn-view-versions').on('click', function() {
        const fn = $(this).attr('data-filename');
        $('#versionsModalFilename').text(fn);
        $('#versionsModalFeedback').addClass('d-none');

        const tbody = $('#versionsTableBody');
        tbody.html('<tr><td colspan="4" class="text-center py-4 text-gray-400"><i class="fas fa-spinner fa-spin me-1"></i> Loading version backups...</td></tr>');

        const modal = new bootstrap.Modal(document.getElementById('adminVersionsModal'));
        modal.show();

        $.ajax({
            url: '/php/admin_action.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'admin_list_versions', filename: fn },
            success: function(res) {
                tbody.empty();
                if (res.success && res.versions.length > 0) {
                    res.versions.forEach(function(v) {
                        tbody.append(`
                            <tr>
                                <td class="p-3 font-bold text-blue-600">v${v.version_num || '1'}</td>
                                <td class="p-3 font-semibold text-gray-700">${v.admin_email || 'System'}</td>
                                <td class="p-3 text-gray-400">${v.created_at || ''}</td>
                                <td class="p-3 text-right">
                                    <button class="btn btn-xs btn-outline-primary rounded-md font-bold py-1 px-2.5 btn-restore-version" data-id="${v.id}">
                                        <i class="fa-solid fa-rotate-left me-1"></i> Restore Version
                                    </button>
                                </td>
                            </tr>
                        `);
                    });
                } else {
                    tbody.html('<tr><td colspan="4" class="text-center text-gray-400 py-4">No previous version backups stored for this page yet.</td></tr>');
                }
            }
        });
    });

    // Restore Version Action
    $(document).on('click', '.btn-restore-version', function() {
        const vId = $(this).attr('data-id');
        if (!confirm('Are you sure you want to RESTORE this version snapshot to the live page?\nThe current live page will be backed up as a new version first.')) return;

        $.ajax({
            url: '/php/admin_action.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'admin_restore_version', version_id: vId },
            success: function(res) {
                alert(res.message);
                location.reload();
            },
            error: function(xhr) {
                alert(xhr.responseJSON ? xhr.responseJSON.message : 'Failed to restore version.');
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
