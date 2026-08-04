<?php
/**
 * index.php
 *
 * This is the single entry point and core router for the entire nodexGosolutions architecture.
 * It coordinates routing for the main marketing pages, the multi-tenant system, CMS engine,
 * and handles host parameter isolation (port stripping).
 */

// Enable strict typing for better software quality
declare(strict_types=1);

// Include the TenantManager class file to manage tenant retrieval logic
require_once __DIR__ . '/TenantManager.php';
// Include the custom JSON Database engine class
require_once __DIR__ . '/php/database.php';

// Capture the incoming HTTP request host from server headers
$currentHost = $_SERVER['HTTP_HOST'] ?? '';

// Strip port numbers from host to handle localhost:8000 and custom ports uniformly
if (str_contains($currentHost, ':')) {
    // Extract everything before the colon
    $currentHost = explode(':', $currentHost)[0];
}

// Parse the raw requested URL path to extract the routing path (slug)
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';

// Physical Asset Router Bypass: If the requested resource exists on disk as a file, return false so the webserver can serve it directly
if (file_exists(__DIR__ . $requestUri) && !is_dir(__DIR__ . $requestUri)) {
    // Return false to allow webserver to handle raw resource files directly
    return false;
}

// Define the root pages matching map pointing to main landing page scripts
$routeMap = [
    '/login' => __DIR__ . '/main/public/login.php',
    '/login.php' => __DIR__ . '/main/public/login.php',
    '/register' => __DIR__ . '/main/public/register.php',
    '/register.php' => __DIR__ . '/main/public/register.php',
    '/about' => __DIR__ . '/main/public/about.php',
    '/about.php' => __DIR__ . '/main/public/about.php',
    '/cedar' => __DIR__ . '/main/public/cedar.php',
    '/cedar.php' => __DIR__ . '/main/public/cedar.php',
    '/gallary' => __DIR__ . '/main/public/gallary.php',
    '/gallary.php' => __DIR__ . '/main/public/gallary.php',
    '/roadmap' => __DIR__ . '/main/public/roadmap.php',
    '/roadmap.php' => __DIR__ . '/main/public/roadmap.php',
    '/traction' => __DIR__ . '/main/public/traction.php',
    '/traction.php' => __DIR__ . '/main/public/traction.php',
    '/portfolio' => __DIR__ . '/main/public/portfolio.php',
    '/portfolio.php' => __DIR__ . '/main/public/portfolio.php',
    '/cms/admin' => __DIR__ . '/cms/admin.php',
    '/cms/admin.php' => __DIR__ . '/cms/admin.php',
    '/cms/save_page' => __DIR__ . '/php/save_page.php',
    '/cms/php/save_page.php' => __DIR__ . '/php/save_page.php',
    '/admin/dashboard' => __DIR__ . '/main/public/admin/dashboard.php',
    '/admin/dashboard.php' => __DIR__ . '/main/public/admin/dashboard.php',
    '/user/dashboard' => __DIR__ . '/main/public/user/dashboard.php',
    '/user/dashboard.php' => __DIR__ . '/main/public/user/dashboard.php',
    '/user/dashboard.html' => __DIR__ . '/main/public/user/dashboard.php',
    '/php/admin_action.php' => __DIR__ . '/php/admin_action.php',
    '/php/admin_action' => __DIR__ . '/php/admin_action.php',
];

// Check if the current requested URI is registered in our hardcoded static routing map
if (isset($routeMap[$requestUri])) {
    // Require and render the designated route file
    require_once $routeMap[$requestUri];
    // Terminate script execution successfully
    exit;
}

// Normalize the requested URL path to detect slash and dot-php extensions
$normalizedSlug = ltrim($requestUri, '/');
// Remove .php if present at the end of the normalized slug
if (str_ends_with($normalizedSlug, '.php')) {
    // Strip php extension
    $normalizedSlug = substr($normalizedSlug, 0, -4);
}

// Define recognized landing page host names (apex, secondary, and development local hosts)
$landingHosts = ['nodexgosolutions.com', 'localhost', '127.0.0.1'];

// Check if the requested host is one of our central marketing landing domain names
if (in_array(strtolower($currentHost), $landingHosts, true)) {
    // If the request path is root, serve the gorgeous main company index page
    if ($requestUri === '/' || $requestUri === '/index.php' || $requestUri === '') {
        // Require and render the primary marketing landing page
        require_once __DIR__ . '/main/public/index.php';
        // Terminate routing process
        exit;
    }

    // Initialize the CMS Database to query custom-created pages from JSON databases
    $cmsDb = new Database(__DIR__ . '/databases', 'site_cms');

    // Attempt to locate a custom page matching the slug from pages.json
    $cmsPage = $cmsDb->selectOne('pages', ['slug' => $normalizedSlug]);

    // If a custom CMS page was found, render its custom code payload
    if ($cmsPage !== null) {
        /**
         * Helper to evaluate raw user PHP/HTML code within isolated variables context.
         *
         * @param string $code Raw markup or PHP script context.
         * @param array $context Context variables array to extract.
         */
        function renderCmsPayload(string $code, array $context = []): void {
            // Extract keys as variables
            extract($context);
            // Evaluate raw PHP codes or print direct HTML structures safely
            eval('?>' . $code);
        }
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo htmlspecialchars($cmsPage['title'] ?? 'CMS Page'); ?></title>
            <?php // Render head tags payload
            renderCmsPayload($cmsPage['head_code'] ?? '', ['db' => $cmsDb, 'page' => $cmsPage]); ?>
        </head>
        <body>
            <?php // Render body content payload
            renderCmsPayload($cmsPage['body_code'] ?? '', ['db' => $cmsDb, 'page' => $cmsPage]); ?>
        </body>
        </html>
        <?php
        // Terminate execution
        exit;
    }
}

// ============================================================
// MULTI-TENANCY ROUTING FALLBACK
// ============================================================

// Initialize TenantManager to retrieve tenant config records based on the host
$tenantManager = new TenantManager();

// Find matched tenant metadata
$tenant = $tenantManager->getTenantByHost($currentHost);

// If no specific tenant matches, fall back to the default fallback tenant metadata
if ($tenant === null) {
    // Fetch generic fall-back tenant
    $tenant = $tenantManager->getDefaultTenant();
}

// Require the multi-tenant template page to display custom color, logs, and information
require_once __DIR__ . '/template.php';
?>
