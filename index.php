<?php
/**
 * index.php
 *
 * Single entry point for the entire NodeX platform.
 * Every request is routed here via .htaccess rules.
 *
 * Responsibilities:
 * 1. Initialize environment and central configuration engine (/main/php/sys_config.php).
 * 2. Load DomainManager and TenantManager.
 * 3. Dispatch requests via SysRouter to MAIN OS (/main/), MAIN-CONNECTED, or TENANT apps.
 */

declare(strict_types=1);

// Load central system files from /main/php/
require_once __DIR__ . '/main/php/sys_config.php';
require_once __DIR__ . '/main/php/DomainManager.php';
require_once __DIR__ . '/main/php/TenantManager.php';
require_once __DIR__ . '/main/php/sys_services.php';
require_once __DIR__ . '/main/php/sys_router.php';

// Capture host from $_SERVER
$currentHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
if (str_contains($currentHost, ':')) {
    $currentHost = explode(':', $currentHost)[0];
}

// Dispatch request through SysRouter
$router = new SysRouter();
$router->dispatch($currentHost);
