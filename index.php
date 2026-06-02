<?php
/**
 * index.php
 *
 * This is the single entry point for the entire nodexGosolutions project.
 * Every request is routed here via the .htaccess rules.
 *
 * Its primary responsibilities are:
 * 1. Initialize the environment.
 * 2. Instantiate the TenantManager.
 * 3. Detect the current tenant based on the HTTP_HOST.
 * 4. Pass the tenant data to the template for rendering.
 */

// Enable strict typing for better code quality and fewer bugs
declare(strict_types=1);

// Include the TenantManager class file
// We use require_once to ensure the class is loaded exactly once
require_once __DIR__ . '/TenantManager.php';

/**
 * Main Application Logic
 */

// 1. Capture the incoming host from the global $_SERVER array
// If the key is not set (e.g. CLI usage without mock), we default to an empty string
$currentHost = $_SERVER['HTTP_HOST'] ?? '';

// We strip the port number if it exists (e.g., localhost:8000 -> localhost)
// to ensure we match the identifier correctly regardless of the port.
if (str_contains($currentHost, ':')) {
    $currentHost = explode(':', $currentHost)[0];
}

// 2. Initialize the TenantManager
$tenantManager = new TenantManager();

// 3. Attempt to fetch the tenant configuration based on the current host
$tenant = $tenantManager->getTenantByHost($currentHost);

// 4. If no tenant is found (e.g., accessed via IP or unknown domain), fallback to default
if ($tenant === null) {
    $tenant = $tenantManager->getDefaultTenant();
}

/**
 * Rendering Phase
 */

// We include the template file.
// Because the template is included here, it has access to the $tenant variable defined above.
require_once __DIR__ . '/template.php';

/**
 * Note on future scalability:
 * In a more complex architecture, we would replace the simple include above with
 * a Controller or View class, but for this pure PHP boilerplate, a clean include
 * is efficient and highly readable.
 */
