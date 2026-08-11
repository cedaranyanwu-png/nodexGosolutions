<?php
/**
 * sidebar.php
 *
 * This is the upgraded modular sidebar navigation component shared by both the Admin and User dashboards.
 * Displays dynamic collapsible multi-level navigation lists matched to the active role.
 * Features hierarchical drop-downs under the main "Tools" header.
 * Designed with a premium White & Blue layout utilizing Bootstrap 5 and FontAwesome.
 * All lines are heavily commented to preserve scalability and maintainability.
 */

// Enable strict typing for safety
declare(strict_types=1);

// Guard against direct file inclusions without active session
$activeRole = strtolower((string)($_SESSION['role'] ?? 'tenant'));
$fullname   = $_SESSION['fullname'] ?? 'System User';
?>
<!-- Sidebar Navigation Container -->
<!-- Enforce fixed min-width and max-width of 260px with flex-shrink disabled to prevent the sidebar from disappearing or shrinking on smaller screen viewports -->
<div class="d-flex flex-column flex-shrink-0 p-3 bg-white border-end" style="width: 260px; min-width: 260px; max-width: 260px; min-height: 100vh; border-color: rgba(0, 114, 255, 0.1) !important; box-shadow: 4px 0 12px rgba(0, 114, 255, 0.03);">

    <!-- Workspace Brand Header -->
    <a href="/" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-primary text-decoration-none" style="font-family: 'Orbitron', sans-serif;">
        <i class="fa-solid fa-server fs-4 me-2"></i>
        <span class="fs-4 fw-bold">nodex<span class="text-dark">Go</span></span>
    </a>

    <hr class="my-3" style="border-color: rgba(0, 114, 255, 0.1);">

    <!-- Current User Info Section with role badges -->
    <div class="d-flex align-items-center px-2 mb-4">
        <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold bg-primary" style="width: 40px; height: 40px; font-size: 16px;">
            <?php echo strtoupper(substr($fullname, 0, 1)); ?>
        </div>
        <div class="ms-3 overflow-hidden">
            <h6 class="mb-0 text-dark fw-bold text-truncate" style="font-size: 14px;"><?php echo htmlspecialchars($fullname); ?></h6>
            <small class="text-muted text-uppercase fw-bold" style="font-size: 11px;">
                <?php
                if ($activeRole === 'super admin') {
                    echo 'Super Admin';
                } elseif ($activeRole === 'admin') {
                    echo 'Administrator';
                } else {
                    echo 'Tenant Account';
                }
                ?>
            </small>
        </div>
    </div>

    <!-- Collapsible Sidebar Navigation Items list -->
    <div class="overflow-y-auto flex-grow-1">
        <ul class="nav nav-pills flex-column gap-1 list-unstyled mb-auto">

            <!-- Standard Hub/Dashboard Core triggers -->
            <li class="nav-item">
                <?php if ($activeRole === 'super admin' || $activeRole === 'admin'): ?>
                    <a href="/admin/dashboard" class="nav-link d-flex align-items-center py-2 px-3 fw-bold rounded-pill text-dark hover-blue <?php echo str_contains($_SERVER['REQUEST_URI'], 'admin/dashboard') ? 'active text-white bg-primary' : ''; ?>">
                        <i class="fa-solid fa-chart-line me-2" style="width: 20px;"></i>
                        System Overview
                    </a>
                <?php else: ?>
                    <a href="/user/dashboard" class="nav-link d-flex align-items-center py-2 px-3 fw-bold rounded-pill text-dark hover-blue <?php echo str_contains($_SERVER['REQUEST_URI'], 'user/dashboard') ? 'active text-white bg-primary' : ''; ?>">
                        <i class="fa-solid fa-gauge-high me-2" style="width: 20px;"></i>
                        Workspace Hub
                    </a>
                <?php endif; ?>
            </li>

            <?php if ($activeRole === 'super admin' || $activeRole === 'admin'): ?>
                <li>
                    <a href="/cms/admin" class="nav-link d-flex align-items-center py-2 px-3 fw-bold text-dark rounded-pill hover-blue">
                        <i class="fa-solid fa-code me-2" style="width: 20px;"></i>
                        CMS Code Panel
                    </a>
                </li>
            <?php endif; ?>

            <hr class="my-3" style="border-color: rgba(0, 114, 255, 0.1);">

            <!-- Collapsible Multi-Level TOOLS Segment -->
            <li class="nav-item">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest block mb-2 px-3" style="font-size: 10px;">Tools Drawer</span>

                <div class="accordion accordion-flush" id="sidebarToolsAccordion" style="--bs-accordion-bg: transparent;">

                    <?php if ($activeRole !== 'super admin' && $activeRole !== 'admin'): ?>
                        <!-- ================= TENANT USER TOOLS ================= -->

                        <!-- Website Dropdown Menu -->
                        <div class="accordion-item border-0">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed py-2 px-3 fw-bold text-dark rounded-pill hover-blue shadow-none d-flex align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapseWebsiteTools" aria-expanded="false" aria-controls="collapseWebsiteTools" style="background: transparent; font-size: 14px;">
                                    <i class="fa-solid fa-earth-americas me-2 text-primary" style="width: 20px;"></i>
                                    Website
                                </button>
                            </h2>
                            <div id="collapseWebsiteTools" class="accordion-collapse collapse" data-bs-parent="#sidebarToolsAccordion">
                                <div class="accordion-body py-1 ps-4 pe-2 d-flex flex-column gap-1">
                                    <a href="/user/dashboard#build-website" class="nav-link d-flex align-items-center py-1.5 px-3 rounded-pill text-muted hover-blue text-xs fw-semibold">
                                        <i class="fa-solid fa-screwdriver-wrench me-2" style="font-size: 11px;"></i> Build Website
                                    </a>
                                    <a href="/user/dashboard#manage-files" class="nav-link d-flex align-items-center py-1.5 px-3 rounded-pill text-muted hover-blue text-xs fw-semibold">
                                        <i class="fa-solid fa-folder-open me-2" style="font-size: 11px;"></i> Manage Files
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Database Dropdown Menu -->
                        <div class="accordion-item border-0 mt-1">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed py-2 px-3 fw-bold text-dark rounded-pill hover-blue shadow-none d-flex align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDatabaseTools" aria-expanded="false" aria-controls="collapseDatabaseTools" style="background: transparent; font-size: 14px;">
                                    <i class="fa-solid fa-database me-2 text-primary" style="width: 20px;"></i>
                                    Database
                                </button>
                            </h2>
                            <div id="collapseDatabaseTools" class="accordion-collapse collapse" data-bs-parent="#sidebarToolsAccordion">
                                <div class="accordion-body py-1 ps-4 pe-2 d-flex flex-column gap-1">
                                    <a href="/user/dashboard#user-db-manager" class="nav-link d-flex align-items-center py-1.5 px-3 rounded-pill text-muted hover-blue text-xs fw-semibold">
                                        <i class="fa-solid fa-list-check me-2" style="font-size: 11px;"></i> Manage Database
                                    </a>
                                    <a href="/user/dashboard#user-db-manager" class="nav-link d-flex align-items-center py-1.5 px-3 rounded-pill text-muted hover-blue text-xs fw-semibold">
                                        <i class="fa-solid fa-circle-plus me-2" style="font-size: 11px;"></i> Create Database
                                    </a>
                                    <a href="/user/dashboard#user-db-manager" class="nav-link d-flex align-items-center py-1.5 px-3 rounded-pill text-muted hover-blue text-xs fw-semibold">
                                        <i class="fa-solid fa-users-gear me-2" style="font-size: 11px;"></i> Database Users
                                    </a>
                                </div>
                            </div>
                        </div>

                    <?php else: ?>
                        <!-- ================= ADMIN TOOLS ================= -->

                        <!-- Websites (Admin) Dropdown Menu -->
                        <div class="accordion-item border-0">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed py-2 px-3 fw-bold text-dark rounded-pill hover-blue shadow-none d-flex align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAdminWebsites" aria-expanded="false" aria-controls="collapseAdminWebsites" style="background: transparent; font-size: 14px;">
                                    <i class="fa-solid fa-globe me-2 text-primary" style="width: 20px;"></i>
                                    Websites
                                </button>
                            </h2>
                            <div id="collapseAdminWebsites" class="accordion-collapse collapse" data-bs-parent="#sidebarToolsAccordion">
                                <div class="accordion-body py-1 ps-4 pe-2 d-flex flex-column gap-1">
                                    <a href="/admin/dashboard#build-website" class="nav-link d-flex align-items-center py-1.5 px-3 rounded-pill text-muted hover-blue text-xs fw-semibold">
                                        <i class="fa-solid fa-laptop-code me-2" style="font-size: 11px;"></i> Build Website
                                    </a>
                                    <a href="/admin/dashboard#websites-section" class="nav-link d-flex align-items-center py-1.5 px-3 rounded-pill text-muted hover-blue text-xs fw-semibold">
                                        <i class="fa-solid fa-sliders me-2" style="font-size: 11px;"></i> Manage Websites
                                    </a>
                                    <a href="/admin/dashboard#websites-section" class="nav-link d-flex align-items-center py-1.5 px-3 rounded-pill text-muted hover-blue text-xs fw-semibold">
                                        <i class="fa-solid fa-folder-open me-2" style="font-size: 11px;"></i> Manage Files
                                    </a>
                                    <a href="/admin/dashboard" class="nav-link d-flex align-items-center py-1.5 px-3 rounded-pill text-muted hover-blue text-xs fw-semibold">
                                        <i class="fa-solid fa-object-group me-2" style="font-size: 11px;"></i> Templates
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Database (Admin) Dropdown Menu -->
                        <div class="accordion-item border-0 mt-1">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed py-2 px-3 fw-bold text-dark rounded-pill hover-blue shadow-none d-flex align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAdminDatabases" aria-expanded="false" aria-controls="collapseAdminDatabases" style="background: transparent; font-size: 14px;">
                                    <i class="fa-solid fa-database me-2 text-primary" style="width: 20px;"></i>
                                    Database
                                </button>
                            </h2>
                            <div id="collapseAdminDatabases" class="accordion-collapse collapse" data-bs-parent="#sidebarToolsAccordion">
                                <div class="accordion-body py-1 ps-4 pe-2 d-flex flex-column gap-1">
                                    <a href="/admin/dashboard" class="nav-link d-flex align-items-center py-1.5 px-3 rounded-pill text-muted hover-blue text-xs fw-semibold">
                                        <i class="fa-solid fa-database me-2" style="font-size: 11px;"></i> Manage Databases
                                    </a>
                                    <a href="/admin/dashboard" class="nav-link d-flex align-items-center py-1.5 px-3 rounded-pill text-muted hover-blue text-xs fw-semibold">
                                        <i class="fa-solid fa-users me-2" style="font-size: 11px;"></i> Database Users
                                    </a>
                                    <a href="/admin/dashboard" class="nav-link d-flex align-items-center py-1.5 px-3 rounded-pill text-muted hover-blue text-xs fw-semibold">
                                        <i class="fa-solid fa-gears me-2" style="font-size: 11px;"></i> Database Settings
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- System (Admin) Dropdown Menu -->
                        <div class="accordion-item border-0 mt-1">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed py-2 px-3 fw-bold text-dark rounded-pill hover-blue shadow-none d-flex align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAdminSystem" aria-expanded="false" aria-controls="collapseAdminSystem" style="background: transparent; font-size: 14px;">
                                    <i class="fa-solid fa-server me-2 text-primary" style="width: 20px;"></i>
                                    System
                                </button>
                            </h2>
                            <div id="collapseAdminSystem" class="accordion-collapse collapse" data-bs-parent="#sidebarToolsAccordion">
                                <div class="accordion-body py-1 ps-4 pe-2 d-flex flex-column gap-1">
                                    <a href="/admin/dashboard#activity-logs-section" class="nav-link d-flex align-items-center py-1.5 px-3 rounded-pill text-muted hover-blue text-xs fw-semibold">
                                        <i class="fa-solid fa-rectangle-list me-2" style="font-size: 11px;"></i> Logs
                                    </a>
                                    <a href="/admin/dashboard#activity-logs-section" class="nav-link d-flex align-items-center py-1.5 px-3 rounded-pill text-muted hover-blue text-xs fw-semibold">
                                        <i class="fa-solid fa-chart-line me-2" style="font-size: 11px;"></i> Activity
                                    </a>
                                    <a href="/admin/dashboard#payment-settings-section" class="nav-link d-flex align-items-center py-1.5 px-3 rounded-pill text-muted hover-blue text-xs fw-semibold">
                                        <i class="fa-solid fa-screwdriver-wrench me-2" style="font-size: 11px;"></i> System Settings
                                    </a>
                                </div>
                            </div>
                        </div>

                    <?php endif; ?>

                </div>
            </li>

            <hr class="my-3" style="border-color: rgba(0, 114, 255, 0.1);">

            <!-- Global Action Links (Visit Homepage & Logout) -->
            <li>
                <a href="/" class="nav-link d-flex align-items-center py-2 px-3 fw-bold text-dark rounded-pill hover-blue" target="_blank">
                    <i class="fa-solid fa-globe me-2" style="width: 20px;"></i>
                    Visit Homepage
                </a>
            </li>
            <li>
                <a href="/login" class="nav-link d-flex align-items-center py-2 px-3 fw-bold text-danger rounded-pill hover-danger">
                    <i class="fa-solid fa-sign-out-alt me-2" style="width: 20px;"></i>
                    Log Out
                </a>
            </li>
        </ul>
    </div>

    <!-- Sidebar footer/versioning indicator -->
    <div class="px-2 mt-3 text-center">
        <small class="text-muted font-monospace" style="font-size: 10px;">v<?php echo date('Y.m.d'); ?>.stable</small>
    </div>
</div>

<style>
    /* Styling adjustments to cleanly style Bootstrap Accordion within custom Sidebar */
    .accordion-button::after {
        background-size: 10px;
        width: 10px;
        height: 10px;
    }
    .accordion-button:not(.collapsed)::after {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%230056b3'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
    }
    .hover-blue:hover {
        background-color: rgba(0, 114, 255, 0.06) !important;
        color: #0072ff !important;
    }
    .hover-danger:hover {
        background-color: rgba(239, 68, 68, 0.06) !important;
        color: #ef4444 !important;
    }
</style>
