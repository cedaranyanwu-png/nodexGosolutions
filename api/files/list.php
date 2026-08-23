<?php
/**
 * api/files/list.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../../backend/auth/AuthService.php';
require_once __DIR__ . '/../../backend/services/FileManagerService.php';

enforceAuth(true);

$auth = new AuthService();
$user = $auth->getCurrentUser();

$subdomain = (string)($_GET['subdomain'] ?? $_POST['subdomain'] ?? '');
$path = (string)($_GET['path'] ?? $_POST['path'] ?? '');

$service = new FileManagerService();
$rootDir = $service->getWebsiteRoot($user, $subdomain);

if (!$rootDir) {
    jsonResponse(['success' => false, 'message' => 'Website workspace not found or access denied.'], 403);
}

$res = $service->listFiles($rootDir, $path);
jsonResponse($res, $res['success'] ? 200 : 400);
