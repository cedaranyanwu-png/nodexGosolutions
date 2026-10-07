<?php
/**
 * api/payments/initialize.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../../backend/auth/AuthService.php';
require_once __DIR__ . '/../../backend/services/PaymentService.php';

enforceAuth(true);

$auth = new AuthService();
$user = $auth->getCurrentUser();

$planId = (string)($_POST['plan_id'] ?? '');

$service = new PaymentService();
$res = $service->initializePayment($user, $planId);

jsonResponse($res, $res['success'] ? 200 : 400);
