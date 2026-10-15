<?php
/**
 * templates.php
 *
 * Standalone Templates Management page.
 */

declare(strict_types=1);

$pageTitle = 'Templates';
$pageSubtitle = 'Manage site builder layout templates and pre-designed starter themes.';

require_once __DIR__ . '/admin_header.php';

// Dynamically scan and synchronize master templates from /templates/
$templatesList = getDiscoveredTemplates();

// Fetch categories for upload dropdown
$conn->createTable('categories');
$categoriesList = $conn->select('categories') ?: [];
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <div class="text-xs text-gray-500 font-semibold">Showing <?php echo count($templatesList); ?> available starter designs in <code class="bg-gray-100 px-2 py-0.5 rounded">/templates/</code></div>
  <button class="btn btn-primary btn-sm font-bold rounded-md bg-blue-600 border-0" data-bs-toggle="modal" data-bs-target="#uploadTemplateModal"><i class="fas fa-upload me-1"></i> Upload New Template</button>
</div>

<div id="adminTplFeedback" class="alert d-none text-xs rounded-lg p-3 mb-4" role="alert"></div>

<div class="row g-4">
  <?php if (empty($templatesList)): ?>
    <div class="col-12 text-center text-gray-400 py-5">No templates discovered inside /templates/ directory.</div>
  <?php else: ?>
    <?php foreach ($templatesList as $tpl):
      $isAct = strtolower((string)($tpl['status'] ?? 'active')) === 'active';
      $tplId = (int)($tpl['id'] ?? 0);
      $tplName = htmlspecialchars((string)($tpl['name'] ?? ''));
      $tplCat = htmlspecialchars((string)($tpl['category'] ?? 'general'));
      $tplImg = htmlspecialchars((string)($tpl['preview_img'] ?? ''));
      $tplPath = htmlspecialchars((string)($tpl['path'] ?? ''));
    ?>
      <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-xl overflow-hidden h-100 hover-translate bg-white">
          <div class="position-relative" style="height: 160px; background-color: #f1f5f9;">
            <img src="<?php echo $tplImg; ?>" class="w-100 h-100 object-cover" alt="Template Preview" onerror="this.src='https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=400'">
            <span class="position-absolute top-2 right-2 badge bg-dark bg-opacity-75 text-white text-2xs uppercase px-2 py-1 rounded"><?php echo strtoupper($tplCat); ?></span>
          </div>
          <div class="card-body p-3">
            <h4 class="text-sm font-bold text-gray-800 m-0"><?php echo $tplName; ?></h4>
            <div class="d-flex justify-content-between align-items-center mt-2 text-2xs text-gray-500">
              <span><i class="fas fa-folder text-gray-400 me-1"></i> <?php echo htmlspecialchars((string)($tpl['folder'] ?? '')); ?></span>
              <?php if ($isAct): ?>
                <span class="badge bg-success bg-opacity-10 text-success font-bold px-2 py-0.5 rounded">Active</span>
              <?php else: ?>
                <span class="badge bg-secondary bg-opacity-10 text-secondary font-bold px-2 py-0.5 rounded">Disabled</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="card-footer bg-gray-50 border-t border-gray-100 p-2.5 d-flex gap-1">
            <button class="btn btn-light btn-xs text-xs font-semibold w-33 border rounded-md btn-admin-preview" data-path="<?php echo $tplPath; ?>/index.html" data-name="<?php echo $tplName; ?>"><i class="fas fa-eye"></i></button>
            <button class="btn btn-outline-primary btn-xs text-xs font-semibold w-33 rounded-md btn-admin-toggle-tpl" data-id="<?php echo $tplId; ?>"><?php echo $isAct ? 'Disable' : 'Enable'; ?></button>
            <button class="btn btn-outline-danger btn-xs text-xs font-semibold w-33 rounded-md btn-admin-delete-tpl" data-id="<?php echo $tplId; ?>"><i class="fas fa-trash"></i></button>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- UPLOAD NEW TEMPLATE MODAL -->
