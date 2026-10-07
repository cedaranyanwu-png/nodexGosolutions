<?php
declare(strict_types=1);

require_once __DIR__ . '/php/db.php';

echo "========================================================\n";
echo "      FLUTTERWAVE PAYMENT GATEWAY E2E TEST SUITE        \n";
echo "========================================================\n\n";

$testsPassed = 0;
$testsFailed = 0;

function assertE2e(string $name, bool $expression): void {
    global $testsPassed, $testsFailed;
    if ($expression) {
        echo "✅ PASS: {$name}\n";
        $testsPassed++;
    } else {
        echo "❌ FAIL: {$name}\n";
        $testsFailed++;
    }
}

// Ensure test user exists in DB
$testEmail = 'e2e_tenant@nodex.com';
$user = $conn->selectOne('users', ['email' => $testEmail]);
if (!$user) {
    $user = $conn->insert('users', [
        'fullname' => 'Test Flutterwave Tenant',
        'email' => $testEmail,
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'role' => 'tenant',
        'status' => 'active',
        'is_verified' => 1,
        'subscription_status' => 'expired',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
} else {
    $conn->update('users', ['is_verified' => 1, 'subscription_status' => 'expired'], ['id' => $user['id']]);
}

$cookieFile = __DIR__ . '/test_cookie.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

// Helper function for cURL requests with cookies
function httpReq(string $url, string $method = 'GET', array $postData = [], string $cookieFile = ''): array {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    }
    if (!empty($cookieFile)) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);

    $header = substr((string)$response, 0, $headerSize);
    $body = substr((string)$response, $headerSize);

    return [
        'code' => $httpCode,
        'header' => $header,
        'body' => $body,
        'redirect' => $redirectUrl
    ];
}

// Step 1: Login
$loginRes = httpReq('http://localhost:8000/php/login.php', 'POST', [
    'email' => $testEmail,
    'password' => 'password123'
], $cookieFile);

$loginData = json_decode($loginRes['body'], true);
assertE2e("User authentication successful", isset($loginData['success']) && $loginData['success'] === true);

// Step 2: Initialize Payment Request
$initRes = httpReq('http://localhost:8000/php/initialize_payment.php', 'POST', ['plan_id' => 'micro'], $cookieFile);
assertE2e("Initialize Payment API returns HTTP 200", $initRes['code'] === 200);

$initData = json_decode($initRes['body'], true);
assertE2e("Initialize Payment response success is true", isset($initData['success']) && $initData['success'] === true);
$checkoutUrl = $initData['link'] ?? '';
assertE2e("Initialize Payment returns Flutterwave live or local checkout URL", !empty($checkoutUrl) && (str_contains($checkoutUrl, 'checkout.flutterwave.com') || str_contains($checkoutUrl, 'flutterwave_checkout.php')));

// Verify pending payment record in DB
$conn->createTable('payments');
$payments = $conn->select('payments', ['user_id' => $user['id']]) ?: [];
$latestPayment = end($payments);
assertE2e("Pending payment record created in payments table", $latestPayment !== false && $latestPayment['status'] === 'pending');

$txRef = $latestPayment['tx_ref'] ?? '';
assertE2e("Pending payment transaction reference generated", !empty($txRef));

// Step 3: Verify payment status verification flow via verify_payment.php
$verifyUrl = "http://localhost:8000/php/verify_payment.php?status=successful&tx_ref=" . urlencode($txRef) . "&transaction_id=flw_tr_test_12345";
$verifyRes = httpReq($verifyUrl, 'GET', [], $cookieFile);

$redirectTarget = (string)($verifyRes['redirect'] ?? '');
if (empty($redirectTarget)) {
    if (preg_match('/Location:\s*([^\r\n]+)/i', $verifyRes['header'], $m)) {
        $redirectTarget = trim($m[1]);
    }
}

assertE2e("Payment verification redirects user back to dashboard with payment=success", str_contains($redirectTarget, 'payment=success'));

// Step 4: Verify Database State
$updatedUser = $conn->selectOne('users', ['email' => $testEmail]);
assertE2e("User subscription status is updated to 'active'", ($updatedUser['subscription_status'] ?? '') === 'active');
assertE2e("User subscription plan is updated to 'Micro'", ($updatedUser['subscription_plan'] ?? '') === 'Micro');

$paymentRecord = $conn->selectOne('payments', ['tx_ref' => $txRef]);
assertE2e("Payment record status is updated to 'successful'", ($paymentRecord['status'] ?? '') === 'successful');

if (file_exists($cookieFile)) unlink($cookieFile);

echo "\n========================================================\n";
echo "E2E TEST RESULTS SUMMARY:\n";
echo "Total Passed: {$testsPassed}\n";
echo "Total Failed: {$testsFailed}\n";
echo "========================================================\n";

if ($testsFailed > 0) {
    exit(1);
} else {
    exit(0);
}
