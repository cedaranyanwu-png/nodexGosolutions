<?php
/**
 * dashboard.php
 *
 * User and hosting tenant control dashboard. Enables hosting deployments and database creations.
 */

// Enable strict typing for safety
declare(strict_types=1);

// Set base system domain constant for tenant hosting
$base_domain = "nodexplatform.com.ng";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mini Hosting Admin - <?php // Render base domain string
    echo $base_domain; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<div class="d-flex" id="wrapper">

    <!-- Sidebar Navigation -->
    <div class="bg-dark border-end text-white" id="sidebar-wrapper">
        <div class="sidebar-heading p-3 border-bottom border-secondary d-flex align-items-center">
            <i class="fa-solid fa-server me-2 text-primary fa-lg"></i>
            <span class="fw-bold">NodeX Hosting</span>
        </div>

        <!-- Admin Profile Summary Card -->
        <div class="p-3 bg-secondary bg-opacity-10 border-bottom border-secondary">
            <div class="d-flex align-items-center">
                <div class="avatar bg-primary text-white rounded-circle me-3 d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">
                    AD
                </div>
                <div>
                    <h6 class="mb-0 fw-bold">Admin Workspace</h6>
                    <small class="text-muted">admin@<?php // Output base domain
                    echo $base_domain; ?></small>
                </div>
            </div>
        </div>

        <!-- Sidebar Menu Links -->
        <div class="list-group list-group-flush pt-2">
            <a class="list-group-item list-group-item-action list-group-item-dark active nav-link-item" href="#view-overview" id="nav-overview">
                <i class="fa-solid fa-chart-pie me-2"></i>Dashboard Overview
            </a>
            <a class="list-group-item list-group-item-action list-group-item-dark nav-link-item" href="#view-projects" id="nav-projects">
                <i class="fa-solid fa-globe me-2"></i>Hosted Projects
            </a>
            <a class="list-group-item list-group-item-action list-group-item-dark nav-link-item" href="#view-database" id="nav-database">
                <i class="fa-solid fa-database me-2"></i>Database Manager
            </a>
            <a class="list-group-item list-group-item-action list-group-item-dark nav-link-item" href="#view-user-admin" id="nav-user-admin">
                <i class="fa-solid fa-user-shield me-2"></i>User Admin Settings
            </a>
        </div>
    </div>

    <!-- Main Content Area -->
    <div id="page-content-wrapper" class="w-100 bg-light">

        <!-- Top Header -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-4 py-3 shadow-sm">
            <div class="d-flex align-items-center justify-content-between w-100">
                <h5 class="mb-0 fw-bold text-dark" id="page-title">Dashboard Overview</h5>
                <div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 me-2">
                        <i class="fa-solid fa-circle me-1 small"></i> System Active
                    </span>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createProjectModal">
                        <i class="fa-solid fa-cloud-arrow-up me-1"></i> New Project
                    </button>
                </div>
            </div>
        </nav>

        <div class="container-fluid p-4">

            <!-- SECTION 1: OVERVIEW DASHBOARD -->
            <div class="view-section" id="section-overview">
                <!-- Analytics Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-body p-3 d-flex align-items-center">
                                <div class="icon-shape bg-primary text-white rounded-3 me-3 p-3">
                                    <i class="fa-solid fa-globe fa-xl"></i>
                                </div>
                                <div>
                                    <span class="text-muted small fw-semibold">Active Subdomains</span>
                                    <h4 class="fw-bold mb-0" id="stat-count">0</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-body p-3 d-flex align-items-center">
                                <div class="icon-shape bg-success text-white rounded-3 me-3 p-3">
                                    <i class="fa-solid fa-hard-drive fa-xl"></i>
                                </div>
                                <div>
                                    <span class="text-muted small fw-semibold">Public Storage</span>
                                    <h4 class="fw-bold mb-0" id="stat-storage">0 KB</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-body p-3 d-flex align-items-center">
                                <div class="icon-shape bg-info text-white rounded-3 me-3 p-3">
                                    <i class="fa-solid fa-database fa-xl"></i>
                                </div>
                                <div>
                                    <span class="text-muted small fw-semibold">Active Databases</span>
                                    <h4 class="fw-bold mb-0" id="stat-db-count">0</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-body p-3 d-flex align-items-center">
                                <div class="icon-shape bg-warning text-white rounded-3 me-3 p-3">
                                    <i class="fa-solid fa-folder-tree fa-xl"></i>
                                </div>
                                <div>
                                    <span class="text-muted small fw-semibold">Target Root</span>
                                    <h4 class="fw-bold mb-0 text-dark">/public/</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: HOSTED PROJECTS -->
            <div class="view-section d-none" id="section-projects">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3">
                        <h5 class="fw-bold mb-0">Hosted Subdomains</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Subdomain URL</th>
                                    <th>Status</th>
                                    <th>Server Directory</th>
                                    <th>Size</th>
                                    <th>Created Date</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="projects-table-body">
                                <!-- Dynamic content -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- SECTION 3: DATABASE MANAGER -->
            <div class="view-section d-none" id="section-database">
                <div class="row g-4">
                    <!-- Create DB Panel -->
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-header bg-white py-3">
                                <h6 class="fw-bold mb-0"><i class="fa-solid fa-plus me-2 text-primary"></i>Create New Database</h6>
                            </div>
                            <div class="card-body">
                                <form id="form-create-db">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Database Name</label>
                                        <input type="text" class="form-control" name="db_name" placeholder="my_project_db" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Database Engine</label>
                                        <select class="form-select" name="db_type">
                                            <option value="json">JSON Flat File Store (.json)</option>
                                            <option value="sqlite">SQLite Database (.sqlite)</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-database me-1"></i> Create Storage</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- DB List & Inspector -->
                    <div class="col-md-8">
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-header bg-white py-3">
                                <h6 class="fw-bold mb-0"><i class="fa-solid fa-table me-2 text-primary"></i>Database Repositories</h6>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Name</th>
                                            <th>Engine</th>
                                            <th>Path</th>
                                            <th>Size</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="db-table-body">
                                        <!-- Dynamic content -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 4: USER ADMIN PROPERTIES -->
            <div class="view-section d-none" id="section-user-admin">
                <div class="card border-0 shadow-sm rounded-3 max-width-700">
                    <div class="card-header bg-white py-3">
                        <h5 class="fw-bold mb-0"><i class="fa-solid fa-user-gear me-2 text-primary"></i>User Admin Properties</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Primary Administrator Email</label>
                            <input type="email" class="form-control" value="admin@<?php // Output base domain
                            echo $base_domain; ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Root Hosting Path</label>
                            <input type="text" class="form-control font-monospace" value="/public/" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Default Subdomain Domain</label>
                            <input type="text" class="form-control" value="<?php // Output base domain
                            echo $base_domain; ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Max Upload Limit</label>
                            <input type="text" class="form-control" value="50 MB (ZIP / HTML)" readonly>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Modal: New Project Deployment -->
