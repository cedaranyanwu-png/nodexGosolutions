<?php
/**
 * monetization_admin_action.php
 *
 * Backend action controller for Monetization System administration.
 * Handles Adsterra ad configuration, ad unit management, revenue share settings,
 * user ad settings overrides, membership audits, and payout request approvals.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

secureSession();

// Access Control: Must be staff and hold settings/financial privileges
if (!isStaff()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized: Staff privileges required.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$action = cleanInput($_POST['action'] ?? '');
$conn->createTable('monetization_settings');
$monSettings = $conn->selectOne('monetization_settings', ['id' => 1]);

switch ($action) {

    // Save global monetization configuration parameters (Adsterra & Revenue share rules)
    case 'save_settings':
        if (!checkAdminPermission('settings.edit')) {
            jsonResponse(['success' => false, 'message' => 'Access Denied: Settings edit capabilities required.'], 403);
        }

        $adShare   = (float)($_POST['ad_revenue_share_percent'] ?? 70.0);
        $memFee    = (float)($_POST['membership_platform_fee_percent'] ?? 10.0);
        $pubId     = cleanInput($_POST['adsterra_publisher_id'] ?? '');
        $apiKey    = cleanInput($_POST['adsterra_api_key'] ?? '');
        $minPayout = (float)($_POST['payout_min_threshold'] ?? 5000.0);
        $adActive  = (int)($_POST['is_ad_monetization_enabled'] ?? 1);
        $memActive = (int)($_POST['is_membership_monetization_enabled'] ?? 1);

        $payload = [
            'ad_revenue_share_percent'          => max(0.0, min(100.0, $adShare)),
            'membership_platform_fee_percent'   => max(0.0, min(100.0, $memFee)),
            'adsterra_publisher_id'             => $pubId,
            'adsterra_api_key'                  => $apiKey,
            'payout_min_threshold'              => max(100.0, $minPayout),
            'is_ad_monetization_enabled'        => $adActive ? 1 : 0,
            'is_membership_monetization_enabled' => $memActive ? 1 : 0,
            'updated_at'                        => date('Y-m-d H:i:s')
        ];

        if ($monSettings) {
            $conn->update('monetization_settings', $payload, ['id' => 1]);
        } else {
            $payload['id'] = 1;
            $payload['created_at'] = date('Y-m-d H:i:s');
            $conn->insert('monetization_settings', $payload);
        }

        $conn->insert('activity_logs', [
            'user_id'    => $_SESSION['user_id'] ?? 0,
            'email'      => $_SESSION['email'] ?? 'admin',
            'action'     => 'monetization_settings_updated',
            'details'    => "Updated monetization settings: Ad Share {$adShare}%, Membership Fee {$memFee}%",
            'created_at' => date('Y-m-d H:i:s')
        ]);

        jsonResponse(['success' => true, 'message' => 'Monetization settings saved successfully!']);
        break;

    // Create a new platform Adsterra Ad Unit placement
    case 'create_ad_unit':
        $conn->createTable('ad_units');
        $title     = cleanInput($_POST['title'] ?? '');
        $format    = strtolower(cleanInput($_POST['format'] ?? 'banner')); // banner, native, popunder, socialbar
        $code      = $_POST['ad_code'] ?? '';
        $placement = cleanInput($_POST['placement'] ?? 'header'); // header, footer, sidebar, inline
        $isActive  = (int)($_POST['is_active'] ?? 1);

        if (empty($title) || empty($code)) {
            jsonResponse(['success' => false, 'message' => 'Ad title and script/code payload are required.'], 400);
        }

        $newId = $conn->insert('ad_units', [
            'title'      => $title,
            'format'     => $format,
            'ad_code'    => $code,
            'placement'  => $placement,
            'is_active'  => $isActive ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        jsonResponse(['success' => true, 'message' => 'New Adsterra Ad Unit created successfully!', 'unit_id' => $newId]);
        break;

    // Delete or toggle status of an Adsterra Ad Unit
    case 'toggle_ad_unit':
        $conn->createTable('ad_units');
        $unitId = (int)($_POST['unit_id'] ?? 0);
        $unit   = $conn->selectOne('ad_units', ['id' => $unitId]);

        if (!$unit) {
            jsonResponse(['success' => false, 'message' => 'Ad unit not found.'], 404);
        }

        $newStatus = ((int)($unit['is_active'] ?? 0) === 1) ? 0 : 1;
        $conn->update('ad_units', ['is_active' => $newStatus], ['id' => $unitId]);

        jsonResponse(['success' => true, 'message' => 'Ad unit status toggled successfully.']);
        break;

    case 'delete_ad_unit':
        $conn->createTable('ad_units');
        $unitId = (int)($_POST['unit_id'] ?? 0);
        $conn->delete('ad_units', ['id' => $unitId]);
        jsonResponse(['success' => true, 'message' => 'Ad unit deleted.']);
        break;

    // Process & Approve User Payout Request
    case 'process_payout':
        $conn->createTable('payouts');
        $conn->createTable('user_earnings');
        $payoutId = (int)($_POST['payout_id'] ?? 0);
        $status   = strtolower(cleanInput($_POST['status'] ?? 'completed')); // completed, rejected

        $payout = $conn->selectOne('payouts', ['id' => $payoutId]);
        if (!$payout) {
            jsonResponse(['success' => false, 'message' => 'Payout record not found.'], 404);
        }

        if (strtolower((string)($payout['status'] ?? '')) !== 'pending') {
            jsonResponse(['success' => false, 'message' => 'This payout request is already processed.'], 400);
        }

        $conn->update('payouts', [
            'status'       => $status,
            'processed_at' => date('Y-m-d H:i:s'),
            'admin_notes'  => cleanInput($_POST['notes'] ?? '')
        ], ['id' => $payoutId]);

        if ($status === 'completed') {
            // Deduct / update user available balance record in user_earnings
            $uId = (int)$payout['user_id'];
            $amount = (float)$payout['amount'];

            $conn->insert('user_earnings', [
                'user_id'      => $uId,
                'website_id'   => 0,
                'type'         => 'payout_deduction',
                'gross_amount' => -$amount,
                'platform_fee' => 0,
                'net_user_earning' => -$amount,
                'description'  => "Payout Disbursement Completed (Ref #{$payoutId})",
                'created_at'   => date('Y-m-d H:i:s')
            ]);
        }

        jsonResponse(['success' => true, 'message' => "Payout request status updated to " . strtoupper($status) . "."]);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Invalid administrative monetization action.'], 400);
        break;
}
?>