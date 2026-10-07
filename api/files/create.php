<?php
/**
 * api/files/create.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../../backend/auth/AuthService.php';
require_once __DIR__ . '/../../backend/services/FileManagerService.php';

enforceAuth(true);

$auth = new AuthService();
$user = $auth->getCurrentUser();

$subdomain = (string)($_POST['subdomain'] ?? '');
$itemType = (string)($_POST['type'] ?? 'file'); // 'file' or 'folder'
$itemName = (string)($_POST['name'] ?? '');
$targetPath = (string)($_POST['path'] ?? '');

$service = new FileManagerService();
$rootDir = $service->getWebsiteRoot($user, $subdomain);

if (!$rootDir) {
    jsonResponse(['success' => false, 'message' => 'Website workspace not found or access denied.'], 403);
}

$fullRelPath = trim($targetPath, '/') . ($targetPath !== '' ? '/' : '') . ltrim($itemName, '/');

if ($itemType === 'folder') {
    $res = $service->createFolder($rootDir, $fullRelPath);
} else {
    $res = $service->saveFile($rootDir, $fullRelPath, "<!-- New file created in " . htmlspecialchars($subdomain) . " -->\n");
}

jsonResponse($res, $res['success'] ? 200 : 400);
