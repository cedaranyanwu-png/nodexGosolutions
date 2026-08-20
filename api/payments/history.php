<?php
/**
 * api/payments/history.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../../backend/auth/AuthService.php';
require_once __DIR__ . '/../../backend/services/PaymentService.php';

enforceAuth(true);

$auth = new AuthService();
$user = $auth->getCurrentUser();

$service = new PaymentService();
$history = $service->getUserPaymentHistory((int)$user['id']);

jsonResponse(['success' => true, 'payments' => $history]);
