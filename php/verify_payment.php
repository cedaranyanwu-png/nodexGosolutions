<?php
/**
 * verify_payment.php
 *
 * Production-safe Flutterwave payment verifier.
 * - Never trusts browser return parameters such as "status=successful".
 * - Requires a Flutterwave transaction ID and verifies it server-to-server.
 * - Checks transaction ID, tx_ref, amount, currency, and customer email.
 * - Uses a local lock and idempotency checks to prevent duplicate activation.
 * - Supports signed Flutterwave webhooks when flw_webhook_hash / FLW_WEBHOOK_HASH is configured.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

secureSession();

function verificationRedirect(string $code): void
{
    header('Location: /user/dashboard?payment=' . rawurlencode($code));
    exit;
}

function webhookResponse(bool $success, string $message, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

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

function flutterwaveWebhookHash($conn): string
{
    $conn->createTable('settings');
    $allSettings = $conn->select('settings') ?: [];
    $settings = $allSettings[0] ?? [];

    $hash = trim((string)($settings['flw_webhook_hash'] ?? ''));
    if ($hash === '') {
        $hash = trim((string)(getenv('FLW_WEBHOOK_HASH') ?: ($_ENV['FLW_WEBHOOK_HASH'] ?? (defined('FLW_WEBHOOK_HASH') ? FLW_WEBHOOK_HASH : ''))));
    }

    return $hash;
}

function flutterwaveLiveSecretReady(string $secretKey): bool
{
    $secretKey = trim($secretKey);

    if ($secretKey === '' || strlen($secretKey) < 20) {
        return false;
    }

    // Block test/sandbox keys completely so test transactions cannot unlock premium.
    if (stripos($secretKey, 'test') !== false || stripos($secretKey, 'sandbox') !== false) {
        return false;
    }

    return true;
}

function cleanCurrencyCode($value): string
{
    $currency = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', (string)$value), 0, 3));
    return $currency !== '' ? $currency : 'NGN';
}

function moneyValue($value): float
{
    return round((float)str_replace([',', '₦', ' '], '', (string)$value), 2);
}

function findPlanById($conn, $planId): ?array
{
    if ($planId === null || $planId === '') {
        return null;
    }

    $conn->createTable('plans');
    $plan = $conn->selectOne('plans', ['id' => $planId]);

    if (!$plan && is_numeric($planId)) {
        $plan = $conn->selectOne('plans', ['id' => (int)$planId]);
    }

    return $plan ?: null;
}

function planDurationMonths(?array $plan): int
{
    if (!$plan) {
        return 1;
    }

    foreach (['duration_months', 'months', 'interval_months'] as $key) {
        if (isset($plan[$key]) && is_numeric($plan[$key]) && (int)$plan[$key] > 0) {
            return min(12, (int)$plan[$key]);
        }
    }

    $period = strtolower((string)($plan['billing_period'] ?? ''));
    if (str_contains($period, 'year') || str_contains($period, 'annual')) {
        return 12;
    }

    return 1;
}

$isWebhook = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST');
$rawWebhookBody = $isWebhook ? (string)file_get_contents('php://input') : '';
$webhookPayload = $isWebhook ? json_decode($rawWebhookBody, true) : null;

$secretKey = flutterwaveSecretKey($conn);
if (!flutterwaveLiveSecretReady($secretKey)) {
    if ($isWebhook) {
        webhookResponse(false, 'Live Flutterwave secret key is not configured.', 503);
    }
    verificationRedirect('system_configuration_error');
}

if ($isWebhook) {
    $expectedHash = flutterwaveWebhookHash($conn);
    $receivedHash = (string)($_SERVER['HTTP_VERIF_HASH'] ?? '');

    if ($expectedHash === '' || $receivedHash === '' || !hash_equals($expectedHash, $receivedHash)) {
        webhookResponse(false, 'Invalid webhook signature.', 401);
    }

    $webhookData = is_array($webhookPayload) ? ($webhookPayload['data'] ?? []) : [];
    $status = cleanInput((string)($webhookData['status'] ?? ''));
    $txRef = cleanInput((string)($webhookData['tx_ref'] ?? ''));
    $transactionId = cleanInput((string)($webhookData['id'] ?? ''));
} else {
    $status = cleanInput((string)($_GET['status'] ?? ''));
    $txRef = cleanInput((string)($_GET['tx_ref'] ?? ''));
    $transactionId = cleanInput((string)($_GET['transaction_id'] ?? ($_GET['id'] ?? '')));
}

if ($txRef === '') {
    if ($isWebhook) {
        webhookResponse(false, 'Missing transaction reference.', 400);
    }
    verificationRedirect('invalid_reference');
}

$conn->createTable('payments');
$payment = $conn->selectOne('payments', ['tx_ref' => $txRef]);
if (!$payment) {
    if ($isWebhook) {
        webhookResponse(false, 'Payment record not found.', 404);
    }
    verificationRedirect('not_found');
}

if (strtolower((string)($payment['status'] ?? '')) === 'successful') {
    if ($isWebhook) {
        webhookResponse(true, 'Payment already processed.');
    }
    verificationRedirect('success');
}

$userId = (int)($payment['user_id'] ?? 0);
$user = $conn->selectOne('users', ['id' => $userId]);
if (!$user) {
    if ($isWebhook) {
        webhookResponse(false, 'Payment user not found.', 404);
    }
    verificationRedirect('user_mismatch');
}

if ($transactionId === '') {
    if (in_array(strtolower($status), ['failed', 'cancelled', 'canceled'], true)) {
        $conn->update('payments', [
            'status' => 'failed',
            'completed_at' => date('Y-m-d H:i:s'),
            'gateway_response' => 'Flutterwave returned a failed/cancelled status without a transaction ID.'
        ], ['tx_ref' => $txRef]);

        if ($isWebhook) {
            webhookResponse(true, 'Failed payment recorded.');
        }
        verificationRedirect('failed');
    }

    if ($isWebhook) {
        webhookResponse(false, 'Missing transaction ID.', 400);
    }
    verificationRedirect('invalid_transaction');
}

$lockName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $txRef);
$lockPath = sys_get_temp_dir() . '/nodex_flw_verify_' . $lockName . '.lock';
$lockHandle = fopen($lockPath, 'c');
$locked = $lockHandle !== false && flock($lockHandle, LOCK_EX);

$outcome = [
    'ok' => false,
    'code' => 'failed',
    'message' => 'Payment verification failed.',
    'http' => 200
];

try {
    // Re-read inside the lock so two simultaneous callbacks cannot activate twice.
    $payment = $conn->selectOne('payments', ['tx_ref' => $txRef]);
    if (!$payment) {
        $outcome = ['ok' => false, 'code' => 'not_found', 'message' => 'Payment record not found.', 'http' => 404];
    } elseif (strtolower((string)($payment['status'] ?? '')) === 'successful') {
        $outcome = ['ok' => true, 'code' => 'success', 'message' => 'Payment already processed.', 'http' => 200];
    } else {
        $verifyUrl = 'https://api.flutterwave.com/v3/transactions/' . urlencode($transactionId) . '/verify';

        $ch = curl_init($verifyUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $secretKey,
                'Content-Type: application/json'
            ],
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $curlError !== '') {
            $outcome = ['ok' => false, 'code' => 'gateway_unreachable', 'message' => 'Unable to reach Flutterwave for verification.', 'http' => 502];
        } else {
            $resData = json_decode((string)$response, true);
            $verified = false;
            $failureReason = 'Flutterwave did not confirm a successful payment.';
            $txData = [];

            if ($httpCode === 200 && is_array($resData) && ($resData['status'] ?? '') === 'success' && isset($resData['data']) && is_array($resData['data'])) {
                $txData = $resData['data'];

                $apiStatus = strtolower((string)($txData['status'] ?? ''));
                $apiTxRef = (string)($txData['tx_ref'] ?? '');
                $apiId = (string)($txData['id'] ?? '');
                $apiAmount = moneyValue($txData['amount'] ?? 0);
                $apiChargedAmount = isset($txData['charged_amount']) ? moneyValue($txData['charged_amount']) : $apiAmount;
                $apiCurrency = cleanCurrencyCode($txData['currency'] ?? '');
                $apiEmail = strtolower((string)($txData['customer']['email'] ?? ''));

                $expectedAmount = moneyValue($payment['amount'] ?? 0);
                $expectedCurrency = cleanCurrencyCode($payment['currency'] ?? 'NGN');

                // The pending payment record is the trusted amount/currency snapshot created
                // by initialize_payment.php. The plan record is loaded only for duration.
                $plan = findPlanById($conn, $payment['plan_id'] ?? null);

                if ($apiStatus !== 'successful') {
                    $failureReason = 'Flutterwave transaction is not successful.';
                } elseif ($apiTxRef !== $txRef) {
                    $failureReason = 'Transaction reference mismatch.';
                } elseif ($apiId !== '' && $apiId !== $transactionId) {
                    $failureReason = 'Transaction ID mismatch.';
                } elseif (abs($apiAmount - $expectedAmount) >= 0.01) {
                    $failureReason = 'Paid amount does not match the expected plan amount.';
                } elseif ($apiChargedAmount + 0.01 < $apiAmount) {
                    $failureReason = 'Charged amount is lower than the verified amount.';
                } elseif ($apiCurrency !== $expectedCurrency) {
                    $failureReason = 'Paid currency does not match the expected currency.';
                } elseif (!empty($payment['email']) && $apiEmail !== '' && strtolower((string)$payment['email']) !== $apiEmail) {
                    $failureReason = 'Payer email does not match the payment owner.';
                } else {
                    $verified = true;
                }
            } elseif ($httpCode >= 500) {
                $outcome = ['ok' => false, 'code' => 'gateway_unreachable', 'message' => 'Flutterwave verification is temporarily unavailable.', 'http' => 502];
            }

            if ($outcome['code'] !== 'gateway_unreachable') {
                if ($verified) {
                    $conn->update('payments', [
                        'status' => 'successful',
                        'completed_at' => date('Y-m-d H:i:s'),
                        'gateway_tx_id' => $transactionId,
                        'gateway_response' => (string)$response
                    ], ['tx_ref' => $txRef]);

                    $months = planDurationMonths($plan ?? null);
                    $subStart = date('Y-m-d H:i:s');
                    $currentEndTimestamp = !empty($user['subscription_end']) ? strtotime((string)$user['subscription_end']) : false;

                    if ($currentEndTimestamp && $currentEndTimestamp > time() && strtolower((string)($user['subscription_plan'] ?? '')) === strtolower((string)$payment['plan_name'])) {
                        $subEnd = date('Y-m-d H:i:s', strtotime('+' . $months . ' month', $currentEndTimestamp));
                    } else {
                        $subEnd = date('Y-m-d H:i:s', strtotime('+' . $months . ' month'));
                    }

                    $conn->update('users', [
                        'subscription_status' => 'active',
                        'subscription_plan' => (string)$payment['plan_name'],
                        'subscription_start' => $subStart,
                        'subscription_end' => $subEnd,
                        'updated_at' => date('Y-m-d H:i:s')
                    ], ['id' => $userId]);

                    $conn->createTable('activity_logs');
                    $conn->insert('activity_logs', [
                        'user_id' => $userId,
                        'email' => (string)($user['email'] ?? ''),
                        'action' => 'subscription_activated',
                        'details' => "Premium plan '{$payment['plan_name']}' activated after verified Flutterwave transaction {$transactionId} ({$txRef}).",
                        'created_at' => date('Y-m-d H:i:s')
                    ]);

                    $outcome = ['ok' => true, 'code' => 'success', 'message' => 'Payment verified and subscription activated.', 'http' => 200];
                } else {
                    $conn->update('payments', [
                        'status' => 'failed',
                        'completed_at' => date('Y-m-d H:i:s'),
                        'gateway_tx_id' => $transactionId,
                        'gateway_response' => (string)$response
                    ], ['tx_ref' => $txRef]);

                    $outcome = ['ok' => false, 'code' => 'failed', 'message' => $failureReason, 'http' => 200];
                }
            }
        }
    }
} finally {
    if ($locked) {
        flock($lockHandle, LOCK_UN);
    }
    if ($lockHandle !== false) {
        fclose($lockHandle);
    }
}

if ($isWebhook) {
    webhookResponse((bool)$outcome['ok'], (string)$outcome['message'], (int)$outcome['http']);
}

verificationRedirect((string)$outcome['code']);
