<?php
/**
 * admin_pricing_action.php
 *
 * Implements granular pricing plan CRUD administrative actions.
 * Restricts access securely via real-time session RBAC validations.
 * Records changes in the Activity log audit trail.
 */

// Enable strict typing for safety
declare(strict_types=1);

// Require central connections and helper files
require_once __DIR__ . '/db.php';

// Instantiate secure session configurations
secureSession();

// RBAC Enforcement Guard: only super admins or administrators with pricing.view/edit capability can enter
if (!checkAdminPermission('pricing.edit') && !checkAdminPermission('pricing.view')) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized: Pricing modification privileges required.'], 401);
}

// Restrict to POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

// Extract administrative command parameters
$action = cleanInput($_POST['action'] ?? '');
if (empty($action)) {
    jsonResponse(['success' => false, 'message' => 'Missing pricing action.'], 400);
}

$conn->createTable('plans');

switch ($action) {

    // Action 1: Create or update a pricing plan in the database
    case 'save_plan':
        if (!checkAdminPermission('pricing.edit')) {
            jsonResponse(['success' => false, 'message' => 'Access Denied: pricing.edit permission required.'], 403);
        }

        $id            = strtolower(cleanInput($_POST['id'] ?? ''));
        $name          = cleanInput($_POST['name'] ?? '');
        $price         = (float)($_POST['price'] ?? 0.0);
        $currency      = strtoupper(cleanInput($_POST['currency'] ?? 'NGN'));
        $billingPeriod = cleanInput($_POST['billing_period'] ?? 'month');
        $description   = cleanInput($_POST['description'] ?? '');
        $features      = $_POST['features'] ?? ''; // Expecting a JSON-encoded list or array
        $isActive      = (int)($_POST['is_active'] ?? 1);
        $displayOrder  = (int)($_POST['display_order'] ?? 1);
        $isRecommended = (int)($_POST['is_recommended'] ?? 0);

        if (empty($id) || empty($name) || $price < 0) {
            jsonResponse(['success' => false, 'message' => 'Please provide a valid plan ID, name, and non-negative price.'], 400);
        }

        // Format features safely as serialized JSON list
        if (is_array($features)) {
            $featuresStr = json_encode($features);
        } else {
            // Check if string is already valid json, otherwise parse comma-separated items
            $decoded = json_decode((string)$features, true);
            if (is_array($decoded)) {
                $featuresStr = $features;
            } else {
                $featuresStr = json_encode(array_map('trim', explode(',', (string)$features)));
            }
        }

        $planData = [
            'id'             => $id,
            'name'           => $name,
            'price'          => $price,
            'currency'       => $currency,
            'billing_period' => $billingPeriod,
            'description'    => $description,
            'features'       => $featuresStr,
            'is_active'      => $isActive,
            'display_order'  => $displayOrder,
            'is_recommended' => $isRecommended,
            'updated_at'     => date('Y-m-d H:i:s')
        ];

        // Check if plan already exists in system database
        $existing = $conn->selectOne('plans', ['id' => $id]);
        if ($existing) {
            $conn->update('plans', $planData, ['id' => $id]);
            $msg = "Pricing plan '{$name}' updated successfully.";
        } else {
            $planData['created_at'] = date('Y-m-d H:i:s');
            $conn->insert('plans', $planData);
            $msg = "Pricing plan '{$name}' created successfully.";
        }

        // Log administrative action
        $conn->insert('activity_logs', [
            'user_id'    => $_SESSION['user_id'] ?? 0,
            'email'      => $_SESSION['email'] ?? 'admin',
            'action'     => 'pricing_plan_saved',
            'details'    => "Saved pricing plan '{$name}' ID:{$id} with price: {$currency}{$price}",
            'created_at' => date('Y-m-d H:i:s')
        ]);

        jsonResponse(['success' => true, 'message' => $msg]);
        break;

    // Action 2: Deactivate or delete a pricing plan
    case 'delete_plan':
        if (!checkAdminPermission('pricing.edit')) {
            jsonResponse(['success' => false, 'message' => 'Access Denied: pricing.edit permission required.'], 403);
        }

        $id = strtolower(cleanInput($_POST['id'] ?? ''));
        if (empty($id)) {
            jsonResponse(['success' => false, 'message' => 'Invalid plan ID.'], 400);
        }

        $conn->delete('plans', ['id' => $id]);

        // Log administrative action
        $conn->insert('activity_logs', [
            'user_id'    => $_SESSION['user_id'] ?? 0,
            'email'      => $_SESSION['email'] ?? 'admin',
            'action'     => 'pricing_plan_deleted',
            'details'    => "Permanently deleted pricing plan ID:{$id}",
            'created_at' => date('Y-m-d H:i:s')
        ]);

        jsonResponse(['success' => true, 'message' => 'Pricing plan deleted successfully.']);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Unrecognized pricing action requested.'], 400);
        break;
}
?>
