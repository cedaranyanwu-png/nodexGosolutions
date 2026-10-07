<?php
/**
 * api/websites/list.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../../backend/auth/AuthService.php';
require_once __DIR__ . '/../../backend/services/WebsiteService.php';

enforceAuth(true);

$auth = new AuthService();
$user = $auth->getCurrentUser();

$service = new WebsiteService();
$websites = $service->getUserWebsites((int)$user['id']);

jsonResponse(['success' => true, 'websites' => $websites]);
