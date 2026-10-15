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
if (session_status() === PHP_SESSION_NONE) {
    secureSession();
}
// Retrieve actual logged-in user's role and details from the database dynamically in real-time
$sidebarDb = new Database(__DIR__ . '/../../databases', 'system');
$sidebarUser = isset($_SESSION['email']) ? $sidebarDb->selectOne('users', ['email' => $_SESSION['email']]) : null;
$activeRole = strtolower((string)($sidebarUser['role'] ?? $_SESSION['role'] ?? 'tenant'));
$fullname   = $sidebarUser['fullname'] ?? $_SESSION['fullname'] ?? 'System User';
?>
<!-- Sidebar Responsive Backdrop/Overlay (Mobile only) -->
<div class="sidebar-overlay d-md-none" id="sidebarOverlay" style="display:none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background-color: rgba(15, 23, 42, 0.5); z-index: 1040;"></div>

<!-- Sidebar Navigation Container -->
<!-- Enforce fixed min-width and max-width of 260px with flex-shrink disabled. Uses mobile offcanvas transitions -->
<div class="sidebar-container d-flex flex-column flex-shrink-0 p-3 bg-white border-end" id="sidebarContainer" style="width: 260px; min-width: 260px; max-width: 260px; min-height: 100vh; border-color: rgba(0, 114, 255, 0.1) !important; box-shadow: 4px 0 12px rgba(0, 114, 255, 0.03); z-index: 1050; transition: transform 0.3s ease;">

    <!-- Workspace Brand Header -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <a href="/" class="d-flex align-items-center text-primary text-decoration-none" style="font-family: 'Orbitron', sans-serif;">
            <i class="fa-solid fa-server fs-4 me-2"></i>
            <span class="fs-4 fw-bold">nodex<span class="text-dark">Go</span></span>
        </a>
        <!-- Close button (Mobile only) -->
        <button class="btn btn-sm btn-light border-0 d-md-none rounded-circle" id="sidebarCloseBtn" type="button">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

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
                $roleLabelMap = [
                    'super admin' => 'Super Admin',
                    'superadmin' => 'Super Admin',
                    'admin' => 'Administrator',
                    'manager' => 'Manager',
                    'support' => 'Support Staff',
                    'moderator' => 'Moderator',
                    'financial' => 'Financial Officer',
                    'marketing_head' => 'Marketing Head',
                    'marketinghead' => 'Marketing Head'
                ];
                echo htmlspecialchars($roleLabelMap[$activeRole] ?? 'Tenant Account');
                ?>
            </small>
        </div>
    </div>

    <!-- Collapsible Sidebar Navigation Items list -->
    <div class="overflow-y-auto flex-grow-1">
        <ul class="nav nav-pills flex-column gap-1 list-unstyled mb-auto">

            <!-- Standard Hub/Dashboard Core triggers -->
            <li class="nav-item">
                <?php if (isStaff()): ?>
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

            <?php if (isStaff() && hasAdminPagePermission($activeRole, '/cms/admin')): ?>
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

                    <?php if (!isStaff()): ?>
                        <!-- ================= TENANT USER TOOLS ================= -->

                        <!-- Website Dropdown Menu -->
                        <div class="accordion-item border-0">
                            <div class="accordion-header">
                                <button class="accordion-button collapsed py-2 px-3 fw-bold text-dark rounded-pill hover-blue shadow-none d-flex align-items-center" type="button" data-bs-toggle="collapse" data-toggle="collapse" data-bs-target="#collapseWebsiteTools" data-target="#collapseWebsiteTools" aria-expanded="false" aria-controls="collapseWebsiteTools" style="background: transparent; font-size: 14px;">
                                    <i class="fa-solid fa-earth-americas me-2 text-primary" style="width: 20px;"></i>
                                    Website
                                </button>
                            </div>
                            <div id="collapseWebsiteTools" class="accordion-collapse collapse" data-bs-parent="#sidebarToolsAccordion">
                                <div class="accordion-body py-1 ps-4 pe-2 d-flex flex-column gap-1">
                                    <a href="/user/dashboard#build-website" class="nav-link d-flex align-items-center py-1.5 px-3 rounded-pill text-muted hover-blue text-xs fw-semibold">
                                        <i class="fa-solid fa-screwdriver-wrench me-2" style="font-size: 11px;"></i> Build Website
                                    </a>
                                    <a href="/user/dashboard#manage-files" class="nav-link d-flex align-items-center py-1.5 px-3 rounded-pill text-muted hover-blue text-xs fw-semibold">
                                        <i class="fa-solid fa-folder-open me-2" style="font-size: 11px;"></i> Manage Files
                                    </a>
                                    <!-- Added dynamic real-time traffic statistics and metrics page loading inside dashboard -->
                                    <a href="/user/dashboard#analytics" class="nav-link d-flex align-items-center py-1.5 px-3 rounded-pill text-muted hover-blue text-xs fw-semibold">
                                        <i class="fa-solid fa-chart-simple me-2" style="font-size: 11px;"></i> Analytics
                                    </a>
                                    <!-- Added dynamic secure user profile update settings page loading inside dashboard -->
                                    <a href="/user/dashboard#profile" class="nav-link d-flex align-items-center py-1.5 px-3 rounded-pill text-muted hover-blue text-xs fw-semibold">
                                        <i class="fa-solid fa-user-gear me-2" style="font-size: 11px;"></i> My Profile
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Database Dropdown Menu -->
                        <div class="accordion-item border-0 mt-1">
                            <div class="accordion-header">
                                <button class="accordion-button collapsed py-2 px-3 fw-bold text-dark rounded-pill hover-blue shadow-none d-flex align-items-center" type="button" data-bs-toggle="collapse" data-toggle="collapse" data-bs-target="#collapseDatabaseTools" data-target="#collapseDatabaseTools" aria-expanded="false" aria-controls="collapseDatabaseTools" style="background: transparent; font-size: 14px;">
                                    <i class="fa-solid fa-database me-2 text-primary" style="width: 20px;"></i>
                                    Database
                                </button>
                            </div>
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
                        <!-- ================= ADMIN STAFF TOOLS ================= -->
                        <?php
                        // Centralized dynamic admin/staff tools directory links array
                        $adminSidebarItems = [
                            ['label' => 'User Directory', 'icon' => 'fa-solid fa-users', 'url' => '/admin/users'],
                            ['label' => 'Website Manager', 'icon' => 'fa-solid fa-globe', 'url' => '/admin/websites'],
                            ['label' => 'Templates', 'icon' => 'fa-solid fa-object-group', 'url' => '/admin/templates'],
                            ['label' => 'Categories', 'icon' => 'fa-solid fa-list', 'url' => '/admin/categories'],
                            ['label' => 'Payments', 'icon' => 'fa-solid fa-credit-card', 'url' => '/admin/payments'],
                            ['label' => 'Revenue', 'icon' => 'fa-solid fa-money-bill-wave', 'url' => '/admin/revenue'],
                            ['label' => 'Financial Reports', 'icon' => 'fa-solid fa-file-invoice-dollar', 'url' => '/admin/financial-reports'],
                            ['label' => 'Platform Analytics', 'icon' => 'fa-solid fa-chart-simple', 'url' => '/admin/analytics'],
                            ['label' => 'Marketing', 'icon' => 'fa-solid fa-bullhorn', 'url' => '/admin/marketing'],
                            ['label' => 'Support Tickets', 'icon' => 'fa-solid fa-headset', 'url' => '/admin/support'],
                            ['label' => 'Moderation Queue', 'icon' => 'fa-solid fa-shield-halved', 'url' => '/admin/moderation'],
                            ['label' => 'System Audits', 'icon' => 'fa-solid fa-rectangle-list', 'url' => '/admin/activity-logs'],
                            ['label' => 'Settings', 'icon' => 'fa-solid fa-gears', 'url' => '/admin/settings']
                        ];

                        // Dynamically render sidebar controls purely based on backend access rights
                        foreach ($adminSidebarItems as $item):
                            if (hasAdminPagePermission($activeRole, $item['url'])):
                                // Check if this item is currently active based on requested path
                                $requestPathClean = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '';
                                $isActiveItem = (rtrim($requestPathClean, '/') === rtrim($item['url'], '/'));
                        ?>
                            <div class="accordion-item border-0 mt-1">
                                <div class="accordion-header">
                                    <a href="<?php echo $item['url']; ?>" class="nav-link d-flex align-items-center py-2 px-3 fw-bold rounded-pill hover-blue <?php echo $isActiveItem ? 'active text-white bg-primary' : 'text-dark'; ?>" style="font-size: 14px;">
                                        <i class="<?php echo $item['icon']; ?> me-2 <?php echo $isActiveItem ? 'text-white' : 'text-primary'; ?>" style="width: 20px;"></i>
                                        <?php echo $item['label']; ?>
                                    </a>
                                </div>
                            </div>
                        <?php
                            endif;
                        endforeach;

                        // Include System/Administration Backup page specifically for Superadmin
                        if ($activeRole === 'superadmin' || $activeRole === 'super admin'):
                        ?>
                            <div class="accordion-item border-0 mt-1">
                                <div class="accordion-header">
                                    <a href="/admin/dashboard#backup-section" class="nav-link d-flex align-items-center py-2 px-3 fw-bold text-dark rounded-pill hover-blue" style="font-size: 14px;">
                                        <i class="fa-solid fa-server me-2 text-primary" style="width: 20px;"></i>
                                        System / Admin
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>

                    <?php endif; ?>

                </div>
            </li>

            <hr class="my-3" style="border-color: rgba(0, 114, 255, 0.1);">

            <!-- Global Action Links (Visit Homepage, Profile Settings & Logout) -->
            <li>
                <?php if (isStaff()): ?>
                    <!-- Dynamically target dashboard-embedded profile tabs instead of separate page reloads -->
                    <a href="/admin/dashboard#profile" class="nav-link d-flex align-items-center py-2 px-3 fw-bold text-dark rounded-pill hover-blue <?php echo str_contains($_SERVER['REQUEST_URI'], 'profile') ? 'active text-white bg-primary' : ''; ?>">
                        <i class="fa-solid fa-user me-2" style="width: 20px;"></i>
                        Profile Settings
                    </a>
                <?php else: ?>
                    <a href="/user/dashboard#profile" class="nav-link d-flex align-items-center py-2 px-3 fw-bold text-dark rounded-pill hover-blue <?php echo str_contains($_SERVER['REQUEST_URI'], 'profile') ? 'active text-white bg-primary' : ''; ?>">
                        <i class="fa-solid fa-user me-2" style="width: 20px;"></i>
                        Profile Settings
                    </a>
                <?php endif; ?>
            </li>
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

        <?php
        // Fetch real-time traffic statistics from system databases dynamically
        // Use global connection instance if loaded, or instantiate directly
        try {
            $sidebarDb = new Database(__DIR__ . '/../../databases', 'system');
            $sidebarDb->createTable('traffic');
            $sidebarDb->createTable('websites');

            $allWebs = [];
            $viewsSum = 0;
            $clicksSum = 0;

            if ($activeRole === 'super admin' || $activeRole === 'admin') {
                // Admin dashboard displays cumulative system-wide metrics
                $allWebs = $sidebarDb->select('websites') ?: [];
            } else if (isset($_SESSION['user_id'])) {
                // Tenant dashboard aggregates statistics specific to user_id
                $allWebs = $sidebarDb->select('websites', ['user_id' => (int)$_SESSION['user_id']]) ?: [];
            }

            foreach ($allWebs as $w) {
                $t = $sidebarDb->selectOne('traffic', ['website_id' => (int)$w['id']]);
                if ($t) {
                    $viewsSum += (int)($t['page_views'] ?? 0);
                    $clicksSum += (int)($t['clicks'] ?? 0);
                }
            }

            // Define dynamic visual percentages for responsive charts
            $totalMetrics = $viewsSum + $clicksSum;
            $viewsPercent = $totalMetrics > 0 ? (int)round(($viewsSum / $totalMetrics) * 100) : 0;
            $clicksPercent = $totalMetrics > 0 ? (int)round(($clicksSum / $totalMetrics) * 100) : 0;
            if ($totalMetrics === 0) {
                // Fallback for visual demo empty states
                $viewsPercent = 0;
                $clicksPercent = 0;
            }
        ?>
            <!-- Real-time dynamic traffic statistics charts inside the sidebar -->
            <div class="mt-4 p-3 rounded-3" style="background-color: rgba(0, 114, 255, 0.03); border: 1px solid rgba(0, 114, 255, 0.08);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-xs font-bold text-dark uppercase tracking-wider d-flex align-items-center" style="font-size: 10px;">
                        <i class="fa-solid fa-chart-simple text-primary me-2"></i> Real-Time Stats
                    </span>
                    <span class="badge bg-primary text-white font-monospace" style="font-size: 9px; padding: 2px 5px;">LIVE</span>
                </div>

                <!-- Page Views Progress Visualizer -->
                <div class="mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-1 text-2xs" style="font-size: 10px;">
                        <span class="text-muted fw-semibold">Page Views (<?php echo number_format($viewsSum); ?>)</span>
                        <span class="text-dark fw-bold"><?php echo $viewsPercent; ?>%</span>
                    </div>
                    <div class="progress" style="height: 6px; background-color: rgba(0, 114, 255, 0.1); border-radius: 9999px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $viewsPercent; ?>%; border-radius: 9999px;" aria-valuenow="<?php echo $viewsPercent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>

                <!-- Clicks Interaction Progress Visualizer -->
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-1 text-2xs" style="font-size: 10px;">
                        <span class="text-muted fw-semibold">Clicks Interaction (<?php echo number_format($clicksSum); ?>)</span>
                        <span class="text-dark fw-bold"><?php echo $clicksPercent; ?>%</span>
                    </div>
                    <div class="progress" style="height: 6px; background-color: rgba(0, 114, 255, 0.1); border-radius: 9999px;">
                        <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $clicksPercent; ?>%; border-radius: 9999px;" aria-valuenow="<?php echo $clicksPercent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        <?php
        } catch (\Throwable $e) {
            // Silence exceptions gracefully to avoid breaking sidebar display on load
        }
        ?>
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

    /* Off-canvas Responsive Sidebar Styles */
    @media (max-width: 767.98px) {
        .sidebar-container {
            position: fixed !important;
            top: 0;
            left: 0;
            height: 100vh !important;
            transform: translateX(-100%);
            z-index: 1050 !important;
        }
        .sidebar-container.active {
            transform: translateX(0);
        }
    }

    /* Fix CSS Conflict between Tailwind CSS and Bootstrap 5 Accordion Collapse elements */
    /* Prevents active/expanded accordion elements from disappearing due to visibility conflicts */
    .sidebar-container .collapse {
        visibility: visible !important;
    }
