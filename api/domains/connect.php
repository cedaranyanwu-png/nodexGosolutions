<?php
/**
 * api/domains/connect.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../../backend/auth/AuthService.php';
require_once __DIR__ . '/../../backend/services/DomainService.php';

enforceAuth(true);

$auth = new AuthService();
$user = $auth->getCurrentUser();

$websiteId = (int)($_POST['website_id'] ?? 0);
$customDomain = (string)($_POST['custom_domain'] ?? '');

$service = new DomainService();
$res = $service->connectCustomDomain($user, $websiteId, $customDomain);

jsonResponse($res, $res['success'] ? 200 : 400);
