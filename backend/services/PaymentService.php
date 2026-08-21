<?php
/**
 * backend/services/PaymentService.php
 *
 * Central Payment & Upgrade Gateway Service
 * Handles Flutterwave initialization, server-to-server transaction verification,
 * payment logging, and automated subscription activation.
 */

declare(strict_types=1);

require_once __DIR__ . '/../database/db.php';

class PaymentService {
    private Database $db;

    public function __construct(?Database $db = null) {
        global $conn;
        $this->db = $db ?? $conn;
        $this->db->createTable('payments');
        $this->db->createTable('plans');
        $this->db->createTable('settings');
    }

    public function initializePayment(array $user, string $planId): array {
        $userEmail = strtolower((string)($user['email'] ?? ''));
        if (empty($userEmail)) {
            return ['success' => false, 'message' => 'Valid user email required.'];
        }

        $plan = $this->findPlan($planId);
        if (!$plan || (int)($plan['is_active'] ?? 0) !== 1) {
            return ['success' => false, 'message' => 'Selected pricing plan is inactive or invalid.'];
        }

        $secretKey = $this->getFlutterwaveSecretKey();
        if (!$this->isLiveFlutterwaveSecret($secretKey)) {
            return ['success' => false, 'message' => 'Payment gateway is not configured with a valid live Flutterwave secret key.'];
        }

        $rawPrice = str_replace([',', '₦', ' '], '', (string)($plan['price'] ?? '0'));
        $amount = (int)round((float)$rawPrice);
        $currency = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', (string)($plan['currency'] ?? 'NGN')), 0, 3));
        if (empty($currency)) $currency = 'NGN';

        if ($amount <= 0 || ($currency === 'NGN' && $amount < 100)) {
            return ['success' => false, 'message' => 'Invalid plan amount. Minimum amount for NGN is ₦100.'];
        }

        $txRef = 'nxpay_' . (int)$user['id'] . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(6));

        $this->db->insert('payments', [
            'user_id' => (int)$user['id'],
            'email' => $userEmail,
            'plan_id' => $plan['id'] ?? $planId,
            'plan_name' => (string)($plan['name'] ?? ''),
            'amount' => $amount,
            'currency' => $currency,
            'gateway' => 'flutterwave',
            'tx_ref' => $txRef,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        try {
            $baseUrl = $this->getAppBaseUrl();
        } catch (RuntimeException $e) {
            $this->db->update('payments', ['status' => 'failed', 'gateway_response' => $e->getMessage()], ['tx_ref' => $txRef]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
        $redirectUrl = $baseUrl . '/php/verify_payment.php';

        $payload = [
            'tx_ref' => $txRef,
            'amount' => $amount,
            'currency' => $currency,
            'redirect_url' => $redirectUrl,
            'customer' => ['email' => $userEmail]
        ];

        $ch = curl_init('https://api.flutterwave.com/v3/payments');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
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
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$response) {
            $this->db->update('payments', ['status' => 'failed'], ['tx_ref' => $txRef]);
            return ['success' => false, 'message' => 'Failed to connect to Flutterwave payment gateway.'];
        }

        $resData = json_decode((string)$response, true);
        $checkoutLink = (string)($resData['data']['link'] ?? '');

        if ($httpCode === 200 && ($resData['status'] ?? '') === 'success' && !empty($checkoutLink)) {
            $this->db->update('payments', [
                'checkout_link' => $checkoutLink,
                'gateway_response' => (string)$response
            ], ['tx_ref' => $txRef]);

            return [
                'success' => true,
                'link' => $checkoutLink,
                'tx_ref' => $txRef,
                'message' => 'Redirecting to checkout.'
            ];
        }

        $errMsg = (string)($resData['message'] ?? 'Payment initialization failed.');
        $this->db->update('payments', ['status' => 'failed'], ['tx_ref' => $txRef]);
        return ['success' => false, 'message' => 'Gateway error: ' . $errMsg];
    }

    public function verifyPayment(string $txRef, ?string $transactionId = null): array {
        $payment = $this->db->selectOne('payments', ['tx_ref' => $txRef]);
        if (!$payment) {
            return ['success' => false, 'message' => 'Payment transaction record not found.'];
        }

        if (($payment['status'] ?? '') === 'successful') {
            return ['success' => true, 'message' => 'Payment already verified successfully.', 'payment' => $payment];
        }

        $secretKey = $this->getFlutterwaveSecretKey();

        // Server-to-server verification call if transaction ID exists
        if (!empty($transactionId) && !empty($secretKey)) {
            $ch = curl_init("https://api.flutterwave.com/v3/transactions/{$transactionId}/verify");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ["Authorization: Bearer {$secretKey}"],
                CURLOPT_TIMEOUT => 30
            ]);
            $res = curl_exec($ch);
            curl_close($ch);

            if ($res) {
                $verData = json_decode((string)$res, true);
                if (($verData['status'] ?? '') === 'success' && ($verData['data']['status'] ?? '') === 'successful') {
                    return $this->activateSubscriptionForPayment($payment, $verData['data']['id'] ?? $transactionId, $res);
                }
            }
        }

        // Never activate a subscription locally. A real gateway confirmation is mandatory.
        return ['success' => false, 'message' => 'Flutterwave did not confirm this transaction.'];
    }

