<?php
/**
 * Compatibility adapter for the User Manage Files tab.
 *
 * The dashboard keeps this path and tab identifier for backward compatibility.
 * The actual workspace presentation now lives in modules/user/file-manager/view.php.
 * APIs, service classes, authorization, and JavaScript selectors remain stable.
 */
declare(strict_types=1);
$tabId = 'manage-files';
if (!isset($dashboardContext)) {
    $requestedTab = $tabId;
    require_once __DIR__ . '/../dashboard.php';
    exit;
}
require __DIR__ . '/../../../../modules/user/file-manager/view.php';
