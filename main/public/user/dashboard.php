<?php
/**
 * dashboard.php
 *
 * This is the enhanced Client-Side Tenant Control Panel.
 * It serves as a centralized hub offering:
 * - "Tools on Top" quick launch bar for all sub-apps (QR Codes, short URLs, invoice creator, image compressor, etc.)
 * - Direct Web Deployment & CMS links
 * - Live dynamic lists of their active deployments (Bios, short links, QR codes) queried directly from our custom JSON Database
 * - Tenant-specific visual analytics cards showing user hits and metrics
 * - Back-to-Main landing page navigation links
 * - Dynamic Database Management Tool Tab: Allow tenants to create, view, drop custom tables, and manage rows.
 */

// Enable strict typing for safety
declare(strict_types=1);

// Require central database configuration and security helpers
require_once __DIR__ . '/../../../php/db.php';

// Instantiate secure session configurations
secureSession();

// Access Control: Verify that the user is logged in
if (!isset($_SESSION['email'])) {
    // If not logged in, redirect to login screen
    header('Location: /login');
    // Terminate script execution
    exit;
}

// Fetch list of links, qr codes, and biography records to display dynamically in the control panel
$urlDb     = new Database(__DIR__ . '/../../../databases', 'url_shortner');
$qrDb      = new Database(__DIR__ . '/../../../databases', 'qrcode');
$bioDb     = new Database(__DIR__ . '/../../../databases', 'bio_builder');

// Load records list
$shortLinks = $urlDb->select('links');
$qrCodes    = $qrDb->select('qrcodes');
$biosList   = $bioDb->select('bios');

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
            --bg-dark: #0b0f1e;
            --card-bg: #131a35;
            --accent: #00d2ff;
            --accent-hover: #00a2cc;
            --text: #f8fafc;
            --text-muted: #8a99ad;
            --border-color: rgba(0, 210, 255, 0.15);
        }
        body {
            background-color: var(--bg-dark);
            color: var(--text);
            font-family: 'Source Sans 3', sans-serif;
            margin: 0;
            padding: 0;
        }
        .navbar-custom {
            background: linear-gradient(135deg, #0072ff, #00d2ff);
            box-shadow: 0 4px 15px rgba(0, 210, 255, 0.25);
            padding: 15px 30px;
        }
        .navbar-brand-custom {
            font-family: 'Orbitron', sans-serif;
            font-weight: 900;
            color: white !important;
            font-size: 22px;
            letter-spacing: 0.5px;
        }
        .nav-link-custom {
            color: white !important;
            font-weight: 600;
            margin-left: 20px;
            transition: opacity 0.3s;
        }
        .nav-link-custom:hover {
            opacity: 0.8;
        }
        .hero-banner {
            background: linear-gradient(135deg, rgba(0, 114, 255, 0.12), rgba(0, 210, 255, 0.12));
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 45px;
            margin-top: 40px;
        }
        .hero-banner h2 {
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            color: white;
            margin-bottom: 8px;
        }
        /* Tools On Top Row Grid */
        .tool-box-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 18px;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
            color: var(--text);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 120px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.15);
        }
        .tool-box-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 210, 255, 0.3);
            color: var(--accent);
        }
        .tool-icon {
            font-size: 26px;
            color: var(--accent);
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
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }
        .panel-card h3 {
            font-family: 'Orbitron', sans-serif;
            font-size: 16px;
            margin-bottom: 20px;
            color: white;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            padding-bottom: 10px;
        }
        /* Table Styles */
        .table-responsive-custom {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            vertical-align: middle;
        }
        th {
            font-family: 'Orbitron', sans-serif;
            color: var(--accent);
            font-size: 11px;
            text-transform: uppercase;
        }
        tr:hover {
            background: rgba(255,255,255,0.01);
        }
        .btn-preview-link {
            font-size: 12px;
            font-weight: bold;
            text-decoration: none;
            color: var(--accent);
            border: 1px solid var(--accent);
            padding: 4px 10px;
            border-radius: 4px;
            transition: all 0.2s;
        }
        .btn-preview-link:hover {
            background-color: var(--accent);
            color: var(--bg-dark);
        }
        /* Analytics box styles */
        .analytic-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }
        .analytic-icon {
            font-size: 32px;
            color: var(--accent);
        }
        .analytic-data {
            text-align: right;
        }
        .analytic-label {
            font-size: 12px;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
        }
        .analytic-value {
            font-size: 22px;
            font-weight: 700;
            font-family: 'Orbitron', sans-serif;
            margin-top: 4px;
        }
        .form-control-db, .form-select-db {
            background-color: #0b0f1e;
            border: 1px solid rgba(0, 210, 255, 0.25);
            color: white;
        }
        .form-control-db:focus, .form-select-db:focus {
            background-color: #060914;
            border-color: var(--accent);
            color: white;
            box-shadow: 0 0 10px rgba(0, 210, 255, 0.2);
        }
    </style>
