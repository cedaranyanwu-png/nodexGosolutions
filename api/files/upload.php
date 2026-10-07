<?php
/**
 * api/files/upload.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../../backend/auth/AuthService.php';
require_once __DIR__ . '/../../backend/services/FileManagerService.php';

enforceAuth(true);

$auth = new AuthService();
$user = $auth->getCurrentUser();

$subdomain = (string)($_POST['subdomain'] ?? '');
$targetPath = (string)($_POST['path'] ?? '');

if (!isset($_FILES['file'])) {
    jsonResponse(['success' => false, 'message' => 'No upload files provided.'], 400);
}

$service = new FileManagerService();
$rootDir = $service->getWebsiteRoot($user, $subdomain);

if (!$rootDir) {
    jsonResponse(['success' => false, 'message' => 'Website workspace not found or access denied.'], 403);
}

$res = $service->uploadFiles($rootDir, $targetPath, $_FILES['file']);
jsonResponse($res, $res['success'] ? 200 : 400);
