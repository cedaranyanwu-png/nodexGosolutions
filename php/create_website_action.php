<?php
/**
 * create_website_action.php
 *
 * Secure backend processor that handles physical provisioning of multi-tenant hosting spaces.
 * Creates an isolated physical subdirectory under /public/ for the tenant's custom subdomain.
 * Automatically provisions basic template files (index.html, css/style.css, js/main.js)
 * to ensure that the site is instantly functional when visited through wildcards/subdomains.
 * All functions and lines are extensively commented for absolute clarity and scale.
 */

// Enable strict typing for safety
declare(strict_types=1);

// Require central system configurations and database engine
require_once __DIR__ . '/db.php';

// Instantiate secure session context
secureSession();

// Restrict to authenticated standard user or administrator sessions
if (!isset($_SESSION['email'])) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized access. Please log in.'], 401);
}

// Restrict requests to POST actions only for structural safety
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

// Fetch current user reference data to check subscription validity
$user = $conn->selectOne('users', ['email' => $_SESSION['email']]);
if (!$user) {
    jsonResponse(['success' => false, 'message' => 'User record not found.'], 404);
}

// Server-side enforcement check: restrict workspace creations to accounts on ACTIVE or TRIAL plans
$status = checkAndUpdateSubscription($user, $conn);
if ($status === 'expired' || $status === 'suspended') {
    jsonResponse([
        'success' => false,
        'message' => 'Your subscription has expired. Please select a plan to activate website provisioning.'
    ], 403);
}

// Extract website creation payload parameters
$websiteName      = cleanInput($_POST['website_name'] ?? '');
$websiteSubdomain = strtolower(cleanInput($_POST['website_subdomain'] ?? ''));

// Validate non-empty form credentials
if (empty($websiteName) || empty($websiteSubdomain)) {
    jsonResponse(['success' => false, 'message' => 'Please provide both website name and subdomain prefix.'], 400);
}

// Enforce strict subdomain rules (alphanumeric and hyphens only, no directory traversals allowed)
if (!preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/', $websiteSubdomain)) {
    jsonResponse(['success' => false, 'message' => 'Subdomain must be lowercase alphanumeric and can include hyphens only.'], 400);
}

// Enforce length limit constraints on the chosen subdomain to maintain layout consistency
if (strlen($websiteSubdomain) < 3 || strlen($websiteSubdomain) > 20) {
    jsonResponse(['success' => false, 'message' => 'Subdomain prefix length must be between 3 and 20 characters.'], 400);
}

// Define reserved system-level subdomains to prevent page routing collisions
$reservedSubdomains = ['www', 'admin', 'api', 'mail', 'webmail', 'localhost', 'nodexgo', 'system', 'main'];
if (in_array($websiteSubdomain, $reservedSubdomains, true)) {
    jsonResponse(['success' => false, 'message' => 'The chosen subdomain is reserved for platform systems.'], 409);
}

// Check database for pre-existing subdomain registrations across all tenants
$conn->createTable('websites');
$existingWeb = $conn->selectOne('websites', ['subdomain' => $websiteSubdomain]);
if ($existingWeb !== null) {
    jsonResponse(['success' => false, 'message' => 'This subdomain prefix is already active on the platform.'], 409);
}

// ============================================================
// PHYSICAL WORKSPACE PROVISIONING LAYER
// ============================================================

// Resolve absolute paths for the public/ hosting directory
$publicRoot = realpath(__DIR__ . '/../');
if ($publicRoot === false) {
    $publicRoot = __DIR__ . '/..';
}

$publicDir = $publicRoot . '/public';
// Dynamically create the primary public directory if missing on host system
if (!is_dir($publicDir)) {
    if (!mkdir($publicDir, 0755, true)) {
        jsonResponse(['success' => false, 'message' => 'Internal Server Error: Failed to initialize primary public directory.'], 500);
    }
}

// Construct absolute path for the tenant's individual website directory
$tenantDir = $publicDir . '/' . $websiteSubdomain;

// Prevent directory injection attacks by resolving realpaths and enforcing bounds checks
$realPublicDir = realpath($publicDir);
if ($realPublicDir !== false) {
    // If the folder already exists, verify it doesn't escape our root boundaries
    if (file_exists($tenantDir)) {
        $realTenantDir = realpath($tenantDir);
        if ($realTenantDir === false || !str_starts_with($realTenantDir, $realPublicDir)) {
            jsonResponse(['success' => false, 'message' => 'Invalid directory boundary traversal detected.'], 403);
        }
    }
}

// Create tenant individual subdirectory
if (!is_dir($tenantDir)) {
    if (!mkdir($tenantDir, 0755, true)) {
        jsonResponse(['success' => false, 'message' => 'Server Configuration Error: Failed to provision physical workspace.'], 500);
    }
}

