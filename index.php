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

// Security Gate: Explicitly block any browser request containing the backup repository string
// This completely neutralizes any directory traversal or malicious attempts targeting NGS_backups
if (str_contains(strtolower($requestUri), 'ngs_backups')) {
    // Return HTTP 403 Forbidden
    http_response_code(403);
    // Write explicit secure rejection message
    echo "403 Forbidden - Access to private backup repositories is restricted.";
    // Halt further routing execution
    exit;
}

// Intercept uploaded avatar requests to serve static avatar images reliably
if (str_starts_with($requestUri, '/uploads/avatars/')) {
    $candidate1 = __DIR__ . $requestUri;
    $candidate2 = __DIR__ . '/main/public' . $requestUri;
    $avatarFile = is_file($candidate1) ? $candidate1 : (is_file($candidate2) ? $candidate2 : null);

    if ($avatarFile !== null && is_file($avatarFile)) {
        $ext = strtolower(pathinfo($avatarFile, PATHINFO_EXTENSION));
        $mimeTypes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
        $contentType = $mimeTypes[$ext] ?? 'image/jpeg';
        header('Content-Type: ' . $contentType);
        readfile($avatarFile);
        exit;
    }
}

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

// Dynamically intercept profile and analytics paths to seamlessly route inside the unified dashboards
if ($requestUri === '/profile' || $requestUri === '/profile.php' || $requestUri === '/analytics' || $requestUri === '/analytics.php') {
    // Start session securely if not initialized
    if (session_status() === PHP_SESSION_NONE) {
        session_name('NODX_SESSION');
        @session_start();
    }

    // Access Control: Redirect guest visitors to login flow
    if (!isset($_SESSION['email'])) {
        header('Location: /login');
        exit;
    }

    // Isolate active session role bounds
    $activeRole = strtolower((string)($_SESSION['role'] ?? 'tenant'));
    $roleNormalized = str_replace(' ', '', str_replace('_', '', $activeRole));
    $staffRoles = ['admin', 'superadmin', 'manager', 'moderator', 'support', 'financial', 'marketinghead'];
    $isAdminContext = in_array($roleNormalized, $staffRoles, true);

    // Formulate target dashboard anchors depending on context role details
    if (str_contains($requestUri, 'profile')) {
        $target = $isAdminContext ? '/admin/dashboard#profile' : '/user/dashboard#profile';
    } else {
        $target = $isAdminContext ? '/admin/dashboard#analytics' : '/user/dashboard#analytics';
    }

    // Perform real-time safe header redirection to active section
    header("Location: " . $target);
    exit;
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
    // Virtual admin pages mapping to unify single entry templates and access verification
    '/admin/users' => __DIR__ . '/main/public/admin/users.php',
    '/admin/users.php' => __DIR__ . '/main/public/admin/users.php',
    '/admin/websites' => __DIR__ . '/main/public/admin/websites.php',
    '/admin/websites.php' => __DIR__ . '/main/public/admin/websites.php',
    '/admin/templates' => __DIR__ . '/main/public/admin/templates.php',
    '/admin/templates.php' => __DIR__ . '/main/public/admin/templates.php',
    '/admin/categories' => __DIR__ . '/main/public/admin/categories.php',
    '/admin/categories.php' => __DIR__ . '/main/public/admin/categories.php',
    '/admin/payments' => __DIR__ . '/main/public/admin/payments.php',
    '/admin/payments.php' => __DIR__ . '/main/public/admin/payments.php',
    '/admin/revenue' => __DIR__ . '/main/public/admin/revenue.php',
    '/admin/revenue.php' => __DIR__ . '/main/public/admin/revenue.php',
    '/admin/financial-reports' => __DIR__ . '/main/public/admin/financial-reports.php',
    '/admin/financial-reports.php' => __DIR__ . '/main/public/admin/financial-reports.php',
    '/admin/analytics' => __DIR__ . '/main/public/admin/analytics.php',
    '/admin/analytics.php' => __DIR__ . '/main/public/admin/analytics.php',
    '/admin/marketing' => __DIR__ . '/main/public/admin/marketing.php',
    '/admin/marketing.php' => __DIR__ . '/main/public/admin/marketing.php',
    '/admin/support' => __DIR__ . '/main/public/admin/support.php',
    '/admin/support.php' => __DIR__ . '/main/public/admin/support.php',
    '/admin/moderation' => __DIR__ . '/main/public/admin/moderator.php',
    '/admin/moderation.php' => __DIR__ . '/main/public/admin/moderator.php',
    '/admin/moderator' => __DIR__ . '/main/public/admin/moderator.php',
    '/admin/moderator.php' => __DIR__ . '/main/public/admin/moderator.php',
    '/admin/manager' => __DIR__ . '/main/public/admin/manager.php',
    '/admin/manager.php' => __DIR__ . '/main/public/admin/manager.php',
    '/admin/financial' => __DIR__ . '/main/public/admin/financial.php',
    '/admin/financial.php' => __DIR__ . '/main/public/admin/financial.php',
    '/admin/teams' => __DIR__ . '/main/public/admin/teams.php',
    '/admin/teams.php' => __DIR__ . '/main/public/admin/teams.php',
    '/admin/activity-logs' => __DIR__ . '/main/public/admin/activity-logs.php',
    '/admin/activity-logs.php' => __DIR__ . '/main/public/admin/activity-logs.php',
    '/admin/settings' => __DIR__ . '/main/public/admin/settings.php',
    '/admin/settings.php' => __DIR__ . '/main/public/admin/settings.php',
    '/user/dashboard' => __DIR__ . '/main/public/user/dashboard.php',
    '/user/dashboard.php' => __DIR__ . '/main/public/user/dashboard.php',
    '/user/dashboard.html' => __DIR__ . '/main/public/user/dashboard.php',
    '/php/admin_backup_action.php' => __DIR__ . '/php/admin_backup_action.php',
    '/php/admin_backup_action' => __DIR__ . '/php/admin_backup_action.php',
    '/php/admin_action.php' => __DIR__ . '/php/admin_action.php',
    '/php/admin_action' => __DIR__ . '/php/admin_action.php',
    '/php/process_payment.php' => __DIR__ . '/php/process_payment.php',
    '/php/process_payment' => __DIR__ . '/php/process_payment.php',
    '/php/create_website_action' => __DIR__ . '/php/create_website_action.php',
    '/php/create_website_action.php' => __DIR__ . '/php/create_website_action.php',
    '/php/manage_files_action' => __DIR__ . '/php/manage_files_action.php',
    '/php/manage_files_action.php' => __DIR__ . '/php/manage_files_action.php',
    '/php/initialize_payment' => __DIR__ . '/php/initialize_payment.php',
    '/php/initialize_payment.php' => __DIR__ . '/php/initialize_payment.php',
    '/php/verify_payment' => __DIR__ . '/php/verify_payment.php',
    '/php/verify_payment.php' => __DIR__ . '/php/verify_payment.php',
    '/php/admin_pricing_action' => __DIR__ . '/php/admin_pricing_action.php',
    '/php/admin_pricing_action.php' => __DIR__ . '/php/admin_pricing_action.php',
    '/php/admin_settings_action' => __DIR__ . '/php/admin_settings_action.php',
    '/php/admin_settings_action.php' => __DIR__ . '/php/admin_settings_action.php',
    '/php/admin_rbac_action' => __DIR__ . '/php/admin_rbac_action.php',
    '/php/admin_rbac_action.php' => __DIR__ . '/php/admin_rbac_action.php',
    '/php/admin_team_action' => __DIR__ . '/php/admin_team_action.php',
    '/php/admin_team_action.php' => __DIR__ . '/php/admin_team_action.php',
    '/php/track_metrics' => __DIR__ . '/php/track_metrics.php',
    '/php/track_metrics.php' => __DIR__ . '/php/track_metrics.php',
    '/sitemap.xml' => __DIR__ . '/php/generate_sitemap.php',
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

    // Instantiate session if none exists to resolve visitor session state
    if (session_status() === PHP_SESSION_NONE) {
        session_name('NODX_SESSION');
        @session_start();
    }

    // Isolate active visitor tenant identification to support page collision resolutions
    $visitorTenantId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    $cmsPage = null;

    if ($visitorTenantId !== null) {
        // Prioritize custom slug layout matching active logged-in tenant ID
        $cmsPage = $cmsDb->selectOne('pages', ['slug' => $normalizedSlug, 'user_id' => $visitorTenantId]);
    }

    if ($cmsPage === null) {
        // Fall back to matching admin system-wide custom page overrides (user_id = 0)
        $cmsPage = $cmsDb->selectOne('pages', ['slug' => $normalizedSlug, 'user_id' => 0]);
    }

    // If a custom CMS page was found and has active custom code, render its custom code payload
    if ($cmsPage !== null && !empty($cmsPage['body_code'])) {
        // Retrieve owner details or roles
        $pageUserId = (int)($cmsPage['user_id'] ?? 0);
        $isAdminPage = ($pageUserId === 0);

        // Define a closure-local safe renderer to satisfy scope & sandboxing requirements
        $renderPayload = function(string $code, bool $isCodeFirstAdmin, array $context = []) use ($cmsDb) {
            if ($isCodeFirstAdmin) {
                // Administrators are trusted; execute with isolated variable context
                extract($context);
                eval('?>' . $code);
            } else {
                // Standard tenants are sandbox restricted: strip PHP tags to prevent RCE/eval hazards
                $sanitized = preg_replace('/<\?php(.*?)\?>/is', '', $code);
                $sanitized = preg_replace('/<\?(.*?)\?>/is', '', $sanitized);
                // Print safe HTML/CSS payload directly
                echo $sanitized;
            }
        };

        // Extract page images from CMS body content if available
        $cmsBodyImages = extractPageImagesFromHtml($cmsPage['body_code'] ?? '');
        $cmsPageTitle = $cmsPage['title'] ?? 'CMS Page';
        $cmsPageSlug = $cmsPage['slug'] ?? 'page';
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <?php echo renderSeoHead([
                'title' => $cmsPageTitle . ' | nodexGosolutions',
                'description' => substr(strip_tags($cmsPage['body_code'] ?? ''), 0, 160),
                'url' => '/' . $cmsPageSlug,
                'images' => $cmsBodyImages,
                'schema_type' => 'WebPage',
                'breadcrumbs' => [
                    ['name' => $cmsPageTitle, 'url' => '/' . $cmsPageSlug]
                ]
            ]); ?>
            <?php
            // Google Analytics Injection
            $gaId = $cmsPage['ga_id'] ?? 'G-NODEXGO123';
            if (!empty($gaId)):
            ?>
            <!-- Google Analytics Tag -->
            <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo htmlspecialchars($gaId); ?>"></script>
            <script>
              window.dataLayer = window.dataLayer || [];
              function gtag(){dataLayer.push(arguments);}
              gtag('js', new Date());
              gtag('config', '<?php echo htmlspecialchars($gaId); ?>');
            </script>
            <?php endif; ?>
            <?php // Render head tags payload with sandboxed logic
            $renderPayload($cmsPage['head_code'] ?? '', $isAdminPage, ['db' => $cmsDb, 'page' => $cmsPage]); ?>
        </head>
        <body>
            <?php // Render body content payload with sandboxed logic
            $renderPayload($cmsPage['body_code'] ?? '', $isAdminPage, ['db' => $cmsDb, 'page' => $cmsPage]); ?>
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
        // Enforce website suspension & subscription expiration checks
        $websiteRecord = $conn->selectOne('websites', ['subdomain' => $folder]);
        if ($websiteRecord !== null) {
            // Check suspension status
            if ((int)($websiteRecord['is_suspended'] ?? 0) === 1) {
                http_response_code(403);
                ?>
                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <title>Website Suspended</title>
                    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
                    <style>
                        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; color: #1e293b; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
                        .card { text-align: center; padding: 40px; background-color: #ffffff; border-radius: 24px; box-shadow: 0 10px 25px rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.08); max-width: 500px; }
                        h1 { font-size: 24px; font-weight: 800; color: #dc2626; margin: 0 0 10px; }
                        p { font-size: 14px; color: #64748b; line-height: 1.6; margin: 0 0 20px; }
                    </style>
                </head>
                <body>
                    <div class="card">
                        <h1>Website Suspended</h1>
                        <p>This website is temporarily suspended by the platform administrator. Please contact support or check your workspace dashboard for details.</p>
                    </div>
                </body>
                </html>
                <?php
                exit;
            }

            // Check subscription expiration of owner
            $owner = $conn->selectOne('users', ['id' => $websiteRecord['user_id']]);
            if ($owner !== null) {
                $subStatus = checkAndUpdateSubscription($owner, $conn);
                if ($subStatus === 'expired' || $subStatus === 'suspended') {
                    http_response_code(402);
                    ?>
                    <!DOCTYPE html>
                    <html lang="en">
                    <head>
                        <meta charset="UTF-8">
                        <title>Website Deactivated</title>
                        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
                        <style>
                            body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; color: #1e293b; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
                            .card { text-align: center; padding: 40px; background-color: #ffffff; border-radius: 24px; box-shadow: 0 10px 25px rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.08); max-width: 500px; }
                            h1 { font-size: 24px; font-weight: 800; color: #e11d48; margin: 0 0 10px; }
                            p { font-size: 14px; color: #64748b; line-height: 1.6; margin: 0 0 20px; }
                        </style>
                    </head>
                    <body>
                        <div class="card">
                            <h1>Website Deactivated</h1>
                            <p>This website hosting has been temporarily deactivated because the owner's free trial or hosting subscription has expired.</p>
                        </div>
                    </body>
                    </html>
                    <?php
                    exit;
                }
            }
        }

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
