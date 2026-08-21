<?php
/**
 * api/files/read.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../../backend/auth/AuthService.php';
require_once __DIR__ . '/../../backend/services/FileManagerService.php';

enforceAuth(true);

$auth = new AuthService();
$user = $auth->getCurrentUser();

$subdomain = (string)($_GET['subdomain'] ?? $_POST['subdomain'] ?? '');
$file = (string)($_GET['file'] ?? $_POST['file'] ?? '');

$service = new FileManagerService();
$rootDir = $service->getWebsiteRoot($user, $subdomain);

if (!$rootDir) {
    jsonResponse(['success' => false, 'message' => 'Website workspace not found or access denied.'], 403);
}

$res = $service->readFile($rootDir, $file);
jsonResponse($res, $res['success'] ? 200 : 400);