<div class="modal fade" id="uploadTemplateModal" tabindex="-1" aria-labelledby="uploadTemplateModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content rounded-2xl border-0 shadow-2xl">
      <div class="modal-header bg-slate-900 text-white py-3 px-4 rounded-t-2xl">
        <h5 class="modal-title font-bold text-sm d-flex align-items-center" id="uploadTemplateModalLabel"><i class="fas fa-file-arrow-up me-2 text-blue-400"></i> Upload New Template Package</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4 text-xs">
        <div id="uploadTplFeedback" class="alert d-none text-xs rounded-lg p-2.5 mb-3" role="alert"></div>

        <form id="uploadTemplateForm" enctype="multipart/form-data">
          <input type="hidden" name="action" value="upload_template">

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label text-2xs uppercase font-bold text-gray-500">Category</label>
              <select name="category" class="form-control text-xs rounded-md" required>
                <option value="">-- Choose Category --</option>
                <?php foreach ($categoriesList as $cat): ?>
                  <option value="<?php echo htmlspecialchars((string)($cat['slug'] ?? '')); ?>"><?php echo htmlspecialchars((string)($cat['name'] ?? '')); ?> (<?php echo htmlspecialchars((string)($cat['slug'] ?? '')); ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label text-2xs uppercase font-bold text-gray-500">Template Title</label>
              <input type="text" name="name" class="form-control text-xs rounded-md" placeholder="e.g. Modern Agency Pro" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label text-2xs uppercase font-bold text-gray-500">Description</label>
            <textarea name="description" class="form-control text-xs rounded-md" rows="2" placeholder="Brief template summary..."></textarea>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label text-2xs uppercase font-bold text-gray-500">Template Archive (.zip file)</label>
              <input type="file" name="template_zip" accept=".zip" class="form-control text-xs rounded-md" required>
              <small class="text-gray-400 block mt-1">Must contain index.html, style.css, and isolated assets.</small>
            </div>
            <div class="col-md-6">
              <label class="form-label text-2xs uppercase font-bold text-gray-500">Preview Image File (Optional)</label>
              <input type="file" name="preview_file" accept="image/*" class="form-control text-xs rounded-md">
            </div>
          </div>

          <button type="submit" id="btnSubmitUploadTpl" class="btn btn-primary bg-blue-600 text-white btn-sm font-bold w-100 rounded-md py-2 border-0"><i class="fas fa-cloud-arrow-up me-1"></i> Upload & Extract into /templates/</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- ADMIN PREVIEW TEMPLATE MODAL -->
<div class="modal fade" id="adminPreviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content rounded-2xl border-0 shadow-2xl overflow-hidden">
      <div class="modal-header bg-slate-900 text-white py-3 px-4">
        <h5 class="modal-title font-bold text-sm d-flex align-items-center"><i class="fas fa-eye me-2 text-blue-400"></i> Admin Template Inspection: <span id="adminPreviewTitle" class="text-blue-300 ms-1 font-bold"></span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0 bg-slate-100" style="height: 600px;">
        <iframe id="adminPreviewIframe" src="about:blank" class="w-100 h-100 border-0"></iframe>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Admin Template Preview
    $('.btn-admin-preview').on('click', function() {
        const path = $(this).attr('data-path');
        const name = $(this).attr('data-name');
        $('#adminPreviewTitle').text(name);
        $('#adminPreviewIframe').attr('src', path);
        const modal = new bootstrap.Modal(document.getElementById('adminPreviewModal'));
        modal.show();
    });

    // Toggle Status
    $('.btn-admin-toggle-tpl').on('click', function() {
        const id = $(this).attr('data-id');
        $.ajax({
            url: '/php/admin_action.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'toggle_template_status', template_id: id },
            success: function(res) {
                alert(res.message);
                location.reload();
            }
        });
    });

    // Delete Template
    $('.btn-admin-delete-tpl').on('click', function() {
        const id = $(this).attr('data-id');
        if (!confirm('Are you sure you want to delete this template from disk and system database?')) return;
        $.ajax({
            url: '/php/admin_action.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'delete_template', template_id: id },
            success: function(res) {
                alert(res.message);
                location.reload();
            }
        });
    });

    // Upload Form
    $('#uploadTemplateForm').on('submit', function(e) {
        e.preventDefault();
        const feedback = $('#uploadTplFeedback');
        const btn = $('#btnSubmitUploadTpl');
        feedback.addClass('d-none').removeClass('alert-success alert-danger');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Extracting Archive...');

        const formData = new FormData(this);

        $.ajax({
            url: '/php/admin_action.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(res) {
                btn.prop('disabled', false).html('<i class="fas fa-cloud-arrow-up me-1"></i> Upload & Extract into /templates/');
                if (res.success) {
                    feedback.removeClass('d-none').addClass('alert-success').text(res.message);
                    setTimeout(function() { location.reload(); }, 1200);
                } else {
                    feedback.removeClass('d-none').addClass('alert-danger').text(res.message);
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fas fa-cloud-arrow-up me-1"></i> Upload & Extract into /templates/');
                feedback.removeClass('d-none').addClass('alert-danger').text(xhr.responseJSON ? xhr.responseJSON.message : 'Upload failed.');
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
