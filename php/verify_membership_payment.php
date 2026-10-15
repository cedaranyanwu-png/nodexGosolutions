<?php
/**
 * verify_membership_payment.php
 *
 * Verification script for customer membership payments.
 * Queries Flutterwave v3 API securely, verifies payment amount, and credits
 * the site owner's balance after deducting the configurable platform fee.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

secureSession();

$txRef         = cleanInput($_GET['tx_ref'] ?? $_GET['trxref'] ?? '');
$transactionId = cleanInput($_GET['transaction_id'] ?? '');

if (empty($txRef) && empty($transactionId)) {
    exit("Invalid payment verification request parameters.");
}

$conn->createTable('payment_transactions');
$conn->createTable('memberships');
$conn->createTable('user_earnings');
$conn->createTable('platform_revenue');
$conn->createTable('monetization_settings');
$conn->createTable('settings');

// Find transaction record by tx_ref
$txRecord = $conn->selectOne('payment_transactions', ['tx_ref' => $txRef]);
if (!$txRecord) {
    exit("Transaction reference not found.");
}

// Prevent duplicate processing
if (strtolower((string)($txRecord['status'] ?? '')) === 'successful') {
    header("Location: /user/dashboard#monetization");
    exit;
}

// Fetch Flutterwave secret key
$allSettings = $conn->select('settings') ?: [];
$flwSettings = $allSettings[0] ?? null;
$secretKey   = $flwSettings['flw_secret_key'] ?? '';

if (!empty($transactionId)) {
    $verifyUrl = "https://api.flutterwave.com/v3/transactions/" . urlencode((string)$transactionId) . "/verify";
    $ch = curl_init($verifyUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $secretKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $resp = curl_exec($ch);
    curl_close($ch);

    $verifyData = json_decode((string)$resp, true);

    if (isset($verifyData['status']) && $verifyData['status'] === 'success' && strtolower((string)($verifyData['data']['status'] ?? '')) === 'successful') {
        $amountPaid = (float)$verifyData['data']['amount'];
        $expectedAmount = (float)$txRecord['amount'];

        if ($amountPaid >= $expectedAmount) {
            // Mark transaction successful
            $conn->update('payment_transactions', [
                'status'         => 'successful',
                'flw_id'         => $transactionId,
                'updated_at'     => date('Y-m-d H:i:s')
            ], ['id' => $txRecord['id']]);

            // Calculate Platform Fee and User Net Earnings
            $monSettings = $conn->selectOne('monetization_settings', ['id' => 1]);
            $platformFeePercent = (float)($monSettings['membership_platform_fee_percent'] ?? 10.0);

            $platformCommission = ($expectedAmount * $platformFeePercent) / 100.0;
            $userNetEarning    = $expectedAmount - $platformCommission;

            $ownerUserId = (int)$txRecord['owner_user_id'];
            $websiteId   = (int)$txRecord['website_id'];
            $planId      = (int)$txRecord['plan_id'];

            // Register Active Membership
            $conn->insert('memberships', [
                'plan_id'        => $planId,
                'website_id'     => $websiteId,
                'owner_user_id'  => $ownerUserId,
                'customer_name'  => $txRecord['customer_name'],
                'customer_email' => $txRecord['customer_email'],
                'status'         => 'active',
                'amount_paid'    => $expectedAmount,
                'tx_ref'         => $txRef,
                'start_date'     => date('Y-m-d H:i:s'),
                'expiry_date'    => date('Y-m-d H:i:s', strtotime('+1 month')),
                'created_at'     => date('Y-m-d H:i:s')
            ]);

            // Record User Earnings
            $conn->insert('user_earnings', [
                'user_id'          => $ownerUserId,
                'website_id'       => $websiteId,
                'type'             => 'membership',
                'gross_amount'     => $expectedAmount,
                'platform_fee'     => $platformCommission,
                'net_user_earning' => $userNetEarning,
                'tx_ref'           => $txRef,
                'description'      => "Membership Purchase: " . $txRecord['customer_email'],
                'created_at'       => date('Y-m-d H:i:s')
            ]);

            // Record Platform Revenue
            $conn->insert('platform_revenue', [
                'type'             => 'membership_fee',
                'gross_amount'     => $expectedAmount,
                'net_platform_fee' => $platformCommission,
                'tx_ref'           => $txRef,
                'created_at'       => date('Y-m-d H:i:s')
            ]);

            ?>
            <!DOCTYPE html>
            <html>
            <head><title>Payment Successful</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"></head>
            <body class="bg-light d-flex align-items-center justify-content-center min-vh-100">
                <div class="card p-5 text-center shadow border-0 rounded-4" style="max-width: 480px;">
                    <div class="text-success mb-3 fs-1"><i class="fa-solid fa-circle-check"></i></div>
                    <h3 class="fw-bold text-dark">Payment Successful!</h3>
                    <p class="text-muted text-sm">Your membership subscription has been activated successfully.</p>
                    <a href="/" class="btn btn-primary rounded-pill px-4 font-bold">Return Home</a>
                </div>
            </body>
            </html>
            <?php
            exit;
        }
    }
}

exit("Payment verification failed or pending.");
?>