<?php
/**
 * sidebar.php
 *
 * This is the modular sidebar navigation component shared by both the Admin and User dashboards.
 * Displays navigation links dynamically matched to the active session role privilege level.
 */

// Enable strict typing for reliability
declare(strict_types=1);

// Guard against direct file inclusions without active session
$activeRole = $_SESSION['role'] ?? 'tenant';
$fullname   = $_SESSION['fullname'] ?? 'System User';
?>
<!-- Sidebar Navigation component -->
<div class="d-flex flex-column flex-shrink-0 p-3" style="width: 250px; min-height: 100vh; background-color: #ffffff; border-right: 1px solid rgba(0, 114, 255, 0.1); box-shadow: 4px 0 12px rgba(0, 114, 255, 0.03);">

    <!-- Workspace Brand Header -->
    <a href="/" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-primary text-decoration-none" style="font-family: 'Orbitron', sans-serif;">
        <i class="fa-solid fa-server fs-4 me-2"></i>
        <span class="fs-4 fw-bold">nodex<span class="text-dark">Go</span></span>
    </a>

    <hr class="my-3" style="border-color: rgba(0, 114, 255, 0.1);">

    <!-- Current User Info Section -->
    <div class="d-flex align-items-center px-2 mb-4">
        <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold bg-primary" style="width: 40px; height: 40px; font-size: 16px;">
            <?php echo strtoupper(substr($fullname, 0, 1)); ?>
        </div>
        <div class="ms-3 overflow-hidden">
            <h6 class="mb-0 text-dark fw-bold text-truncate" style="font-size: 14px;"><?php echo htmlspecialchars($fullname); ?></h6>
            <small class="text-muted text-uppercase fw-bold" style="font-size: 11px;"><?php echo $activeRole === 'admin' ? 'Administrator' : 'Tenant Account'; ?></small>
        </div>
    </div>

    <!-- Navigation Menu list links -->
    <ul class="nav nav-pills flex-column mb-auto gap-1">
        <?php if ($activeRole === 'admin'): ?>
            <!-- ADMIN NAVIGATION ITEMS -->
            <li class="nav-item">
                <a href="/admin/dashboard" class="nav-link d-flex align-items-center py-2 px-3 fw-bold rounded-pill <?php echo str_contains($_SERVER['REQUEST_URI'], 'admin/dashboard') ? 'active text-white bg-primary' : 'text-dark hover-blue'; ?>">
                    <i class="fa-solid fa-chart-line me-2" style="width: 20px;"></i>
                    System Overview
                </a>
            </li>
            <li>
                <a href="/cms/admin" class="nav-link d-flex align-items-center py-2 px-3 fw-bold text-dark rounded-pill hover-blue">
                    <i class="fa-solid fa-code me-2" style="width: 20px;"></i>
                    CMS Code Panel
                </a>
            </li>
        <?php else: ?>
            <!-- TENANT USER NAVIGATION ITEMS -->
            <li class="nav-item">
                <a href="/user/dashboard" class="nav-link d-flex align-items-center py-2 px-3 fw-bold rounded-pill <?php echo str_contains($_SERVER['REQUEST_URI'], 'user/dashboard') ? 'active text-white bg-primary' : 'text-dark hover-blue'; ?>">
                    <i class="fa-solid fa-gauge-high me-2" style="width: 20px;"></i>
                    Workspace Hub
                </a>
            </li>
            <li>
                <a href="#user-db-manager" class="nav-link d-flex align-items-center py-2 px-3 fw-bold text-dark rounded-pill hover-blue">
                    <i class="fa-solid fa-database me-2" style="width: 20px;"></i>
                    Database Manager
                </a>
            </li>
        <?php endif; ?>

        <hr class="my-3" style="border-color: rgba(0, 114, 255, 0.1);">

        <!-- GLOBAL CORE ITEMS -->
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

    <div class="px-2 mt-4 text-center">
        <small class="text-muted font-monospace" style="font-size: 10px;">v<?php echo date('Y.m.d'); ?>.alpha</small>
    </div>
</div>

<style>
    /* Premium Hover Animation Effects */
    .hover-blue:hover {
        background-color: rgba(0, 114, 255, 0.06) !important;
        color: #0072ff !important;
    }
    .hover-danger:hover {
        background-color: rgba(239, 68, 68, 0.06) !important;
        color: #ef4444 !important;
    }
</style>
