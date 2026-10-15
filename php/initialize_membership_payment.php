<?php
/**
 * initialize_membership_payment.php
 *
 * Secure backend processor for initializing visitor membership plan purchases.
 * Routes payments through the platform's central Flutterwave gateway credentials.
 * Tracks target website, plan, owner user_id, and customer metadata.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

secureSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$conn->createTable('membership_plans');
$conn->createTable('websites');
$conn->createTable('settings');
$conn->createTable('payment_transactions');

$planId        = (int)($_POST['plan_id'] ?? 0);
$customerName  = cleanInput($_POST['customer_name'] ?? '');
$customerEmail = cleanInput($_POST['customer_email'] ?? '');

if ($planId <= 0 || empty($customerEmail)) {
    jsonResponse(['success' => false, 'message' => 'Valid membership plan and customer email required.'], 400);
}

$plan = $conn->selectOne('membership_plans', ['id' => $planId, 'is_active' => 1]);
if (!$plan) {
    jsonResponse(['success' => false, 'message' => 'Membership plan not found or inactive.'], 404);
}

$websiteId = (int)$plan['website_id'];
$ownerId   = (int)$plan['user_id'];
$amount    = (float)$plan['price'];

// Fetch global platform Flutterwave keys
$allSettings = $conn->select('settings') ?: [];
$flwSettings = $allSettings[0] ?? null;
$secretKey   = $flwSettings['flw_secret_key'] ?? '';

if (empty($secretKey)) {
    jsonResponse(['success' => false, 'message' => 'Payment gateway error: Flutterwave parameters not configured by Administrator.'], 503);
}

// Generate unique transaction reference
$txRef = 'MEMB-' . time() . '-' . rand(1000, 9999);

// Construct redirect callback URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'nodexplatform.com.ng';
$redirectUrl = "{$protocol}://{$host}/php/verify_membership_payment.php?tx_ref={$txRef}";

// Save pending payment transaction
$conn->insert('payment_transactions', [
    'tx_ref'           => $txRef,
    'type'             => 'membership',
    'plan_id'          => $planId,
    'website_id'       => $websiteId,
    'owner_user_id'    => $ownerId,
    'customer_name'    => $customerName,
    'customer_email'   => $customerEmail,
    'amount'           => $amount,
    'currency'         => 'NGN',
    'status'           => 'pending',
    'created_at'       => date('Y-m-d H:i:s')
]);

// Initialize Flutterwave Hosted Checkout
$flwPayload = [
    'tx_ref'          => $txRef,
    'amount'          => $amount,
    'currency'        => 'NGN',
    'redirect_url'    => $redirectUrl,
    'payment_options' => 'card,banktransfer,account',
    'customer'        => [
        'email'       => $customerEmail,
        'name'        => $customerName ?: $customerEmail
    ],
    'customizations'  => [
        'title'       => $plan['title'] . ' Membership',
        'description' => "Subscription to " . $plan['title'],
        'logo'        => "{$protocol}://{$host}/main/assets/images/logo.png"
    ]
];

$ch = curl_init('https://api.flutterwave.com/v3/payments');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $secretKey,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($flwPayload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    jsonResponse(['success' => false, 'message' => 'Flutterwave Connection Error: ' . $curlErr], 500);
}

$resData = json_decode((string)$response, true);
if (isset($resData['status']) && $resData['status'] === 'success' && !empty($resData['data']['link'])) {
    jsonResponse([
        'success'      => true,
        'checkout_url' => $resData['data']['link'],
        'tx_ref'       => $txRef
    ]);
} else {
    jsonResponse([
        'success' => false,
        'message' => $resData['message'] ?? 'Failed to initialize membership payment link.'
    ], 400);
}
?>