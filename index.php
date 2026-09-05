<?php

/**
 * NodeX Platform - Universal Single Entry Point
 *
 * All HTTP requests route through /index.php.
 */

declare(strict_types=1);

// Standard PSR-4 style autoloader supporting case-insensitive directory mapping
spl_autoload_register(function ($class) {
    $prefix = 'Main\\';
    $baseDir = __DIR__ . '/main/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $parts = explode('\\', $relativeClass);

    // The class name (last element) keeps exact casing, directory parts map to lower-case
    $className = array_pop($parts);
    $subDir = !empty($parts) ? strtolower(implode('/', $parts)) . '/' : '';

    $file = $baseDir . $subDir . $className . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

use Main\Config\DomainConfig;
use Main\Database\JsonDatabase;
use Main\Discovery\MainDiscovery;
use Main\Services\DomainResolver;
use Main\Services\ProjectResolver;
use Main\Session\Session;

// Initialize Universal Session
Session::start();

// Load Domain Configuration
DomainConfig::load();

// Capture Host (fallback dynamically to main domain from DomainConfig)
$currentHost = $_SERVER['HTTP_HOST'] ?? DomainConfig::mainDomain();

// 1. Resolve Domain
$db = new JsonDatabase(__DIR__ . '/main/storage/db');
$domainResolver = new DomainResolver($db);
$resolution = $domainResolver->resolve($currentHost);

// 2. Dispatch based on domain ownership
if ($resolution['type'] === 'MAIN') {
    // Route MAIN platform request
    $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

    if ($resolution['target'] === 'main_subdomain' && $resolution['subdomain_key'] === 'admin') {
        require_once __DIR__ . '/main/modules/admin/index.php';
        exit;
    }

    if ($resolution['target'] === 'main_subdomain' && $resolution['subdomain_key'] === 'api') {
        require_once __DIR__ . '/main/api/router.php';
        exit;
    }

    // Default MAIN router handling
    if (str_starts_with($requestUri, '/api/')) {
        require_once __DIR__ . '/main/api/router.php';
        exit;
    }

    if (str_starts_with($requestUri, '/admin')) {
        require_once __DIR__ . '/main/modules/admin/index.php';
        exit;
    }

    // Render MAIN platform homepage
    echo "<!DOCTYPE html><html><head><title>NodeX Main Platform</title>";
    echo "<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css'></head>";
    echo "<body class='bg-light'><div class='container py-5'>";
    echo "<h1 class='text-primary fw-bold'>NodeX Platform Core</h1>";
    echo "<p class='lead'>Welcome to the central platform controller.</p>";
    echo "<p>Host: <code>" . htmlspecialchars($currentHost) . "</code></p>";
    echo "<div class='mt-4'><a href='/admin' class='btn btn-primary'>Admin Portal</a></div>";
    echo "</div></body></html>";
    exit;
}

if ($resolution['type'] === 'TENANT') {
    $projectId = $resolution['project_id'];
    $projectResolver = new ProjectResolver($db, __DIR__ . '/public');
    $projectResult = $projectResolver->resolveProject($projectId);

    if (!$projectResult['success']) {
        http_response_code(403);
        echo "<!DOCTYPE html><html><head><title>Tenant Unavailable</title>";
        echo "<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css'></head>";
        echo "<body class='bg-light text-center py-5'><div class='container'>";
        echo "<h2 class='text-danger'>Project Access Unavailable</h2>";
        echo "<p>" . htmlspecialchars($projectResult['message']) . "</p>";
        echo "</div></body></html>";
        exit;
    }

    // Isolate tenant execution scope
    $tenantProject = $projectResult['project'];
    $tenantFolder = $projectResult['folder_path'];

    if ($projectResult['entry_file'] && file_exists($projectResult['entry_file'])) {
        require_once $projectResult['entry_file'];
        exit;
    } else {
        echo "<!DOCTYPE html><html><head><title>" . htmlspecialchars($tenantProject['id']) . "</title></head>";
        echo "<body><h1>Tenant Project: " . htmlspecialchars($tenantProject['id']) . "</h1>";
        echo "<p>Project is active but contains no entry index.php.</p></body></html>";
        exit;
    }
}

// 3. Unknown Domain or Unregistered Target
http_response_code(404);
echo "<!DOCTYPE html><html><head><title>404 Not Found</title>";
echo "<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css'></head>";
echo "<body class='bg-light text-center py-5'><div class='container'>";
echo "<h1 class='display-1 text-secondary fw-bold'>404</h1>";
echo "<h2>Domain / Project Not Found</h2>";
echo "<p>The domain <code>" . htmlspecialchars($currentHost) . "</code> is not registered on this platform.</p>";
echo "</div></body></html>";
exit;
