<?php
/**
 * api/domains/remove.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../../backend/auth/AuthService.php';
require_once __DIR__ . '/../../backend/services/DomainService.php';

enforceAuth(true);

$auth = new AuthService();
$user = $auth->getCurrentUser();

$websiteId = (int)($_POST['website_id'] ?? 0);

$service = new DomainService();
$res = $service->removeCustomDomain($user, $websiteId);

jsonResponse($res, $res['success'] ? 200 : 400);
