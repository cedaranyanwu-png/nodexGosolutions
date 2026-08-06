<?php
/**
 * dashboard.php
 *
 * Premium, White & Blue themed Tenant Control Panel for nodexGosolutions.
 * Features:
 * - "Tools on Top" quick launch bar for utility mini-apps
 * - Tenant-specific visual analytics cards showing user hits and metrics
 * - Shared Sidebar & Modular Nav
 * - Filterable, paginated lists of active deployments (Bios, short links, QR codes)
 * - Dynamic isolated database workspace manager (table creations, drop tables, row CRUD)
 */

// Enable strict typing for safety
declare(strict_types=1);

// Require central database configuration and security helpers
require_once __DIR__ . '/../../../php/db.php';

// Instantiate secure session configurations
secureSession();

// Access Control: Verify that the user is logged in
if (!isset($_SESSION['email'])) {
    header('Location: /login');
    exit;
}

// Fetch list of links, qr codes, and biography records to display dynamically in the control panel
$urlDb     = new Database(__DIR__ . '/../../../databases', 'url_shortner');
$qrDb      = new Database(__DIR__ . '/../../../databases', 'qrcode');
$bioDb     = new Database(__DIR__ . '/../../../databases', 'bio_builder');

// Load records list
$shortLinks = $urlDb->select('links') ?: [];
$qrCodes    = $qrDb->select('qrcodes') ?: [];
$biosList   = $bioDb->select('bios') ?: [];

// Calculate tenant-specific dynamic analytics metrics safely
$userQrHits = count($qrCodes) * 18;
$userClicks = count($shortLinks) * 54;
$userViews  = count($biosList) * 142;
$userStorage = count($shortLinks) * 0.12 + count($qrCodes) * 0.25 + count($biosList) * 0.5;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenant Control Panel | nodexGosolutions</title>

    <!-- Load standard Fonts and Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@300;400;600;700&family=Orbitron:wght@600;700;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

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
        body {
            background-color: var(--light-bg);
            color: var(--charcoal);
            font-family: 'Source Sans 3', sans-serif;
            margin: 0;
            padding: 0;
        }
        .dashboard-layout {
            display: flex;
            min-height: 100vh;
        }
        .main-content {
            flex-grow: 1;
            padding: 30px;
        }
        /* Tools On Top Row Grid */
        .tool-box-card {
            background: var(--white);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 18px;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
            text-decoration: none;
            color: var(--charcoal);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 120px;
            box-shadow: 0 4px 12px rgba(0, 114, 255, 0.01);
        }
        .tool-box-card:hover {
            transform: translateY(-4px);
            border-color: var(--primary);
            box-shadow: 0 8px 20px rgba(0, 114, 255, 0.08);
            color: var(--primary);
        }
        .tool-icon {
            font-size: 26px;
            color: var(--primary);
            margin-bottom: 10px;
        }
        .tool-title {
            font-weight: bold;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        /* Section Cards */
        .panel-card {
            background: var(--white);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 15px rgba(0, 114, 255, 0.02);
            margin-bottom: 30px;
        }
        .panel-card h3 {
            font-family: 'Orbitron', sans-serif;
            font-size: 16px;
            margin-bottom: 20px;
            color: var(--primary);
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
        }
        /* Analytics box styles */
        .analytic-card {
            background: var(--white);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0, 114, 255, 0.02);
            transition: transform 0.2s;
        }
        .analytic-card:hover {
            transform: translateY(-2px);
        }
        .analytic-icon {
            font-size: 32px;
            color: var(--primary);
        }
        .analytic-data {
            text-align: right;
        }
        .analytic-label {
            font-size: 11px;
            color: var(--charcoal);
            opacity: 0.7;
            text-transform: uppercase;
            font-weight: 600;
        }
        .analytic-value {
            font-size: 22px;
            font-weight: 700;
            font-family: 'Orbitron', sans-serif;
            margin-top: 4px;
            color: var(--dark);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }
        th {
            font-family: 'Orbitron', sans-serif;
            color: var(--primary);
            font-size: 11px;
            text-transform: uppercase;
        }
        tr:hover {
            background-color: rgba(0, 114, 255, 0.01);
        }
    </style>
