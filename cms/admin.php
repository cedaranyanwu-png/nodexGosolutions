<?php
/**
 * admin.php
 *
 * This is the premium, White & Blue themed Code-First CMS Administration Control Panel.
 * Allows administrators to select page slugs, adjust title metadata, and dynamically
 * inject custom HTML/CSS/PHP code blocks into active endpoints.
 */

// Enable strict typing for safety
declare(strict_types=1);

// Require dynamic database configuration
require_once __DIR__ . '/../php/db.php';

// Instantiate secure session configurations
secureSession();

// Access Control: Ensure only authorized admins can access the CMS editor
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login');
    exit;
}

// Initialize the site_cms database instance
$siteCmsDb = new Database(__DIR__ . '/../databases', 'site_cms');
$siteCmsDb->createTable('pages');

// Fetch the list of all currently created CMS pages
$pagesList = $siteCmsDb->select('pages') ?: [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CMS Code Editor | nodexGosolutions</title>

  <!-- Load standard Fonts and Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@300;400;600;700&family=Orbitron:wght@600;700;900&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Load premium central White & Blue stylesheet -->
  <link href="<?= assetUrl('/css/style.css') ?>" rel="stylesheet">

  <style>
    :root {
      --primary: #0072ff;
      --primary-light: #eef2ff;
      --accent: #00d2ff;
      --dark: #0f172a;
      --charcoal: #1e293b;
      --light-bg: #f8fafc;
      --white: #ffffff;
      --border-color: rgba(0, 114, 255, 0.08);
    }
    .cms-editor-container {
      max-width: 1100px;
      margin: 40px auto;
      padding: 0 20px;
    }
    .code-editor-textarea {
      font-family: 'Fira Code', 'Courier New', Courier, monospace;
      font-size: 14px;
      background-color: #0f172a;
      color: #38bdf8;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      padding: 15px;
      resize: vertical;
    }
    .code-editor-textarea:focus {
      background-color: #0b0f19;
      border-color: var(--primary);
      outline: none;
      box-shadow: 0 0 12px rgba(0, 114, 255, 0.15);
      color: #38bdf8;
    }
  </style>
</head>
<body style="background-color: var(--light-bg); color: var(--charcoal);">

  <!-- Modular Navigation Bar component -->
  <?php require_once __DIR__ . '/../main/modul/nav.html'; ?>

  <div class="cms-editor-container">

    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="fw-bold text-dark mb-1" style="font-family: 'Orbitron', sans-serif;"><i class="fa-solid fa-code text-primary me-2"></i>Code-First CMS Panel</h2>
        <p class="text-muted small mb-0">Direct HTML/CSS/PHP code injection into active routed slug endpoints.</p>
      </div>
      <a href="/admin/dashboard" class="btn btn-outline-primary rounded-pill fw-bold"><i class="fa-solid fa-arrow-left me-1"></i> Return to Admin Control Panel</a>
    </div>

    <div class="row g-4">

      <!-- List of created/registered slugs sidebar -->
      <div class="col-lg-4">
        <div class="premium-card">
          <h5 class="fw-bold text-dark mb-3" style="font-family: 'Orbitron', sans-serif;">Slugs Directory</h5>

          <div class="list-group list-group-flush mb-4">
            <!-- Add default main marketing pages as directory items -->
            <button class="list-group-item list-group-item-action py-2 px-3 fw-bold btn-select-slug text-dark text-start" data-slug="index">
              <i class="fa-solid fa-house me-2 text-primary"></i>index (Landing Page)
            </button>
            <button class="list-group-item list-group-item-action py-2 px-3 fw-bold btn-select-slug text-dark text-start" data-slug="about">
              <i class="fa-solid fa-building me-2 text-primary"></i>about (About Corporate)
            </button>
            <button class="list-group-item list-group-item-action py-2 px-3 fw-bold btn-select-slug text-dark text-start" data-slug="cedar">
              <i class="fa-solid fa-user me-2 text-primary"></i>cedar (Founder Vision)
            </button>
            <button class="list-group-item list-group-item-action py-2 px-3 fw-bold btn-select-slug text-dark text-start" data-slug="gallary">
              <i class="fa-solid fa-images me-2 text-primary"></i>gallary (Gallery media)
            </button>
            <button class="list-group-item list-group-item-action py-2 px-3 fw-bold btn-select-slug text-dark text-start" data-slug="portfolio">
              <i class="fa-solid fa-id-badge me-2 text-primary"></i>portfolio (Executive Portfolio)
            </button>
            <button class="list-group-item list-group-item-action py-2 px-3 fw-bold btn-select-slug text-dark text-start" data-slug="roadmap">
              <i class="fa-solid fa-route me-2 text-primary"></i>roadmap (Milestones Roadmap)
            </button>
            <button class="list-group-item list-group-item-action py-2 px-3 fw-bold btn-select-slug text-dark text-start" data-slug="traction">
              <i class="fa-solid fa-chart-line me-2 text-primary"></i>traction (Platform Traction)
            </button>

            <!-- Render custom pages list from JSON database -->
            <?php foreach ($pagesList as $p):
                $pSlug = htmlspecialchars($p['slug'] ?? '');
                // Skip listing predefined static slugs to avoid duplications
                if (in_array($pSlug, ['index', 'about', 'cedar', 'gallary', 'portfolio', 'roadmap', 'traction'])) continue;
            ?>
              <button class="list-group-item list-group-item-action py-2 px-3 fw-bold btn-select-slug text-dark text-start" data-slug="<?php echo $pSlug; ?>">
                <i class="fa-solid fa-file-code me-2 text-primary"></i><?php echo $pSlug; ?> (Custom CMS)
              </button>
            <?php endforeach; ?>
          </div>

          <button class="btn btn-primary w-100 rounded-pill fw-bold btn-create-custom-page"><i class="fa-solid fa-plus me-1"></i>Create Custom Slug</button>
        </div>
      </div>

      <!-- Main Editor Console Panel -->
      <div class="col-lg-8">
        <div class="premium-card text-start">
          <h5 class="fw-bold text-dark mb-3" style="font-family: 'Orbitron', sans-serif;"><i class="fa-solid fa-file-signature me-1 text-primary"></i>Editor Console</h5>

          <div id="saveFeedback" class="alert d-none" role="alert"></div>

          <form id="cmsEditorForm">
            <!-- Target slug identifier -->
            <div class="mb-3">
              <label class="form-label text-muted small fw-bold">ENDPOINT SLUG</label>
              <input type="text" id="cmsSlug" class="form-control" placeholder="e.g. index, cedar, my-new-page" required style="border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 14px;" />
              <div class="form-text text-muted small">The dynamic URI pattern to associate (e.g. nodexplatform.com.ng/slug).</div>
            </div>

            <!-- Page Title metadata -->
            <div class="mb-3">
              <label class="form-label text-muted small fw-bold">PAGE TITLE METADATA</label>
              <input type="text" id="cmsTitle" class="form-control" placeholder="Enter browser tab title..." style="border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 14px;" />
            </div>

            <!-- Custom Head tags block -->
            <div class="mb-3">
              <label class="form-label text-muted small fw-bold">CUSTOM HEAD CODE & TAGS</label>
              <textarea id="cmsHeadCode" class="form-control code-editor-textarea" rows="4" placeholder="&lt;!-- Inject link tags, stylesheets, metadata tags here --&gt;"></textarea>
            </div>

            <!-- Custom Body markup block -->
            <div class="mb-4">
                <label class="form-label text-muted small fw-bold">CUSTOM BODY MARKUP & PHP CODES</label>
                <textarea id="cmsBodyCode" class="form-control code-editor-textarea" style="min-height: 350px;" placeholder="&lt;!-- Input custom HTML/CSS and executable PHP tags (&lt;?php ... ?&gt;) --&gt;"></textarea>
            </div>

            <!-- Submit action buttons -->
            <button type="submit" id="btnSaveCmsPage" class="btn btn-premium px-5 py-3 rounded-pill fw-bold"><i class="fa-solid fa-circle-check me-1"></i>Publish Page Layout</button>
          </form>
        </div>
      </div>

    </div>

  </div>

  <!-- jQuery & Bootstrap JS -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Interactive AJAX Handlers for Code loading & saving -->
  <script>
  $(document).ready(function() {
      // Keep tracks of all database CMS pages
      let pagesDb = <?php echo json_encode($pagesList); ?>;

      // Handle custom slug selections
      $(document).on('click', '.btn-select-slug', function() {
          const slug = $(this).attr('data-slug');

          // Clear active classes
          $('.btn-select-slug').removeClass('active bg-primary text-white');
          $(this).addClass('active bg-primary text-white');

          // Find page settings inside database list
          const page = pagesDb.find(p => p.slug === slug);
          if (page) {
              $('#cmsSlug').val(page.slug).prop('readonly', true);
              $('#cmsTitle').val(page.title || '');
              $('#cmsHeadCode').val(page.head_code || '');
              $('#cmsBodyCode').val(page.body_code || '');
          } else {
              // Predefined static defaults template loaders
              $('#cmsSlug').val(slug).prop('readonly', true);
              $('#cmsTitle').val('');
              $('#cmsHeadCode').val('');
              $('#cmsBodyCode').val('<!-- Ready for custom overrides! Enter custom HTML or PHP code tags here -->');
          }
      });

      // Handle create custom page click
      $('.btn-create-custom-page').on('click', function() {
          $('.btn-select-slug').removeClass('active bg-primary text-white');
          $('#cmsSlug').val('').prop('readonly', false).focus();
          $('#cmsTitle').val('');
          $('#cmsHeadCode').val('');
          $('#cmsBodyCode').val('');
      });

      // Handle CMS Form publishing
      $('#cmsEditorForm').on('submit', function(e) {
          e.preventDefault();
          const feedback = $('#saveFeedback');
          const btn = $('#btnSaveCmsPage');

          btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i>Publishing Payloads...');
          feedback.addClass('d-none').removeClass('alert-success alert-danger');

          $.ajax({
              url: '/cms/save_page',
              type: 'POST',
              dataType: 'json',
              data: {
                  slug: $('#cmsSlug').val(),
                  title: $('#cmsTitle').val(),
                  head_code: $('#cmsHeadCode').val(),
                  body_code: $('#cmsBodyCode').val()
              },
              success: function(res) {
                  btn.prop('disabled', false).html('<i class="fa-solid fa-circle-check me-1"></i>Publish Page Layout');
                  feedback.removeClass('d-none');

                  if (res.success) {
                      feedback.addClass('alert-success').text(res.message);
                      // Schedule reload to update lists
                      setTimeout(() => {
                          window.location.reload();
                      }, 1000);
                  } else {
                      feedback.addClass('alert-danger').text(res.message);
                  }
              },
              error: function() {
                  btn.prop('disabled', false).html('<i class="fa-solid fa-circle-check me-1"></i>Publish Page Layout');
                  feedback.removeClass('d-none').addClass('alert-danger').text('Failed to communicate with CMS writer.');
              }
          });
      });

      // Select first item by default on load
      $('.btn-select-slug').first().click();
  });
  </script>

</body>
</html>
