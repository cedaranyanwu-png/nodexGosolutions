<?php
/**
 * generate_sitemap.php
 *
 * Automatically generates a standard search-engine compliant XML sitemap.
 * Scans non-recursively the immediate root `/` directory and `/public/` directory
 * for valid public .php and .html files, excluding internal scripts, configurations, and API controllers.
 * All functions and lines are extensively commented for absolute clarity and scale.
 */

// Enable strict typing for safety
declare(strict_types=1);

// Require central DB helper to access protocol/domain utilities if needed
require_once __DIR__ . '/db.php';

// Set response headers to XML
header('Content-Type: application/xml; charset=utf-8');

// Determine protocol and host domain name
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$hostDomain = $_SERVER['HTTP_HOST'] ?? 'nodexplatform.com.ng';

// Exclude port numbers if they exist
$rawHost = str_contains($hostDomain, ':') ? explode(':', $hostDomain)[0] : $hostDomain;
$baseUrl = "{$protocol}://{$rawHost}";
if (str_contains($hostDomain, ':')) {
    $port = explode(':', $hostDomain)[1];
    $baseUrl = "{$protocol}://{$rawHost}:{$port}";
}

// List of files to explicitly exclude (internal, API, actions, tests, config, or routing handlers)
$explicitExclusions = [
    'Template.php', 'template.php', 'TenantManager.php', 'tests_verifier.php',
    'test_backup.php', 'test_subscription.php', 'php_server.log',
    'admin.php', 'save_page.php', 'index.php' // index.php is normalized to root '/'
];

// Keywords in filenames that mark them as internal utility scripts or controllers rather than public webpages
$excludeKeywords = ['action', 'controller', 'api', 'db', 'handler', 'test', 'payment', 'initialize', 'verify', 'process', 'save', 'manage'];

/**
 * Scans a single directory non-recursively for valid public .php and .html files.
 *
 * @param string $dirPath Absolute path of directory to scan.
 * @param string $urlPrefix Relative URL prefix for the found files (e.g., '/' or '/public/').
 * @param array $exclusions Array of filenames to explicitly exclude.
 * @param array $excludeKeywords Keywords to check against for filtering out internal files.
 * @return array List of valid public absolute URLs mapped to their last modified dates.
 */
function scanDirForPublicPages(string $dirPath, string $urlPrefix, array $exclusions, array $excludeKeywords): array {
    $pages = [];
    if (!is_dir($dirPath)) {
        return [];
    }

    $files = scandir($dirPath);
    if ($files === false) {
        return [];
    }

    foreach ($files as $file) {
        // Skip directory pointers
        if ($file === '.' || $file === '..') {
            continue;
        }

        $fullPath = $dirPath . '/' . $file;

        // Strictly ignore folders (no recursive scanning as per exact sitemap scanning rules)
        if (is_dir($fullPath)) {
            continue;
        }

        // Check file extension
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if ($ext !== 'php' && $ext !== 'html') {
            continue;
        }

        // Exclude specific files
        if (in_array($file, $exclusions, true) || in_array(strtolower($file), array_map('strtolower', $exclusions), true)) {
            continue;
        }

        // Exclude based on utility/processing keywords
        $isInternal = false;
        foreach ($excludeKeywords as $kw) {
            if (str_contains(strtolower($file), $kw)) {
                $isInternal = true;
                break;
            }
        }
        if ($isInternal) {
            continue;
        }

        // Normalize filename (strip .php/.html suffix for clean URLs if index page)
        $cleanName = $file;
        if ($cleanName === 'index.php' || $cleanName === 'index.html') {
            $cleanName = '';
        }

        $lastMod = date('Y-m-d', filemtime($fullPath));
        $relativeUrl = $urlPrefix . $cleanName;

        // Store result
        $pages[$relativeUrl] = $lastMod;
    }

    return $pages;
}

// 1. Scan root directory non-recursively
$rootPages = scanDirForPublicPages(__DIR__ . '/..', '/', $explicitExclusions, $excludeKeywords);

// 2. Scan public directory non-recursively
$publicPages = scanDirForPublicPages(__DIR__ . '/../public', '/public/', $explicitExclusions, $excludeKeywords);

// Combine lists and prevent duplicates
$allDiscoveredPages = array_merge($rootPages, $publicPages);

// Add default homepage '/' as priority
$allDiscoveredPages['/'] = date('Y-m-d', filemtime(__DIR__ . '/../index.php'));

// Clean duplicates
$finalSitemap = [];
foreach ($allDiscoveredPages as $path => $lastmod) {
    // Normalise slash structures
    $normalizedPath = $path === '/' ? '/' : '/' . ltrim($path, '/');
    $finalSitemap[$normalizedPath] = $lastmod;
}

// Output XML sitemap
echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

foreach ($finalSitemap as $path => $lastmod) {
    $loc = htmlspecialchars($baseUrl . $path);
    echo "    <url>" . PHP_EOL;
    echo "        <loc>{$loc}</loc>" . PHP_EOL;
    echo "        <lastmod>{$lastmod}</lastmod>" . PHP_EOL;
    echo "    </url>" . PHP_EOL;
}

echo '</urlset>' . PHP_EOL;
?>
