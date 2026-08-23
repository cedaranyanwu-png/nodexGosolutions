<?php
declare(strict_types=1);

require_once __DIR__ . '/../../backend/auth/AuthService.php';
require_once __DIR__ . '/../../backend/services/FileManagerService.php';

enforceAuth(true);
$auth = new AuthService();
$user = $auth->getCurrentUser();
$subdomain = strtolower(trim((string)($_POST['subdomain'] ?? '')));
$sourcePath = (string)($_POST['source_path'] ?? '');
$destinationPath = (string)($_POST['destination_path'] ?? '');

$service = new FileManagerService();
$root = $service->getWebsiteRoot($user, $subdomain);
if (!$root) jsonResponse(['success' => false, 'message' => 'Website workspace not found or access denied.'], 403);
jsonResponse($service->copyItem($root, $sourcePath, $destinationPath));