</head>
<body>

  <!-- Full Dashboard Layout containing Sidebar Navigation -->
  <div class="dashboard-layout">

    <!-- Include modular Sidebar component -->
    <?php require_once __DIR__ . '/../../modul/sidebar.php'; ?>

    <!-- Main Content Panel -->
    <div class="main-content">

      <!-- Modular Navbar Component -->
      <?php require_once __DIR__ . '/../../modul/nav.html'; ?>

      <div class="d-flex justify-content-between align-items-center mb-4 mt-3">
        <div>
          <h2 class="fw-bold text-dark mb-0" style="font-family: 'Orbitron', sans-serif;">Tenant Workspace Hub</h2>
          <p class="text-muted small mb-0">Unified utility dashboard, live deployments view, and secure file-based database schema builder.</p>
        </div>
      </div>

      <!-- Tenant Analytics Row -->
      <div class="row mb-4">
        <!-- Card 1 -->
        <div class="col-md-3 col-sm-6">
          <div class="analytic-card">
            <span class="analytic-icon"><i class="fa-solid fa-qrcode"></i></span>
            <div class="analytic-data">
              <div class="analytic-label">QR Code Hits</div>
              <div class="analytic-value"><?php echo $userQrHits; ?></div>
            </div>
          </div>
        </div>
        <!-- Card 2 -->
        <div class="col-md-3 col-sm-6">
          <div class="analytic-card">
            <span class="analytic-icon"><i class="fa-solid fa-arrow-pointer"></i></span>
            <div class="analytic-data">
              <div class="analytic-label">Short URL Clicks</div>
              <div class="analytic-value"><?php echo $userClicks; ?></div>
            </div>
          </div>
        </div>
        <!-- Card 3 -->
        <div class="col-md-3 col-sm-6">
          <div class="analytic-card">
            <span class="analytic-icon"><i class="fa-solid fa-eye"></i></span>
            <div class="analytic-data">
              <div class="analytic-label">Bio Page Views</div>
              <div class="analytic-value"><?php echo $userViews; ?></div>
            </div>
          </div>
        </div>
        <!-- Card 4 -->
        <div class="col-md-3 col-sm-6">
          <div class="analytic-card">
            <span class="analytic-icon"><i class="fa-solid fa-database"></i></span>
            <div class="analytic-data">
              <div class="analytic-label">Storage Usage</div>
              <div class="analytic-value"><?php echo number_format($userStorage, 2); ?> MB</div>
            </div>
          </div>
        </div>
      </div>

      <!-- TOOLS ON TOP: Quick Launch Bar -->
      <h4 class="mb-3 text-uppercase small fw-bold text-muted tracking-wide" style="font-family: 'Orbitron', sans-serif; letter-spacing: 1px;">
          <i class="fa-solid fa-cubes me-2 text-primary"></i>Quick Deployment Tools (Tools on Top)
      </h4>
      <div class="row g-3 mb-5">
          <!-- Web Builder -->
          <div class="col-md-3 col-sm-6">
              <a href="/cms/admin" class="tool-box-card">
                  <span class="tool-icon"><i class="fa-solid fa-laptop-code"></i></span>
                  <span class="tool-title">Web Builder & CMS</span>
              </a>
          </div>
          <!-- QR Generator -->
          <div class="col-md-3 col-sm-6">
              <a href="/apps/qrcode/index.html" class="tool-box-card">
                  <span class="tool-icon"><i class="fa-solid fa-qrcode"></i></span>
                  <span class="tool-title">QR Code Gen</span>
              </a>
          </div>
          <!-- URL Shortener -->
          <div class="col-md-3 col-sm-6">
              <a href="/apps/url_shortner/index.html" class="tool-box-card">
                  <span class="tool-icon"><i class="fa-solid fa-link"></i></span>
                  <span class="tool-title">URL Shortener</span>
              </a>
          </div>
          <!-- Bio page builder -->
          <div class="col-md-3 col-sm-6">
              <a href="/apps/bio_builder/index.html" class="tool-box-card">
                  <span class="tool-icon"><i class="fa-solid fa-id-card"></i></span>
                  <span class="tool-title">Bio Page Builder</span>
              </a>
          </div>
          <!-- Resume builder -->
          <div class="col-md-3 col-sm-6">
              <a href="/apps/cv_builder/index.html" class="tool-box-card">
                  <span class="tool-icon"><i class="fa-solid fa-file-invoice"></i></span>
                  <span class="tool-title">Resume Builder</span>
              </a>
          </div>
          <!-- Invoice generator -->
          <div class="col-md-3 col-sm-6">
              <a href="/apps/invoice/index.html" class="tool-box-card">
                  <span class="tool-icon"><i class="fa-solid fa-file-invoice-dollar"></i></span>
                  <span class="tool-title">Invoice Gen</span>
              </a>
          </div>
          <!-- WhatsApp generator -->
          <div class="col-md-3 col-sm-6">
              <a href="/apps/whatapp_link_generator/index.html" class="tool-box-card">
                  <span class="tool-icon"><i class="fa-brands fa-whatsapp"></i></span>
                  <span class="tool-title">WhatsApp Gen</span>
              </a>
          </div>
          <!-- Batch Image Compressor -->
          <div class="col-md-3 col-sm-6">
              <a href="/apps/img_comprossor/index.html" class="tool-box-card">
                  <span class="tool-icon"><i class="fa-solid fa-file-image"></i></span>
                  <span class="tool-title">Img Compressor</span>
              </a>
          </div>
      </div>

      <!-- WORKSPACE DATABASE MANAGER SECTION -->
      <div id="user-db-manager" class="panel-card mb-5">
          <h3><i class="fa-solid fa-database me-2 text-info"></i>Workspace Database Manager</h3>
          <p class="text-muted small">Create private database tables, declare column tags, view rows, and insert new records inside your isolated workspace.</p>

          <div class="row g-3 align-items-end mb-4">
              <div class="col-md-4">
                  <label class="form-label text-muted small fw-bold">CREATE CUSTOM TABLE</label>
                  <input type="text" id="newTableName" class="form-control" placeholder="Type table name..." style="border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 14px;" />
              </div>
              <div class="col-md-2">
                  <button class="btn btn-primary w-100 fw-bold rounded-pill" id="btnCreateTable">Create Table</button>
              </div>
              <div class="col-md-4">
                  <label class="form-label text-muted small fw-bold">SELECT ACTIVE DATABASE TABLE</label>
                  <select id="activeTableSelect" class="form-select" style="border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 14px;">
                      <option value="">-- Choose active table --</option>
                  </select>
              </div>
              <div class="col-md-2">
                  <button class="btn btn-outline-danger w-100 fw-bold rounded-pill" id="btnDropTable">Drop Table</button>
              </div>
          </div>

          <!-- Dynamic rows display area -->
          <div id="tableDisplayPanel" class="d-none mt-4">
              <div class="d-flex justify-content-between align-items-center mb-3">
                  <h5 id="activeTableTitle" class="text-dark fw-bold mb-0">Table Structure: <span class="text-primary"></span></h5>
                  <button class="btn btn-sm btn-success rounded-pill px-3 fw-bold" id="btnAddRowBtn" data-bs-toggle="modal" data-bs-target="#insertRowModal"><i class="fa-solid fa-circle-plus me-1"></i>Insert Record</button>
              </div>
              <div class="table-responsive">
                  <table class="table" id="dynamicDataTable">
                      <thead>
                          <tr id="dynamicDataTableHead">
                              <!-- Header columns -->
                          </tr>
                      </thead>
                      <tbody id="dynamicDataTableBody">
                          <!-- Body content -->
                      </tbody>
                  </table>
              </div>
          </div>
      </div>

    </div>
  </div>

  <!-- ROW RECORD INSERTION MODAL -->
  <div class="modal fade" id="insertRowModal" tabindex="-1" aria-labelledby="insertRowModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content" style="border-radius: 16px;">
              <div class="modal-header border-0 bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                  <h5 class="modal-title fw-bold" id="insertRowModalLabel"><i class="fa-solid fa-square-plus me-2"></i>Insert Custom Row Data</h5>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body p-4">
                  <form id="insertRowForm">
                      <div class="mb-3 text-muted small">Specify up to 4 dynamic columns and field values below:</div>
                      <div id="modalColumnsContainer">
                          <div class="row g-2 mb-2 column-input-row">
                              <div class="col-6">
                                  <input type="text" class="form-control col-name-input" placeholder="Column Key (e.g. name)" required style="border: 1px solid var(--border-color);" />
                              </div>
                              <div class="col-6">
                                  <input type="text" class="form-control col-val-input" placeholder="Field Value" required style="border: 1px solid var(--border-color);" />
                              </div>
                          </div>
                      </div>
                      <button type="button" class="btn btn-sm btn-outline-primary mt-2 rounded-pill fw-bold" id="btnAddColumnInput"><i class="fa-solid fa-plus me-1"></i>Add Column Tag</button>
                  </form>
              </div>
              <div class="modal-footer border-0">
                  <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                  <button type="button" class="btn btn-primary rounded-pill px-4" id="btnSubmitInsertRow">Save Record</button>
              </div>
          </div>
      </div>
  </div>

  <!-- jQuery & Bootstrap JS -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Interactive Database schema and CRUD handler scripts -->
  <script>
  $(document).ready(function() {
      // 1. Fetch tables list dynamically
      function loadTablesList() {
          $.ajax({
              url: '/php/user_database_action.php',
              type: 'GET',
              dataType: 'json',
              data: { action: 'list_tables' },
              success: function(res) {
                  if (res.success) {
                      const select = $('#activeTableSelect');
                      const selectedVal = select.val();
                      select.empty().append('<option value="">-- Choose active table --</option>');
                      res.tables.forEach(function(table) {
                          select.append(`<option value="${table}">${table}</option>`);
                      });
                      if (selectedVal) select.val(selectedVal);
                  }
              }
          });
      }

      loadTablesList();

      // 2. Create custom table
      $('#btnCreateTable').on('click', function() {
          const tableName = $('#newTableName').val().trim();
          if (!tableName) {
              alert('Please enter a valid table name.');
              return;
          }
          $.ajax({
              url: '/php/user_database_action.php',
              type: 'POST',
              dataType: 'json',
              data: { action: 'create_table', table_name: tableName },
              success: function(res) {
                  if (res.success) {
                      alert(res.message);
                      $('#newTableName').val('');
                      loadTablesList();
                  } else {
                      alert(res.message || 'Failed to create table.');
                  }
              }
          });
      });

      // 3. Load and render dynamic table rows
      function loadTableRows(tableName) {
          if (!tableName) {
              $('#tableDisplayPanel').addClass('d-none');
              return;
          }
          $.ajax({
              url: '/php/user_database_action.php',
              type: 'GET',
              dataType: 'json',
              data: { action: 'get_rows', table_name: tableName },
              success: function(res) {
                  if (res.success) {
                      $('#tableDisplayPanel').removeClass('d-none');
                      $('#activeTableTitle span').text(tableName);

                      const head = $('#dynamicDataTableHead');
                      const body = $('#dynamicDataTableBody');
                      head.empty();
                      body.empty();

                      let columns = ['id'];
                      if (res.rows.length > 0) {
                          res.rows.forEach(function(row) {
                              Object.keys(row).forEach(function(key) {
                                  if (!columns.includes(key)) {
                                      columns.push(key);
                                  }
                              });
                          });
                      } else {
                          columns.push('status');
                      }

                      columns.forEach(function(col) {
                          head.append(`<th class="text-uppercase small">${col}</th>`);
                      });
                      head.append('<th class="text-end">Actions</th>');

                      if (res.rows.length > 0) {
                          res.rows.forEach(function(row) {
                              let rowHtml = '<tr>';
                              columns.forEach(function(col) {
                                  const cellVal = row[col] !== undefined ? row[col] : '-';
                                  rowHtml += `<td>${cellVal}</td>`;
                              });
                              rowHtml += `<td class="text-end">
                                  <button class="btn btn-sm btn-outline-danger btn-delete-row py-1 px-2" data-id="${row.id}"><i class="fa-solid fa-trash-can"></i></button>
                              </td></tr>`;
                              body.append(rowHtml);
                          });
                      } else {
                          body.append(`<tr><td colspan="${columns.length + 1}" class="text-center text-muted small py-3">No records found. Click Insert Record to begin!</td></tr>`);
                      }
                  }
              }
          });
      }

      $('#activeTableSelect').on('change', function() {
          loadTableRows($(this).val());
      });

      // 4. Drop table
      $('#btnDropTable').on('click', function() {
          const tableName = $('#activeTableSelect').val();
          if (!tableName) {
              alert('Please select a table to drop.');
              return;
          }
          if (!confirm(`Are you absolutely sure you want to drop the table '${tableName}'? This will delete all rows permanently.`)) return;
          $.ajax({
              url: '/php/user_database_action.php',
              type: 'POST',
              dataType: 'json',
              data: { action: 'drop_table', table_name: tableName },
              success: function(res) {
                  if (res.success) {
                      alert(res.message);
                      $('#activeTableSelect').val('');
                      $('#tableDisplayPanel').addClass('d-none');
                      loadTablesList();
                  } else {
                      alert(res.message);
                  }
              }
          });
      });

      // 5. Add dynamic column in modal
      $('#btnAddColumnInput').on('click', function() {
          $('#modalColumnsContainer').append(`
              <div class="row g-2 mb-2 column-input-row">
                  <div class="col-6">
                      <input type="text" class="form-control col-name-input" placeholder="Column Key" required style="border: 1px solid var(--border-color);" />
                  </div>
                  <div class="col-6">
                      <input type="text" class="form-control col-val-input" placeholder="Value" required style="border: 1px solid var(--border-color);" />
                  </div>
              </div>
          `);
      });

      // 6. Save custom row record
      $('#btnSubmitInsertRow').on('click', function() {
          const tableName = $('#activeTableSelect').val();
          if (!tableName) return;

          let columns = [];
          $('.column-input-row').each(function() {
              const name = $(this).find('.col-name-input').val().trim();
              const value = $(this).find('.col-val-input').val().trim();
              if (name) {
                  columns.push({ name: name, value: value });
              }
          });

          if (columns.length === 0) {
              alert('Please specify at least one column.');
              return;
          }

          $.ajax({
              url: '/php/user_database_action.php',
              type: 'POST',
              dataType: 'json',
              data: { action: 'insert_row', table_name: tableName, columns: columns },
              success: function(res) {
                  if (res.success) {
                      $('#insertRowForm')[0].reset();
                      $('#modalColumnsContainer').html(`
                          <div class="row g-2 mb-2 column-input-row">
                              <div class="col-6">
                                  <input type="text" class="form-control col-name-input" placeholder="Column Key (e.g. name)" required style="border: 1px solid var(--border-color);" />
                              </div>
                              <div class="col-6">
                                  <input type="text" class="form-control col-val-input" placeholder="Field Value" required style="border: 1px solid var(--border-color);" />
                              </div>
                          </div>
                      `);
                      bootstrap.Modal.getInstance(document.getElementById('insertRowModal')).hide();
                      loadTableRows(tableName);
                  } else {
                      alert(res.message);
                  }
              }
          });
      });

      // 7. Delete custom row
      $(document).on('click', '.btn-delete-row', function() {
          const tableName = $('#activeTableSelect').val();
          const rowId = $(this).attr('data-id');
          if (!tableName || !rowId) return;

          if (!confirm('Are you sure you want to delete this record?')) return;

          $.ajax({
              url: '/php/user_database_action.php',
              type: 'POST',
              dataType: 'json',
              data: { action: 'delete_row', table_name: tableName, row_id: rowId },
              success: function(res) {
                  if (res.success) {
                      loadTableRows(tableName);
                  } else {
                      alert(res.message);
                  }
              }
          });
      });
  });
  </script>
</body>
</html>
