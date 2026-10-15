<?php
/**
 * index.php
 *
 * This is the single entry point for the entire nodexGosolutions project.
 * Every request is routed here via the .htaccess rules.
 *
 * Its primary responsibilities are:
 * 1. Initialize the environment.
 * 2. Instantiate the TenantManager from the php directory.
 * 3. Detect the current tenant based on the HTTP_HOST or query parameters.
 * 4. Pass the tenant data to the template for rendering.
 */

declare(strict_types=1);

// Include the TenantManager class file from modular php/ directory
require_once __DIR__ . '/php/TenantManager.php';

/**
 * Main Application Logic
 */

// 1. Capture the incoming host from global $_SERVER array or override via query parameter
$currentHost = $_GET['tenant_host'] ?? $_SERVER['HTTP_HOST'] ?? '';

// Strip port number if exists (e.g. localhost:8000 -> localhost)
if (str_contains($currentHost, ':')) {
    $currentHost = explode(':', $currentHost)[0];
}

// 2. Initialize the TenantManager
$tenantManager = new TenantManager();

// 3. Attempt to fetch the tenant configuration based on the host
$tenant = $tenantManager->getTenantByHost($currentHost);

// 4. Fallback to default tenant if not found
if ($tenant === null) {
    $tenant = $tenantManager->getDefaultTenant();
}

/**
 * Rendering Phase
 */
require_once __DIR__ . '/template.php';