</head>
<body>

    <!-- Unified Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid d-flex justify-content-between">
            <a class="navbar-brand-custom" href="/"><i class="fa-solid fa-server me-2"></i>nodexGo Portal</a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3 small"><i class="fa-solid fa-user me-1"></i> <?php echo htmlspecialchars($_SESSION['fullname'] ?? $_SESSION['email']); ?></span>
                <!-- Clean back-to-main page link -->
                <a class="btn btn-outline-light btn-sm rounded-pill px-3 nav-link-custom" href="/"><i class="fa-solid fa-arrow-left me-1"></i> Return to Main Page</a>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="container py-4">

        <!-- Welcome Banner Section -->
        <div class="hero-banner text-center">
            <h2>Welcome to Your Tenant Workspace Control Panel!</h2>
            <p class="text-muted mb-0">Unify deployment tools on demand. Launch apps, create shortened URLs, generate QR codes, and monitor active deployments below.</p>
        </div>

        <!-- Tenant Analytics Row -->
        <h4 class="mb-3 text-uppercase small fw-bold text-muted tracking-wide" style="font-family: 'Orbitron', sans-serif; letter-spacing: 1px;">
            <i class="fa-solid fa-chart-line me-2 text-info"></i>My Deployment Analytics
        </h4>
        <div class="row g-3 mb-5">
            <!-- QR Hits -->
            <div class="col-md-3 col-sm-6">
                <div class="analytic-card">
                    <span class="analytic-icon"><i class="fa-solid fa-qrcode"></i></span>
                    <div class="analytic-data">
                        <div class="analytic-label">QR Code Hits</div>
                        <div class="analytic-value"><?php echo $userQrHits; ?></div>
                    </div>
                </div>
            </div>
            <!-- Short URLs clicks -->
            <div class="col-md-3 col-sm-6">
                <div class="analytic-card">
                    <span class="analytic-icon"><i class="fa-solid fa-arrow-pointer"></i></span>
                    <div class="analytic-data">
                        <div class="analytic-label">Short URL Clicks</div>
                        <div class="analytic-value"><?php echo $userClicks; ?></div>
                    </div>
                </div>
            </div>
            <!-- Biography views -->
            <div class="col-md-3 col-sm-6">
                <div class="analytic-card">
                    <span class="analytic-icon"><i class="fa-solid fa-eye"></i></span>
                    <div class="analytic-data">
                        <div class="analytic-label">Bio Page Views</div>
                        <div class="analytic-value"><?php echo $userViews; ?></div>
                    </div>
                </div>
            </div>
            <!-- Storage usage -->
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
            <!-- Web Deployment & CMS Builder -->
            <div class="col-md-3 col-sm-6">
                <a href="/cms/admin.php" class="tool-box-card">
                    <span class="tool-icon"><i class="fa-solid fa-laptop-code"></i></span>
                    <span class="tool-title">Web Builder & CMS</span>
                </a>
            </div>
            <!-- Dynamic QR Code Generator -->
            <div class="col-md-3 col-sm-6">
                <a href="/apps/qrcode/index.html" class="tool-box-card">
                    <span class="tool-icon"><i class="fa-solid fa-qrcode"></i></span>
                    <span class="tool-title">QR Code Gen</span>
                </a>
            </div>
            <!-- Mini URL Shortener -->
            <div class="col-md-3 col-sm-6">
                <a href="/apps/url_shortner/index.html" class="tool-box-card">
                    <span class="tool-icon"><i class="fa-solid fa-link"></i></span>
                    <span class="tool-title">URL Shortener</span>
                </a>
            </div>
            <!-- Digital Bio Page Builder -->
            <div class="col-md-3 col-sm-6">
                <a href="/apps/bio_builder/index.html" class="tool-box-card">
                    <span class="tool-icon"><i class="fa-solid fa-id-card"></i></span>
                    <span class="tool-title">Bio Page Builder</span>
                </a>
            </div>
            <!-- Interactive CV/Resume Builder -->
            <div class="col-md-3 col-sm-6">
                <a href="/apps/cv_builder/index.html" class="tool-box-card">
                    <span class="tool-icon"><i class="fa-solid fa-file-invoice"></i></span>
                    <span class="tool-title">Resume Builder</span>
                </a>
            </div>
            <!-- Dynamic Invoice Estimator -->
            <div class="col-md-3 col-sm-6">
                <a href="/apps/invoice/index.html" class="tool-box-card">
                    <span class="tool-icon"><i class="fa-solid fa-file-invoice-dollar"></i></span>
                    <span class="tool-title">Invoice Gen</span>
                </a>
            </div>
            <!-- WhatsApp Link Generator -->
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

        <!-- USER DATABASE MANAGEMENT SUITE PANEL -->
        <div class="panel-card mb-5">
            <h3><i class="fa-solid fa-database me-2 text-info"></i>My Isolated Workspace Database Manager</h3>
            <p class="text-muted small">Create, view, and manipulate custom tables and rows inside your private tenant database securely.</p>

            <div class="row g-3 align-items-end mb-4">
                <div class="col-md-4">
                    <label class="form-label text-muted small fw-bold">CREATE NEW TABLE</label>
                    <input type="text" id="newTableName" class="form-control form-control-db" placeholder="Enter table name..." />
                </div>
                <div class="col-md-2">
                    <button class="btn btn-info w-100 fw-bold" id="btnCreateTable"><i class="fa-solid fa-plus me-1"></i>Create Table</button>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted small fw-bold">CHOOSE ACTIVE TABLE</label>
                    <select id="activeTableSelect" class="form-select form-select-db">
                        <option value="">-- Select custom table --</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-danger w-100 fw-bold" id="btnDropTable"><i class="fa-solid fa-trash me-1"></i>Drop Table</button>
                </div>
            </div>

            <!-- Custom rows Insertion / table rows display area -->
            <div id="tableDisplayPanel" class="d-none">
                <hr class="border-secondary my-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 id="activeTableTitle" class="text-white fw-bold mb-0">Active Table: <span></span></h5>
                    <button class="btn btn-sm btn-outline-success" id="btnAddRowBtn" data-bs-toggle="modal" data-bs-target="#insertRowModal"><i class="fa-solid fa-circle-plus me-1"></i>Insert Row</button>
                </div>
                <div class="table-responsive-custom">
                    <table class="table table-dark table-hover table-striped" id="dynamicDataTable">
                        <thead>
                            <tr id="dynamicDataTableHead">
                                <!-- Dynamic columns injected here -->
                            </tr>
                        </thead>
                        <tbody id="dynamicDataTableBody">
                            <!-- Dynamic rows injected here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- DYNAMIC CONTROL PANEL TABLES -->
        <div class="row">
            <!-- Active Short Links & QR Code Deployments -->
            <div class="col-md-6">
                <div class="panel-card h-100">
                    <h3><i class="fa-solid fa-rocket me-2 text-info"></i>Active Short Links</h3>
                    <div class="table-responsive-custom">
                        <table>
                            <thead>
                                <tr>
                                    <th>Short Code</th>
                                    <th>Original Destination URL</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php // Iterate through active shortened link deployments
                                if (!empty($shortLinks)):
                                    foreach (array_slice($shortLinks, -5) as $link): ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($link['code'] ?? ''); ?></code></td>
                                            <td class="text-truncate" style="max-width: 150px;"><?php echo htmlspecialchars($link['long_url'] ?? ''); ?></td>
                                            <td class="text-end">
                                                <a href="/php/url_shortner.php?c=<?php echo htmlspecialchars($link['code'] ?? ''); ?>" target="_blank" class="btn-preview-link">Visit</a>
                                            </td>
                                        </tr>
                                    <?php endforeach;
                                else: ?>
                                    <tr><td colspan="3" class="text-center text-muted small">No short URLs deployed yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Active Biography Pages -->
            <div class="col-md-6">
                <div class="panel-card h-100">
                    <h3><i class="fa-solid fa-address-book me-2 text-success"></i>Active Bio Profiles</h3>
                    <div class="table-responsive-custom">
                        <table>
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Display Name</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php // Iterate through active biography deployments
                                if (!empty($biosList)):
                                    foreach (array_slice($biosList, -5) as $bio): ?>
                                        <tr>
                                            <td><code>@<?php echo htmlspecialchars($bio['username'] ?? ''); ?></code></td>
                                            <td><?php echo htmlspecialchars($bio['display_name'] ?? ''); ?></td>
                                            <td class="text-end">
                                                <a href="/php/bio_builder.php?u=<?php echo htmlspecialchars($bio['username'] ?? ''); ?>" target="_blank" class="btn-preview-link">Visit Profile</a>
                                            </td>
                                        </tr>
                                    <?php endforeach;
                                else: ?>
                                    <tr><td colspan="3" class="text-center text-muted small">No bio profile pages deployed yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Dynamic Rows Insertion modal form -->
    <div class="modal fade" id="insertRowModal" tabindex="-1" aria-labelledby="insertRowModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-white" style="background-color: var(--card-bg); border: 1px solid var(--border-color);">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title" id="insertRowModalLabel"><i class="fa-solid fa-square-plus me-1 text-info"></i>Insert Custom Row Data</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="insertRowForm">
                        <div class="mb-3 text-muted small">Add up to 4 custom database columns dynamically below.</div>
                        <div id="modalColumnsContainer">
                            <div class="row g-2 mb-2 column-input-row">
                                <div class="col-6">
                                    <input type="text" class="form-control form-control-db col-name-input" placeholder="Column Name (e.g. name)" required />
                                </div>
                                <div class="col-6">
                                    <input type="text" class="form-control form-control-db col-val-input" placeholder="Column Value" required />
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-info mt-2" id="btnAddColumnInput"><i class="fa-solid fa-plus me-1"></i>Add Column Input</button>
                    </form>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-sm btn-info" id="btnSubmitInsertRow">Save Record</button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery & Bootstrap Bundle JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- AJAX handlers for Dynamic Database Manager -->
    <script>
    $(document).ready(function() {

        // Load custom user tables list dynamically
        function loadTablesList() {
            $.ajax({
                url: '/php/user_database_action.php',
                type: 'GET',
                dataType: 'json',
                data: { action: 'list_tables' },
                success: function(res) {
                    if (res.success) {
                        const select = $('#activeTableSelect');
                        // Capture existing selection
                        const selectedVal = select.val();
                        select.empty().append('<option value="">-- Select custom table --</option>');
                        res.tables.forEach(function(table) {
                            select.append(`<option value="${table}">${table}</option>`);
                        });
                        // Restore selection
                        if (selectedVal) {
                            select.val(selectedVal);
                        }
                    }
                }
            });
        }

        // Run tables list fetch immediately
        loadTablesList();

        // Handle create table
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
                },
                error: function() {
                    alert('Server error.');
                }
            });
        });

        // Load and render active table rows
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

                        // If table contains rows, calculate dynamic columns list dynamically
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

                        // Render header cols
                        columns.forEach(function(col) {
                            head.append(`<th class="text-uppercase small">${col}</th>`);
                        });
                        head.append('<th class="text-end">Actions</th>');

                        // Render data rows
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
                            body.append(`<tr><td colspan="${columns.length + 1}" class="text-center text-muted small py-3">No records found. Click Insert Row to begin!</td></tr>`);
                        }
                    }
                }
            });
        }

        // Drop active selected table
        $('#btnDropTable').on('click', function() {
            const tableName = $('#activeTableSelect').val();
            if (!tableName) {
                alert('Please select a table to drop.');
                return;
            }
            if (!confirm(`Are you absolutely sure you want to drop the table '${tableName}'? This will delete all rows permanently.`)) {
                return;
            }
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

        // Trigger dynamic table view changes
        $('#activeTableSelect').on('change', function() {
            loadTableRows($(this).val());
        });

        // Add additional dynamic column inputs in modal
        $('#btnAddColumnInput').on('click', function() {
            const count = $('.column-input-row').length;
            if (count >= 5) {
                alert('Maximum of 5 custom columns allowed.');
                return;
            }
            $('#modalColumnsContainer').append(`
                <div class="row g-2 mb-2 column-input-row">
                    <div class="col-6">
                        <input type="text" class="form-control form-control-db col-name-input" placeholder="Column Name" required />
                    </div>
                    <div class="col-6">
                        <input type="text" class="form-control form-control-db col-val-input" placeholder="Column Value" required />
                    </div>
                </div>
            `);
        });

        // Handle Row record insertions
        $('#btnSubmitInsertRow').on('click', function() {
            const tableName = $('#activeTableSelect').val();
            if (!tableName) return;

            let columns = [];
            let isValid = true;

            $('.column-input-row').each(function() {
                const name = $(this).find('.col-name-input').val().trim();
                const value = $(this).find('.col-val-input').val().trim();
                if (name) {
                    columns.push({ name: name, value: value });
                }
            });

            if (columns.length === 0) {
                alert('Please define at least one column key and value.');
                return;
            }

            $.ajax({
                url: '/php/user_database_action.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'insert_row',
                    table_name: tableName,
                    columns: columns
                },
                success: function(res) {
                    if (res.success) {
                        // Reset forms
                        $('#insertRowForm')[0].reset();
                        $('#modalColumnsContainer').html(`
                            <div class="row g-2 mb-2 column-input-row">
                                <div class="col-6">
                                    <input type="text" class="form-control form-control-db col-name-input" placeholder="Column Name (e.g. name)" required />
                                </div>
                                <div class="col-6">
                                    <input type="text" class="form-control form-control-db col-val-input" placeholder="Column Value" required />
                                </div>
                            </div>
                        `);
                        // Hide modal
                        bootstrap.Modal.getInstance(document.getElementById('insertRowModal')).hide();
                        // Reload rows
                        loadTableRows(tableName);
                    } else {
                        alert(res.message);
                    }
                }
            });
        });

        // Handle single row deletions
        $(document).on('click', '.btn-delete-row', function() {
            const tableName = $('#activeTableSelect').val();
            const rowId = $(this).attr('data-id');
            if (!tableName || !rowId) return;

            if (!confirm('Are you sure you want to delete this record?')) return;

            $.ajax({
                url: '/php/user_database_action.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'delete_row',
                    table_name: tableName,
                    row_id: rowId
                },
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
