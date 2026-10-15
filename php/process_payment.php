<?php
/**
 * process_payment.php
 *
 * Secure backend-driven subscription payment processor for tenant workspaces.
 * Validates active tenant sessions, processes payment simulation parameters,
 * and securely updates subscription start, end, and status fields inside the custom JSON database.
 */

// Enable strict typing for architectural safety
declare(strict_types=1);

// Require central system configurations and security helpers
require_once __DIR__ . '/db.php';

// Instantiate secure session context
secureSession();

// Restrict payment processing requests to POST actions only for optimal security
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Return method not allowed HTTP status response
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

// Access Control: Ensure the user session is active and authenticated
if (!isset($_SESSION['email'])) {
    // Return unauthorized response status
    jsonResponse(['success' => false, 'message' => 'Unauthorized access. Please log in.'], 401);
}

// Extract selected subscription plan from payload
$plan = cleanInput($_POST['plan'] ?? '');

// Reject empty plan parameters
if (empty($plan)) {
    // Return bad request error status
    jsonResponse(['success' => false, 'message' => 'Please select a valid hosting plan.'], 400);
}

// Verify plan validity
$validPlans = ['Starter Space', 'Growth Plan', 'Enterprise Space'];
if (!in_array($plan, $validPlans, true)) {
    // Return bad request error for unrecognized plans
    jsonResponse(['success' => false, 'message' => 'Invalid hosting plan selected.'], 400);
}

// Fetch active user records matching email from users JSON table
$user = $conn->selectOne('users', ['email' => $_SESSION['email']]);

// Reject if user record is missing in database
if ($user === null) {
    // Return resource not found response
    jsonResponse(['success' => false, 'message' => 'User account not found.'], 404);
}

// Calculate the subscription start time (current server timestamp)
$subStart = date('Y-m-d H:i:s');
// Automatically compute standard 1-month renewal date
$subEnd = date('Y-m-d H:i:s', strtotime('+1 month'));

// Persist the verified subscription activation state into the database
$updatedRows = $conn->update('users', [
    'subscription_status' => 'active',
    'subscription_plan'   => $plan,
    'subscription_start'  => $subStart,
    'subscription_end'    => $subEnd
], [
    'id' => $user['id']
]);

// Return success feedback to trigger client-side transition
if ($updatedRows > 0) {
    // Output success response block
    jsonResponse([
        'success' => true,
        'message' => "Payment simulated and verified successfully! Your subscription to '{$plan}' is now active."
    ]);
} else {
    // Return state unchanged or failure response status
    jsonResponse([
        'success' => false,
        'message' => 'Failed to process subscription activation. Please try again.'
    ], 500);
}
?>
