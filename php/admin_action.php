<?php
/**
 * admin_action.php
 *
 * Handles administrator backend operations (employing/suspending, role promotions, and account deletions).
 * Secured strictly to authenticated sessions with administrative role privileges.
 */

// Enable strict typing for better safety and quality
declare(strict_types=1);

// Require central database configuration and security helpers from same folder
require_once __DIR__ . '/db.php';

// Instantiate secure session configurations
secureSession();

// Access Control: Strict check if active user session holds administrative role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Return unauthorized JSON forbidden response
    jsonResponse([
        'success' => false,
        'message' => 'Access denied. Administrator privileges required.'
    ], 403);
}

// Extract requested POST actions
$action = $_POST['action'] ?? '';
// Extract target user ID
$targetUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

// Verify valid user ID parameters are provided
if ($targetUserId <= 0) {
    // Return bad request error
    jsonResponse([
        'success' => false,
        'message' => 'Invalid target user identifier provided.'
    ], 400);
}

// ------------------------------------------------------------
// 1. TOGGLE USER STATUS (Employ / Suspend)
// ------------------------------------------------------------
if ($action === 'toggle_status') {
    // Fetch matched user details from system database
    $user = $conn->selectOne('users', ['id' => $targetUserId]);

    // Check if user account exists
    if ($user === null) {
        // Return not found error response
        jsonResponse(['success' => false, 'message' => 'User account not found.'], 404);
    }

    // Determine current status: default empty/missing status to 'Active'
    $currentStatus = strtolower((string)($user['status'] ?? 'active'));
    // Toggle active status between Active and Suspended states
    $newStatus = ($currentStatus === 'suspended') ? 'Active' : 'Suspended';

    // Update user status metrics in the database
    $conn->update('users', ['status' => $newStatus], ['id' => $targetUserId]);

    // Return success response details
    jsonResponse([
        'success' => true,
        'message' => "User status successfully toggled to '{$newStatus}'."
    ]);
}

// ------------------------------------------------------------
// 2. TOGGLE USER ROLE (Promote / Demote)
// ------------------------------------------------------------
if ($action === 'toggle_role') {
    // Fetch target user details
    $user = $conn->selectOne('users', ['id' => $targetUserId]);

    // Check if user exists
    if ($user === null) {
        // Return not found error response
        jsonResponse(['success' => false, 'message' => 'User account not found.'], 404);
    }

    // Prevent administrators from self-demoting to avoid locking themselves out
    if ($targetUserId === (int)($_SESSION['user_id'] ?? 0)) {
        // Return bad request response
        jsonResponse(['success' => false, 'message' => 'Self-demotion is restricted to maintain system access.'], 400);
    }

    // Capture current privilege role: default to 'tenant' if empty
    $currentRole = strtolower((string)($user['role'] ?? 'tenant'));
    // Toggle role values between 'admin' and 'tenant'
    $newRole = ($currentRole === 'admin') ? 'tenant' : 'admin';

    // Update user role level in the database
    $conn->update('users', ['role' => $newRole], ['id' => $targetUserId]);

    // Return success response details
    jsonResponse([
        'success' => true,
        'message' => "User role successfully toggled to '{$newRole}'."
    ]);
}

// ------------------------------------------------------------
// 3. DELETE USER ACCOUNT
// ------------------------------------------------------------
if ($action === 'delete_user') {
    // Prevent administrators from deleting their own active accounts
    if ($targetUserId === (int)($_SESSION['user_id'] ?? 0)) {
        // Return bad request response
        jsonResponse(['success' => false, 'message' => 'Self-deletion is restricted to maintain system integrity.'], 400);
    }

    // Attempt deleting user matching target id from users table
    $deletedCount = $conn->delete('users', ['id' => $targetUserId]);

    // Check if a record was deleted successfully
    if ($deletedCount > 0) {
        // Return success response details
        jsonResponse([
            'success' => true,
            'message' => 'User account deleted successfully.'
        ]);
    } else {
        // Return deletion failure error response
        jsonResponse(['success' => false, 'message' => 'Failed to delete user account.'], 500);
    }
}

// Return bad request if action parameter did not match any registered routine
jsonResponse([
    'success' => false,
    'message' => 'Invalid administration action parameter requested.'
], 400);
?>
