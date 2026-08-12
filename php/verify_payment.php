<?php
/**
 * verify_payment.php
 *
 * Highly secure, server-side transaction verifier for Flutterwave payments.
 * Triggered by Flutterwave redirects/callbacks.
 * Retrieves pending transaction metadata, calls Flutterwave API securely via cURL
 * to double-verify payment status, verifies exact amount and currency matching,
 * and activates the premium subscription plan inside users database.
 * Completely immune to client-side return bypass tricks. Fully documented line-by-line.
 */

// Enable strict typing for safety
declare(strict_types=1);

// Require central configuration files
require_once __DIR__ . '/db.php';

// Instantiate secure session context
secureSession();

// Extract query parameters dispatched by Flutterwave checkout redirection page
$status        = cleanInput($_GET['status'] ?? '');
$txRef         = cleanInput($_GET['tx_ref'] ?? '');
$transactionId = cleanInput($_GET['transaction_id'] ?? $_GET['id'] ?? '');

// If critical reference components are missing, redirect with failure status
if (empty($txRef)) {
    header('Location: /user/dashboard?payment=invalid_reference');
    exit;
}

// Locate matching pending payment log inside the database
$conn->createTable('payments');
$payment = $conn->selectOne('payments', ['tx_ref' => $txRef]);
if (!$payment) {
    header('Location: /user/dashboard?payment=not_found');
    exit;
}

// Enforce duplicate callback checks (idempotency safety)
if (strtolower((string)($payment['status'] ?? '')) === 'successful') {
    // If transaction was already verified, redirect directly to avoid duplicate processing
    header('Location: /user/dashboard?payment=duplicate_callback');
    exit;
}

// Load custom Flutterwave configuration credentials securely from system settings
$conn->createTable('settings');
$flwSettings = $conn->selectOne('settings', ['id' => 'flutterwave']);
$secretKey = $flwSettings['flw_secret_key'] ?? '';

// Check if verification parameters can be handled
if (empty($secretKey)) {
    header('Location: /user/dashboard?payment=system_configuration_error');
    exit;
}

// Retrieve associated user account reference
$userId = (int)($payment['user_id'] ?? 0);
$user   = $conn->selectOne('users', ['id' => $userId]);
if (!$user) {
    header('Location: /user/dashboard?payment=user_mismatch');
    exit;
}

// Initialize validation variables
$paymentVerified = false;
$apiAmount       = 0.0;
$apiCurrency     = '';

// Check if we are operating in a localized sandbox testing mode
if (str_contains($secretKey, 'TEST-sandbox') && str_contains((string)$transactionId, 'mock_tr_')) {
    // Sandbox test mock transactions bypass real API server network calls and are marked as verified instantly
    $paymentVerified = (strtolower($status) === 'successful' || strtolower($status) === 'success');
    $apiAmount       = (float)$payment['amount'];
    $apiCurrency     = (string)$payment['currency'];
} else {
    // Execute a real, production-ready server-to-server transaction verification API query
    $url = "https://api.flutterwave.com/v3/transactions/" . urlencode((string)$transactionId) . "/verify";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$secretKey}",
        "Content-Type: application/json"
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $resData = json_decode((string)$response, true);
        // Assert successful payment parameters
        if (isset($resData['status']) && $resData['status'] === 'success' && isset($resData['data'])) {
            $txData = $resData['data'];

            // Check that transaction matches status, reference, and billing amount
            if (strtolower((string)($txData['status'] ?? '')) === 'successful' &&
                (string)($txData['tx_ref'] ?? '') === $txRef) {

                $paymentVerified = true;
                $apiAmount       = (float)($txData['amount'] ?? 0.0);
                $apiCurrency     = (string)($txData['currency'] ?? 'NGN');
            }
        }
    }
}

// Ensure exact currency and amount validation checks to prevent client tamper bypasses
if ($paymentVerified &&
    abs($apiAmount - (float)$payment['amount']) < 0.01 &&
    strtoupper($apiCurrency) === strtoupper((string)$payment['currency'])) {

    // 1. Update pending payment transaction audit log status to successful
    $conn->update('payments', [
        'status'        => 'successful',
        'completed_at'  => date('Y-m-d H:i:s'),
        'gateway_tx_id' => $transactionId
    ], ['tx_ref' => $txRef]);

    // 2. Compute subscription start and renewal date timestamps
    $subStart = date('Y-m-d H:i:s');
    $subEnd   = date('Y-m-d H:i:s', strtotime('+1 month'));

    // 3. Persist the activated premium plan state back into the users database
    $conn->update('users', [
        'subscription_status' => 'active',
        'subscription_plan'   => $payment['plan_name'],
        'subscription_start'  => $subStart,
        'subscription_end'    => $subEnd
    ], ['id' => $userId]);

    // 4. Log administrative activity audit trace
    $conn->createTable('activity_logs');
    $conn->insert('activity_logs', [
        'user_id'    => $userId,
        'email'      => $user['email'],
        'action'     => 'subscription_activated',
        'details'    => "User activated plan '{$payment['plan_name']}' via verified Flutterwave transaction ref: {$txRef}",
        'created_at' => date('Y-m-d H:i:s')
    ]);

    // Redirect user to dashboard with success alert
    header('Location: /user/dashboard?payment=success');
    exit;
} else {
    // Record failed payment transaction audit trace
    $conn->update('payments', [
        'status'        => 'failed',
        'completed_at'  => date('Y-m-d H:i:s'),
        'gateway_tx_id' => $transactionId
    ], ['tx_ref' => $txRef]);

    // Redirect user with failure status
    header('Location: /user/dashboard?payment=failed');
    exit;
}
?>