</style>

<!-- Sidebar mobile helper script for off-canvas drawer toggling and auto expanding active links -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const sidebarContainer = document.getElementById("sidebarContainer");
    const sidebarOverlay = document.getElementById("sidebarOverlay");
    const sidebarCloseBtn = document.getElementById("sidebarCloseBtn");
    const sidebarToggleBtn = document.getElementById("sidebarToggleBtn");

    const openSidebar = function() {
        if (sidebarContainer) sidebarContainer.classList.add("active");
        if (sidebarOverlay) sidebarOverlay.style.display = "block";
    };

    const closeSidebar = function() {
        if (sidebarContainer) sidebarContainer.classList.remove("active");
        if (sidebarOverlay) sidebarOverlay.style.display = "none";
    };

    if (sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener("click", openSidebar);
    }
    if (sidebarCloseBtn) {
        sidebarCloseBtn.addEventListener("click", closeSidebar);
    }
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener("click", closeSidebar);
    }

    // Automatically expand the corresponding accordion tools drawer on page hashes or active states
    const autoExpandActiveDropdown = function() {
        const hash = window.location.hash;
        if (!hash) return;

        // Detect if active control panel context is administrative
        const isAdmin = window.location.pathname.includes('/admin');

        // Map hash identifiers to active collapsible containers
        const targetMap = {
            '#build-website': 'collapseWebsiteTools',
            '#manage-files': 'collapseWebsiteTools',
            '#analytics': isAdmin ? 'collapseAdminWebsites' : 'collapseWebsiteTools',
            '#profile': isAdmin ? 'collapseAdminWebsites' : 'collapseWebsiteTools',
            '#user-db-manager': 'collapseDatabaseTools',
            '#websites-section': 'collapseAdminWebsites',
            '#activity-logs-section': 'collapseAdminSystem',
            '#payment-settings-section': 'collapseAdminSystem'
        };

        const targetCollapseId = targetMap[hash];
        if (targetCollapseId) {
            const collapseEl = document.getElementById(targetCollapseId);
            if (collapseEl && !collapseEl.classList.contains("show")) {
                // Use Bootstrap collapse API to show if available
                if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
                    const bsCollapse = bootstrap.Collapse.getInstance(collapseEl) || new bootstrap.Collapse(collapseEl);
                    bsCollapse.show();
                } else {
                    collapseEl.classList.add("show");
                }
            }
        }
    };

    // Auto expand on load and hash change
    autoExpandActiveDropdown();
    window.addEventListener("hashchange", autoExpandActiveDropdown);
});
</script>
