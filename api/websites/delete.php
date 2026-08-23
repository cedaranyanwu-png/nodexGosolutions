<?php
/**
 * api/websites/delete.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../../backend/auth/AuthService.php';
require_once __DIR__ . '/../../backend/services/WebsiteService.php';

enforceAuth(true);

$auth = new AuthService();
$user = $auth->getCurrentUser();

$websiteId = (int)($_POST['website_id'] ?? $_GET['website_id'] ?? 0);

$service = new WebsiteService();
$res = $service->deleteWebsite($user, $websiteId);

jsonResponse($res, $res['success'] ? 200 : 400);
