<?php
/**
 * api/files/rename.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../../backend/auth/AuthService.php';
require_once __DIR__ . '/../../backend/services/FileManagerService.php';

enforceAuth(true);

$auth = new AuthService();
$user = $auth->getCurrentUser();

$subdomain = (string)($_POST['subdomain'] ?? '');
$oldPath = (string)($_POST['old_path'] ?? '');
$newName = (string)($_POST['new_name'] ?? '');

$service = new FileManagerService();
$rootDir = $service->getWebsiteRoot($user, $subdomain);

if (!$rootDir) {
    jsonResponse(['success' => false, 'message' => 'Website workspace not found or access denied.'], 403);
}

$res = $service->renameItem($rootDir, $oldPath, $newName);
jsonResponse($res, $res['success'] ? 200 : 400);