// Define tenant subfolders list to maintain visual asset layouts
$subfolders = ['css', 'js', 'assets'];
foreach ($subfolders as $sub) {
    $folderPath = $tenantDir . '/' . $sub;
    if (!is_dir($folderPath)) {
        mkdir($folderPath, 0755, true);
    }
}

// Determine protocol and domain names to formulate target preview URL
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$hostDomain = $_SERVER['HTTP_HOST'] ?? 'nodexplatform.com.ng';
if (str_contains($hostDomain, ':')) {
    $hostDomain = explode(':', $hostDomain)[0];
}

// Formulate correct subdomain hosting preview URL structure
$siteUrl = "{$protocol}://{$websiteSubdomain}.{$hostDomain}";
// If running on a local development port, preserve port numbers for instant previews
if (isset($_SERVER['HTTP_HOST']) && str_contains($_SERVER['HTTP_HOST'], ':')) {
    $port = explode(':', $_SERVER['HTTP_HOST'])[1];
    $siteUrl = "{$protocol}://{$websiteSubdomain}.{$hostDomain}:{$port}";
}

// Construct dynamic default index webpage content
$indexHtml = "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>" . htmlspecialchars($websiteName) . " | nodexGo Hosted</title>
    <link href='https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap' rel='stylesheet'>
    <link rel='stylesheet' href='css/style.css'>
</head>
<body style='margin: 0; font-family: \"Plus Jakarta Sans\", sans-serif; background-color: #f8fafc; color: #1e293b; display: flex; align-items: center; justify-content: center; min-height: 100vh;'>
    <div style='text-align: center; padding: 40px; background-color: #ffffff; border-radius: 24px; box-shadow: 0 10px 25px rgba(0, 114, 255, 0.05); border: 1px solid rgba(0, 114, 255, 0.08); max-width: 500px;'>
        <div style='width: 72px; height: 72px; background-color: rgba(0, 114, 255, 0.1); color: #0072ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 20px;'>
            🚀
        </div>
        <h1 style='font-size: 28px; font-weight: 800; color: #0f172a; margin: 0 0 10px;'>" . htmlspecialchars($websiteName) . "</h1>
        <p style='font-size: 14px; color: #64748b; line-height: 1.6; margin: 0 0 25px;'>Your physical workspace has been successfully provisioned on nodexGo! Customize files directly through your isolated File Manager tab.</p>
        <div style='font-family: monospace; font-size: 12px; background-color: #f1f5f9; padding: 10px 15px; border-radius: 12px; color: #0f172a; word-break: break-all;'>
            Folder: public/" . htmlspecialchars($websiteSubdomain) . "/
        </div>
        <div style='margin-top: 25px;'>
            <a href='#' id='clickBtn' style='display: inline-block; background-color: #0072ff; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 13px; padding: 12px 24px; border-radius: 9999px; box-shadow: 0 4px 12px rgba(0, 114, 255, 0.25); transition: background-color 0.2s;'>Interact Page</a>
        </div>
    </div>
    <script src='js/main.js'></script>
</body>
</html>
";

// Construct default stylesheet rules
$styleCss = "
/* Default visual stylesheet generated by nodexGo multi-tenant engine */
body {
    background-color: #f8fafc;
}
#clickBtn:hover {
    background-color: #0056b3 !important;
}
";

// Construct default javascript element interactors
$mainJs = "
// Default scripts loader for " . htmlspecialchars($websiteName) . "
document.getElementById('clickBtn').addEventListener('click', function(e) {
    e.preventDefault();
    alert('Hello from " . htmlspecialchars($websiteName) . "! Your workspace is completely interactive.');
});
";

// Write provisioned default templates onto tenant directory
file_put_contents($tenantDir . '/index.html', $indexHtml, LOCK_EX);
file_put_contents($tenantDir . '/css/style.css', $styleCss, LOCK_EX);
file_put_contents($tenantDir . '/js/main.js', $mainJs, LOCK_EX);

// Insert metadata tracking parameters inside system JSON table
$newSite = $conn->insert('websites', [
    'user_id'    => $user['id'],
    'name'       => $websiteName,
    'subdomain'  => $websiteSubdomain,
    'url'        => $siteUrl,
    'folder'     => $websiteSubdomain,
    'created_at' => date('Y-m-d H:i:s')
]);

if ($newSite !== false) {
    jsonResponse([
        'success'   => true,
        'message'   => "Website '{$websiteName}' created and physically provisioned successfully!",
        'subdomain' => $websiteSubdomain,
        'url'       => $siteUrl
    ]);
} else {
    jsonResponse(['success' => false, 'message' => 'Failed to persist website project metadata in database.'], 500);
}
?>
