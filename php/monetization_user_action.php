<?php
/**
 * monetization_user_action.php
 *
 * Backend action controller for Tenant User Monetization controls.
 * Allows website owners to create paid membership plans, toggle ad unit placements
 * on their websites, review earnings analytics, and submit payout withdrawal requests.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

secureSession();

// Enforce login
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized: Please log in.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$userId = (int)$_SESSION['user_id'];
$action = cleanInput($_POST['action'] ?? '');

switch ($action) {

    // Create a new Membership Plan for a user's website
    case 'create_membership_plan':
        $conn->createTable('membership_plans');
        $conn->createTable('websites');

        $websiteId = (int)($_POST['website_id'] ?? 0);
        $title     = cleanInput($_POST['title'] ?? '');
        $price     = (float)($_POST['price'] ?? 0.0);
        $period    = strtolower(cleanInput($_POST['billing_period'] ?? 'monthly')); // monthly, yearly, one_time
        $desc      = cleanInput($_POST['description'] ?? '');

        if ($websiteId <= 0 || empty($title) || $price <= 0) {
            jsonResponse(['success' => false, 'message' => 'Valid website, title, and price (> 0) are required.'], 400);
        }

        // Verify website ownership
        $web = $conn->selectOne('websites', ['id' => $websiteId, 'user_id' => $userId]);
        if (!$web && !isStaff()) {
            jsonResponse(['success' => false, 'message' => 'Access Denied: Website workspace ownership mismatch.'], 403);
        }

        $planId = $conn->insert('membership_plans', [
            'user_id'        => $userId,
            'website_id'     => $websiteId,
            'title'          => $title,
            'price'          => $price,
            'billing_period' => $period,
            'description'    => $desc,
            'is_active'      => 1,
            'created_at'     => date('Y-m-d H:i:s')
        ]);

        jsonResponse([
            'success'   => true,
            'message'   => 'Membership plan created successfully!',
            'plan_id'   => $planId,
            'checkout_link' => "/pay/membership?plan_id=" . $planId
        ]);
        break;

    // Toggle active state or delete user membership plan
    case 'toggle_membership_plan':
        $conn->createTable('membership_plans');
        $planId = (int)($_POST['plan_id'] ?? 0);
        $plan   = $conn->selectOne('membership_plans', ['id' => $planId, 'user_id' => $userId]);

        if (!$plan && !isStaff()) {
            jsonResponse(['success' => false, 'message' => 'Plan not found.'], 404);
        }

        $newStatus = ((int)($plan['is_active'] ?? 0) === 1) ? 0 : 1;
        $conn->update('membership_plans', ['is_active' => $newStatus], ['id' => $planId]);

        jsonResponse(['success' => true, 'message' => 'Membership plan status updated.']);
        break;

    // Toggle site-wide Adsterra Ad placements
    case 'save_user_ad_settings':
        $conn->createTable('user_ad_settings');
        $conn->createTable('websites');

        $websiteId  = (int)($_POST['website_id'] ?? 0);
        $enabled    = (int)($_POST['is_enabled'] ?? 1);
        $adUnitIds  = $_POST['ad_unit_ids'] ?? []; // Array of selected ad unit IDs

        if ($websiteId <= 0) {
            jsonResponse(['success' => false, 'message' => 'Website selection required.'], 400);
        }

        $web = $conn->selectOne('websites', ['id' => $websiteId, 'user_id' => $userId]);
        if (!$web && !isStaff()) {
            jsonResponse(['success' => false, 'message' => 'Access Denied: Website workspace ownership mismatch.'], 403);
        }

        $unitIdsJson = is_array($adUnitIds) ? json_encode(array_map('intval', $adUnitIds)) : json_encode([]);

        $existing = $conn->selectOne('user_ad_settings', ['user_id' => $userId, 'website_id' => $websiteId]);
        if ($existing) {
            $conn->update('user_ad_settings', [
                'is_enabled'  => $enabled ? 1 : 0,
                'ad_unit_ids' => $unitIdsJson,
                'updated_at'  => date('Y-m-d H:i:s')
            ], ['id' => $existing['id']]);
        } else {
            $conn->insert('user_ad_settings', [
                'user_id'     => $userId,
                'website_id'  => $websiteId,
                'is_enabled'  => $enabled ? 1 : 0,
                'ad_unit_ids' => $unitIdsJson,
                'created_at'  => date('Y-m-d H:i:s')
            ]);
        }

        jsonResponse(['success' => true, 'message' => 'Website Ad Monetization preferences saved!']);
        break;

    // Submit Payout Request
    case 'request_payout':
        $conn->createTable('payouts');
        $conn->createTable('user_earnings');
        $conn->createTable('monetization_settings');

        $monSettings = $conn->selectOne('monetization_settings', ['id' => 1]);
        $minThreshold = (float)($monSettings['payout_min_threshold'] ?? 5000.0);

        // Calculate current net available user earnings balance
        $allEarnings = $conn->select('user_earnings', ['user_id' => $userId]) ?: [];
        $netBalance = 0.0;
        foreach ($allEarnings as $e) {
            $netBalance += (float)($e['net_user_earning'] ?? 0.0);
        }

        $requestedAmount = (float)($_POST['amount'] ?? 0.0);
        $bankName        = cleanInput($_POST['bank_name'] ?? '');
        $accountNumber    = cleanInput($_POST['account_number'] ?? '');
        $accountName      = cleanInput($_POST['account_name'] ?? '');

        if ($requestedAmount < $minThreshold) {
            jsonResponse(['success' => false, 'message' => "Minimum payout threshold is ₦" . number_format($minThreshold) . "."], 400);
        }

        if ($requestedAmount > $netBalance) {
            jsonResponse(['success' => false, 'message' => "Insufficient available balance. Your balance is ₦" . number_format($netBalance) . "."], 400);
        }

        if (empty($bankName) || empty($accountNumber) || empty($accountName)) {
            jsonResponse(['success' => false, 'message' => 'Complete bank account payout details are required.'], 400);
        }

        // Create pending payout request
        $payoutId = $conn->insert('payouts', [
            'user_id'        => $userId,
            'amount'         => $requestedAmount,
            'status'         => 'pending',
            'bank_name'      => $bankName,
            'account_number' => $accountNumber,
            'account_name'   => $accountName,
            'created_at'     => date('Y-m-d H:i:s')
        ]);

        jsonResponse(['success' => true, 'message' => 'Payout withdrawal request submitted successfully!', 'payout_id' => $payoutId]);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Invalid user monetization action.'], 400);
        break;
}
?>