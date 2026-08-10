<?php
/**
 * index.php
 *
 * This is the single entry point and core router for the entire nodexGosolutions architecture.
 * It coordinates routing for the main marketing pages, the multi-tenant system, CMS engine,
 * and handles host parameter isolation (port stripping) with LFI folder bounds.
 */

// Enable strict typing for better software quality
declare(strict_types=1);

// Include the TenantManager class file to manage tenant retrieval logic
require_once __DIR__ . '/TenantManager.php';
// Include the custom JSON Database engine class
require_once __DIR__ . '/php/database.php';
// Include database and security helpers
require_once __DIR__ . '/php/db.php';

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
$bypassPath = __DIR__ . $requestUri;
$realBypassPath = realpath($bypassPath);
$realRootPath = realpath(__DIR__);

// Only allow bypass if the resolved path is strictly within the project directory root (or is not going outside)
if ($realBypassPath !== false && $realRootPath !== false && str_starts_with($realBypassPath, $realRootPath) && is_file($realBypassPath)) {
    // Forbid direct HTTP access to databases or sensitive assets
    $relativeToRoot = substr($realBypassPath, strlen($realRootPath));
    $relativeToRoot = ltrim(str_replace('\\', '/', $relativeToRoot), '/');

    // List of directories that should never be directly downloaded via physical asset bypass
    $protectedDirs = ['databases/', 'main/modul/'];
    $isProtected = false;
    foreach ($protectedDirs as $protected) {
        if (str_starts_with($relativeToRoot, $protected)) {
            $isProtected = true;
            break;
        }
    }

    if (!$isProtected) {
        // Return false to allow webserver to handle raw resource files directly
        return false;
    }
}

// Define the root pages matching map pointing to main landing page scripts
$routeMap = [
    '/verify' => __DIR__ . '/main/public/verify.html',
    '/verify.html' => __DIR__ . '/main/public/verify.html',
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
    '/php/process_payment.php' => __DIR__ . '/php/process_payment.php',
    '/php/process_payment' => __DIR__ . '/php/process_payment.php',
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
$landingHosts = ['nodexplatform.com.ng', 'localhost', '127.0.0.1'];

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

    // If a custom CMS page was found and has active custom code, render its custom code payload
    if ($cmsPage !== null && !empty($cmsPage['body_code'])) {
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
// DYNAMIC TENANT PUBLIC DIRECTORY ROUTING
// ============================================================

// Check if the current host is NOT one of our landing hosts (meaning it is a subdomain or a specific tenant custom domain)
if (!in_array(strtolower($currentHost), $landingHosts, true)) {
    // Define candidate tenant folder names inside the root "public" directory
    $possibleTenantFolders = [
        // Candidate 1: The full request host name (e.g. "tenant1.nodexplatform.com.ng" or "mytenant.com")
        $currentHost,
    ];

    // Split host by dots to isolate subdomains
    $hostParts = explode('.', $currentHost);
    // If we have subdomain parts, add the first subdomain segment as a candidate folder name (e.g., "tenant1")
    if (count($hostParts) > 1) {
        // Append first subdomain segment
        $possibleTenantFolders[] = $hostParts[0];
    }

    // Initialize tenant public folder directory pointer
    $tenantPublicDir = null;
    // Iterate through candidates to locate an existing tenant subdirectory inside the root public/ folder
    foreach ($possibleTenantFolders as $folder) {
        // Construct the candidate directory path on disk
        $candidatePath = __DIR__ . '/public/' . $folder;
        // Verify if the candidate folder exists and is a valid directory
        if (is_dir($candidatePath)) {
            // Set the resolved tenant public directory pointer
            $tenantPublicDir = $candidatePath;
            // Break loop once matching folder is found
            break;
        }
    }

    // If a valid tenant public directory is found on disk, resolve and serve the requested resource
    if ($tenantPublicDir !== null) {
        // Canonicalize base tenant directory path
        $realPublicDir = realpath($tenantPublicDir);

        // Combine the tenant's public folder path with the requested URL path
        $targetPath = $tenantPublicDir . $requestUri;

        // If the targeted path points to a directory, append default index page files
        if (is_dir($targetPath)) {
            // Normalize path trailing slash
            $targetPath = rtrim($targetPath, '/') . '/';
            // Check for index.php as priority
            if (file_exists($targetPath . 'index.php')) {
                // Route to index.php
                $targetPath .= 'index.php';
            // Fallback to index.html
            } elseif (file_exists($targetPath . 'index.html')) {
                // Route to index.html
                $targetPath .= 'index.html';
            }
        }

        // Get the real absolute path of the targeted resource
        $realTargetPath = realpath($targetPath);

        // Security check: Ensure target path exists, is a file, and remains strictly within the tenant's public folder boundary to prevent LFI/directory traversal
        if ($realPublicDir !== false && $realTargetPath !== false && is_file($realTargetPath) && str_starts_with($realTargetPath, $realPublicDir)) {
            // Isolate file extension to determine execution or direct static asset serving
            $extension = strtolower(pathinfo($realTargetPath, PATHINFO_EXTENSION));

            // If the requested resource is a dynamic PHP script
            if ($extension === 'php') {
                // Execute and render the PHP file directly in tenant context
                require_once $realTargetPath;
                // Halt further routing processes
                exit;
            } else {
                // Define common web content MIME-types list
                $mimeTypes = [
                    'html' => 'text/html',
                    'htm'  => 'text/html',
                    'css'  => 'text/css',
                    'js'   => 'application/javascript',
                    'png'  => 'image/png',
                    'jpg'  => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'gif'  => 'image/gif',
                    'svg'  => 'image/svg+xml',
                    'ico'  => 'image/x-icon',
                    'json' => 'application/json',
                    'pdf'  => 'application/pdf',
                    'zip'  => 'application/zip',
                ];

                // Retrieve MIME type header matching the extension, falling back to octet-stream
                $contentType = $mimeTypes[$extension] ?? 'application/octet-stream';
                // Emit correct browser response content header
                header("Content-Type: " . $contentType);
                // Stream raw file contents directly to client
                readfile($realTargetPath);
                // Exit routing script successfully
                exit;
            }
        }
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
