<?php
/**
 * api/websites/create.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../../backend/auth/AuthService.php';
require_once __DIR__ . '/../../backend/services/WebsiteService.php';

enforceAuth(true);

$auth = new AuthService();
$user = $auth->getCurrentUser();

$name = (string)($_POST['name'] ?? '');
$subdomain = (string)($_POST['subdomain'] ?? '');
$customDomain = (string)($_POST['custom_domain'] ?? '');
$template = $_POST['template_folder'] ?? null;

$service = new WebsiteService();
$res = $service->createWebsite($user, $name, $subdomain, $template ? (string)$template : null, $customDomain !== '' ? $customDomain : null);

jsonResponse($res, $res['success'] ? 200 : 400);
