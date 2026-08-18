<?php
/**
 * initialize_payment.php
 *
 * Secure backend-driven initializer for Flutterwave hosted checkout.
 * Authenticates user, loads pricing plans exclusively from the database to prevent client tampering,
 * constructs transaction references, and executes a secure server-to-server cURL call
 * to initialize the Flutterwave payment checkout transaction link.
 * Every line and configuration parameter is thoroughly commented for scale and security.
 */

// Enable strict typing for safety
declare(strict_types=1);

// Require central system config and db connector
require_once __DIR__ . '/db.php';

// Instantiate secure session context
secureSession();

// Restrict to POST requests for security
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

// Access Control: Ensure standard user session is active
if (!isset($_SESSION['email'])) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized access. Please log in.'], 401);
}

// Extract targeted pricing plan ID from payload
$planId = cleanInput($_POST['plan_id'] ?? '');
if (empty($planId)) {
    jsonResponse(['success' => false, 'message' => 'Please select a valid pricing plan.'], 400);
}

// Query plan details exclusively from DB to neutralize price tampering attempts
$conn->createTable('plans');
$plan = $conn->selectOne('plans', ['id' => $planId]);
if (!$plan) {
    $plan = $conn->selectOne('plans', ['id' => (int)$planId]);
}
if (!$plan) {
    $allPlans = $conn->select('plans') ?: [];
    foreach ($allPlans as $p) {
        if (strtolower((string)($p['id'] ?? '')) === strtolower($planId) || strtolower((string)($p['name'] ?? '')) === strtolower($planId)) {
            $plan = $p;
            break;
        }
    }
}
if (!$plan || (int)($plan['is_active'] ?? 0) !== 1) {
    jsonResponse(['success' => false, 'message' => 'The selected pricing plan is inactive or invalid.'], 404);
}

// Retrieve current authenticated user database reference
$user = $conn->selectOne('users', ['email' => $_SESSION['email']]);
if (!$user) {
    jsonResponse(['success' => false, 'message' => 'User record not found.'], 404);
}

// Load custom Flutterwave keys securely from server-side database
$conn->createTable('settings');
$allSettings = $conn->select('settings') ?: [];
$flwSettings = $allSettings[0] ?? null;
$secretKey = (string)($flwSettings['flw_secret_key'] ?? '');

if (empty($secretKey)) {
    $envSecret = getenv('FLW_SECRET_KEY') ?: ($_ENV['FLW_SECRET_KEY'] ?? '');
    if (!empty($envSecret)) {
        $secretKey = (string)$envSecret;
    }
}

if (empty($secretKey)) {
    jsonResponse(['success' => false, 'message' => 'Payment Gateway Error: Flutterwave credentials are not configured by Administrator.'], 503);
}

// Extract price parameters
$amount   = (float)$plan['price'];
$currency = $plan['currency'] ?? 'NGN';

// Generate a cryptographically secure, unique transaction reference string
$txRef = 'tx_' . generateSecureToken(8) . '_' . $user['id'] . '_' . time();

// Save the pending payment transaction inside the database log table for auditing
$conn->createTable('payments');
$conn->insert('payments', [
    'user_id'        => $user['id'],
    'plan_id'        => $plan['id'],
    'plan_name'      => $plan['name'],
    'amount'         => $amount,
    'currency'       => $currency,
    'tx_ref'         => $txRef,
    'status'         => 'pending',
    'created_at'     => date('Y-m-d H:i:s'),
    'completed_at'   => null
]);

// Determine protocol and host domain names to configure redirection routes
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$hostDomain = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
$redirectUrl = "{$protocol}://{$hostDomain}/php/verify_payment.php";
$checkoutPageUrl = "{$protocol}://{$hostDomain}/php/flutterwave_checkout.php?tx_ref={$txRef}";

// Check if we are operating with dummy sandbox fallback keys (i.e. admin hasn't provided real keys)
if (str_contains($secretKey, 'TEST-sandbox')) {
    // Standard mock checkout fallback for sandbox development testing
    jsonResponse([
        'success' => true,
        'link'    => $checkoutPageUrl,
        'message' => 'Flutterwave sandbox checkout page generated.'
    ]);
}

// Set up Flutterwave v3 API hosted payments initialization request parameters
$payload = [
    'tx_ref'         => $txRef,
    'amount'         => $amount,
    'currency'       => $currency,
    'redirect_url'   => $redirectUrl,
    'customer'       => [
        'email' => $user['email'],
        'name'  => $user['fullname'] ?? 'Customer'
    ],
    'customizations' => [
        'title'       => 'nodexGo Hosting Space',
        'description' => "Activation / Upgrade subscription for '{$plan['name']}' plan",
        'logo'        => "{$protocol}://{$hostDomain}/css/logo.png"
    ]
];

// Initialize secure server-to-server cURL request to Flutterwave API v3 endpoint
$ch = curl_init('https://api.flutterwave.com/v3/payments');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$secretKey}",
    "Content-Type: application/json"
]);

// Execute live payment initialization request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false || !empty($curlError)) {
    jsonResponse([
        'success' => false,
        'message' => 'Flutterwave API Connection Error: ' . ($curlError ?: 'Unable to reach Flutterwave servers.')
    ], 502);
}

// Parse returned JSON response from Flutterwave API
$resData = json_decode((string)$response, true);

if ($httpCode === 200 && isset($resData['status']) && $resData['status'] === 'success' && !empty($resData['data']['link'])) {
    // Successfully initialized live hosted checkout link on Flutterwave
    jsonResponse([
        'success' => true,
        'link'    => $resData['data']['link'],
        'message' => 'Redirecting to Flutterwave checkout.'
    ]);
} else {
    $errMessage = $resData['message'] ?? 'Failed to initialize Flutterwave live checkout.';
    jsonResponse([
        'success' => false,
        'message' => 'Flutterwave Gateway Error: ' . $errMessage
    ], 400);
}
?>
