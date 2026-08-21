<?php
/**
 * api/payments/verify.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../../backend/services/PaymentService.php';

$txRef = (string)($_GET['tx_ref'] ?? $_POST['tx_ref'] ?? '');
$txId = $_GET['transaction_id'] ?? $_POST['transaction_id'] ?? null;

if (empty($txRef)) {
    jsonResponse(['success' => false, 'message' => 'Transaction reference required.'], 400);
}

$service = new PaymentService();
$res = $service->verifyPayment($txRef, $txId ? (string)$txId : null);

jsonResponse($res, $res['success'] ? 200 : 400);