<div class="modal fade" id="createProjectModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Deploy Subdomain Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-create-project" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Subdomain Name</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="subdomain" id="input-subdomain" placeholder="myproject" required>
                            <span class="input-group-text">.<?php // Output base domain
                            echo $base_domain; ?></span>
                        </div>
                    </div>

                    <ul class="nav nav-tabs nav-fill mb-3">
                        <li class="nav-item">
                            <button class="nav-link active fw-bold" id="upload-tab" data-bs-toggle="tab" data-bs-target="#tab-upload" type="button">
                                <i class="fa-solid fa-file-zipper me-2"></i>Upload ZIP or File
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold" id="editor-tab" data-bs-toggle="tab" data-bs-target="#tab-editor" type="button">
                                <i class="fa-solid fa-code me-2"></i>Raw HTML Editor
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active p-2" id="tab-upload">
                            <input type="file" class="form-control mb-2" name="project_file" accept=".zip,.html,.htm">
                            <small class="text-muted">ZIP files auto-extract into <code>/public/[subdomain]/</code>.</small>
                        </div>
                        <div class="tab-pane fade p-2" id="tab-editor">
                            <textarea class="form-control font-monospace" name="html_content" rows="6" placeholder="<h1>Hello World</h1>"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btn-deploy">Deploy Project</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- jQuery & Bootstrap 5 Bundle JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom jQuery Engine -->
<script src="/js/userdash/app.js"></script>
</body>
</html>
