<?php
/**
 * initialize_payment.php - FULLY CORRECTED & DEBUGGING VERSION
 */

declare(strict_types=1);
require_once __DIR__ . '/db.php';

secureSession();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

if (empty($_SESSION['email'])) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized access. Please log in.'], 401);
}

function paymentAppBaseUrl(): string
{
    $configured = getenv('APP_BASE_URL') ?: ($_ENV['APP_BASE_URL'] ?? '');
    $configured = trim((string)$configured);

    if ($configured !== '' && filter_var($configured, FILTER_VALIDATE_URL)) {
        return rtrim($configured, '/');
    }

    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    $host = preg_replace('/[^a-zA-Z0-9\.\-:]/', '', $host);

    if (empty($host) || $host === 'localhost' || $host === '127.0.0.1' || preg_match('/^\d{1,3}(\.\d{1,3}){3}$/', $host)) {
        throw new Exception('APP_BASE_URL is not set or is invalid. Please configure it in your environment.');
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    return ($https ? 'https' : 'http') . '://' . $host;
}

/** Load the Flutterwave secret key from settings or environment. */
function flutterwaveSecretKey($conn): string
{
    $conn->createTable('settings');
    $allSettings = $conn->select('settings') ?: [];
    $settings = $allSettings[0] ?? [];

    $secretKey = trim((string)($settings['flw_secret_key'] ?? ''));
    if ($secretKey === '') {
        $secretKey = trim((string)(getenv('FLW_SECRET_KEY') ?: ($_ENV['FLW_SECRET_KEY'] ?? (defined('FLW_SECRET_KEY') ? FLW_SECRET_KEY : ''))));
    }

    return $secretKey;
}

function flutterwaveLiveSecretReady(string $secretKey): bool
{
    $secretKey = trim($secretKey);
    if (strlen($secretKey) < 20) return false;

    // MUST be a Live Secret Key (FLWSECK-)
    if (strpos($secretKey, 'FLWSECK-') !== 0) return false;

    // MUST NOT be a test key
    if (stripos($secretKey, '_TEST') !== false || stripos($secretKey, 'sandbox') !== false) return false;

    return true;
}

function findActivePlan($conn, string $planId): ?array
{
    $conn->createTable('plans');
    $plan = $conn->selectOne('plans', ['id' => $planId]);
    if (!$plan && ctype_digit($planId)) {
        $plan = $conn->selectOne('plans', ['id' => (int)$planId]);
    }

    if (!$plan) {
        $allPlans = $conn->select('plans') ?: [];
        foreach ($allPlans as $candidate) {
            $candidateId = strtolower((string)($candidate['id'] ?? ''));
            $candidateName = strtolower((string)($candidate['name'] ?? ''));
            $candidateSlug = strtolower((string)($candidate['slug'] ?? ''));
            $needle = strtolower($planId);

            if ($candidateId === $needle || $candidateName === $needle || ($candidateSlug !== '' && $candidateSlug === $needle)) {
                $plan = $candidate;
                break;
            }
        }
    }

    if (!$plan || (int)($plan['is_active'] ?? 0) !== 1) return null;
    return $plan;
}

$planId = cleanInput((string)($_POST['plan_id'] ?? ''));
if ($planId === '') jsonResponse(['success' => false, 'message' => 'Please select a valid pricing plan.'], 400);

$plan = findActivePlan($conn, $planId);
if (!$plan) jsonResponse(['success' => false, 'message' => 'The selected pricing plan is inactive or invalid.'], 404);

$user = $conn->selectOne('users', ['email' => $_SESSION['email']]);
if (!$user) jsonResponse(['success' => false, 'message' => 'User record not found.'], 404);

// STRICT EMAIL VALIDATION
if (!filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['success' => false, 'message' => 'Invalid user email address in database.'], 400);
}

$secretKey = flutterwaveSecretKey($conn);
if (!flutterwaveLiveSecretReady($secretKey)) {
    jsonResponse([
        'success' => false,
        'message' => 'Payment gateway error: Invalid Live Secret Key. Ensure your database contains the LIVE SECRET KEY (starts with FLWSECK-).'
    ], 503);
}

// FORCE AMOUNT TO INTEGER (Flutterwave UI crashes on floats)
$rawPrice = str_replace([',', '₦', ' '], '', (string)($plan['price'] ?? '0'));
$amount = (int) round((float)$rawPrice);

$currency = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', (string)($plan['currency'] ?? 'NGN')), 0, 3));
if ($currency === '') $currency = 'NGN';

// Ensure amount meets minimum limits (NGN min is 100, USD min is 1)
if ($amount <= 0 || ($currency === 'NGN' && $amount < 100)) {
    jsonResponse(['success' => false, 'message' => 'Selected plan has an invalid price or is below the minimum transaction limit.'], 400);
}

$txRef = 'nxpay_' . (int)$user['id'] . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(8));

$conn->createTable('payments');
$conn->insert('payments', [
    'user_id' => (int)$user['id'], 'email' => (string)$user['email'],
    'plan_id' => $plan['id'] ?? $planId, 'plan_name' => (string)($plan['name'] ?? ''),
    'amount' => $amount, 'currency' => $currency, 'gateway' => 'flutterwave',
    'tx_ref' => $txRef, 'status' => 'pending', 'gateway_tx_id' => null,
    'gateway_response' => null, 'created_at' => date('Y-m-d H:i:s'), 'completed_at' => null
]);

try {
    $baseUrl = paymentAppBaseUrl();
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}

// Force HTTPS for live redirects
if (strpos($baseUrl, 'http://') === 0) $baseUrl = str_replace('http://', 'https://', $baseUrl);
$redirectUrl = rtrim($baseUrl, '/') . '/php/verify_payment.php';

// BARE MINIMUM PAYLOAD to prevent UI crashes
$payload = [
    'tx_ref'       => $txRef,
    'amount'       => $amount,
    'currency'     => $currency,
    'redirect_url' => $redirectUrl,
    'customer'     => [
        'email' => (string)$user['email']
    ]
];

$ch = curl_init('https://api.flutterwave.com/v3/payments');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $secretKey,
        'Content-Type: application/json'
    ],
    CURLOPT_CONNECTTIMEOUT => 15, CURLOPT_TIMEOUT => 45,
    CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2
]);

$response = curl_exec($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false || $curlError !== '') {
    $conn->update('payments', ['status' => 'failed', 'completed_at' => date('Y-m-d H:i:s'), 'gateway_response' => 'Connection error: ' . $curlError], ['tx_ref' => $txRef]);
    jsonResponse(['success' => false, 'message' => 'Flutterwave API connection error.'], 502);
}

$resData = json_decode((string)$response, true);
$checkoutLink = (string)($resData['data']['link'] ?? '');

if ($httpCode === 200 && ($resData['status'] ?? '') === 'success' && $checkoutLink !== '') {
    $conn->update('payments', ['checkout_link' => $checkoutLink, 'gateway_response' => (string)$response], ['tx_ref' => $txRef]);

    jsonResponse([
        'success' => true,
        'link'    => $checkoutLink,
        'message' => 'Redirecting to Flutterwave checkout.'
    ]);
}

$errorMessage = (string)($resData['message'] ?? 'Failed to initialize Flutterwave checkout.');
$conn->update('payments', ['status' => 'failed', 'completed_at' => date('Y-m-d H:i:s'), 'gateway_response' => (string)$response], ['tx_ref' => $txRef]);

jsonResponse(['success' => false, 'message' => 'Flutterwave gateway error: ' . $errorMessage], 400);