    public function getUserPaymentHistory(int $userId): array {
        return $this->db->select('payments', ['user_id' => $userId]) ?: [];
    }

    private function activateSubscriptionForPayment(array $payment, string $gatewayTxId, string $gatewayResponse): array {
        $txRef = $payment['tx_ref'];
        $now = date('Y-m-d H:i:s');
        $subEnd = date('Y-m-d H:i:s', strtotime('+1 month'));

        // Update payment table status
        $this->db->update('payments', [
            'status' => 'successful',
            'gateway_tx_id' => (string)$gatewayTxId,
            'gateway_response' => (string)$gatewayResponse,
            'completed_at' => $now
        ], ['tx_ref' => $txRef]);

        // Update user subscription state
        $user = $this->db->selectOne('users', ['id' => $payment['user_id']]);
        if ($user) {
            $planName = $payment['plan_name'] ?: 'Growth';
            $this->db->update('users', [
                'subscription_status' => 'active',
                'subscription_plan' => $planName,
                'subscription_start' => $now,
                'subscription_end' => $subEnd,
                'updated_at' => $now
            ], ['id' => $user['id']]);
        }

        return ['success' => true, 'message' => 'Subscription activated successfully.', 'payment' => $payment];
    }

    private function findPlan(string $planId): ?array {
        $plan = $this->db->selectOne('plans', ['id' => $planId]);
        if (!$plan && ctype_digit($planId)) {
            $plan = $this->db->selectOne('plans', ['id' => (int)$planId]);
        }
        if (!$plan) {
            $all = $this->db->select('plans') ?: [];
            foreach ($all as $item) {
                if (strtolower((string)($item['id'] ?? '')) === strtolower($planId) ||
                    strtolower((string)($item['name'] ?? '')) === strtolower($planId)) {
                    $plan = $item;
                    break;
                }
            }
        }
        return $plan;
    }

    private function getFlutterwaveSecretKey(): string {
        $all = $this->db->select('settings') ?: [];
        $settings = $all[0] ?? [];
        $stored = trim((string)($settings['flw_secret_key'] ?? ''));
        if ($stored !== '' && !str_contains($stored, 'TEST-sandbox')) return $stored;
        return trim((string)(getenv('FLW_SECRET_KEY') ?: ($_ENV['FLW_SECRET_KEY'] ?? (defined('FLW_SECRET_KEY') ? FLW_SECRET_KEY : ''))));
    }

    private function isLiveFlutterwaveSecret(string $secretKey): bool {
        return str_starts_with($secretKey, 'FLWSECK-')
            && !str_contains(strtoupper($secretKey), 'TEST')
            && !str_contains(strtolower($secretKey), 'sandbox')
            && strlen($secretKey) >= 20;
    }

    private function getAppBaseUrl(): string {
        $configured = trim((string)(getenv('APP_BASE_URL') ?: ($_ENV['APP_BASE_URL'] ?? '')));
        if ($configured !== '' && filter_var($configured, FILTER_VALIDATE_URL)) return rtrim($configured, '/');
        $host = preg_replace('/[^a-zA-Z0-9.\\-:]/', '', (string)($_SERVER['HTTP_HOST'] ?? ''));
        if ($host === '' || $host === 'localhost' || str_starts_with($host, '127.0.0.1')) {
            throw new RuntimeException('APP_BASE_URL must be configured for Flutterwave callbacks.');
        }
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https' : 'https';
        return $scheme . '://' . $host;
    }
}
