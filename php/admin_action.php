<?php
/**
 * admin_action.php
 *
 * Implements administrative user profile adjustments inside the custom JSON Database.
 * Securely restricts deletion and promotion operations to authorized personnel only.
 */

// Require system connections and helper files
require_once __DIR__ . '/db.php';

// Instantiate secure session configurations
secureSession();

// Access Control: Check if active user holds administrative privileges
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    jsonResponse(['success' => false, 'message' => 'Unauthorized access.'], 401);
}

// Restrict authentication requests to POST actions only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

// Extract administrative command parameters
$action = cleanInput($_POST['action'] ?? '');
$targetUserId = (int)($_POST['user_id'] ?? 0);

// Reject requests with missing targets
if ($targetUserId <= 0) {
    jsonResponse(['success' => false, 'message' => 'Invalid target user ID.'], 400);
}

// Fetch the target user details from the database
$targetUser = $conn->selectOne('users', ['id' => $targetUserId]);
if ($targetUser === null) {
    jsonResponse(['success' => false, 'message' => 'Designated user account not found.'], 404);
}

// Capture targets emails and roles
$targetEmail = strtolower((string)($targetUser['email'] ?? ''));
$targetRole  = strtolower((string)($targetUser['role'] ?? 'tenant'));

// Capture current executing administrator email
$executingAdminEmail = strtolower((string)($_SESSION['email'] ?? ''));

// Execute matched administrative operation
switch ($action) {

    // ACTION: Toggle Role privilege level between 'admin' and 'tenant'
    case 'toggle_role':
        // CRITICAL CHECK: Main administrator account (admin@nodexplatform.com.ng) role cannot be demoted
        if ($targetEmail === 'admin@nodexplatform.com.ng') {
            jsonResponse(['success' => false, 'message' => 'CRITICAL PROTECTION: The main administrator account cannot be demoted.'], 403);
        }

        // Only the main administrator is allowed to toggle/promote other accounts to Admin
        if ($executingAdminEmail !== 'admin@nodexplatform.com.ng') {
            jsonResponse(['success' => false, 'message' => 'CRITICAL SECURITY: Only the main administrator (Cedar Anyanwu) can promote or demote other accounts.'], 403);
        }

        // Toggle role values between 'admin' and 'tenant'
        $newRole = ($targetRole === 'admin') ? 'tenant' : 'admin';

        // Update target row
        $updated = $conn->update('users', ['role' => $newRole], ['id' => $targetUserId]);
        if ($updated > 0) {
            jsonResponse(['success' => true, 'message' => "User role toggled to " . strtoupper($newRole) . " successfully!"]);
        } else {
            jsonResponse(['success' => false, 'message' => 'Failed to toggle user role.'], 500);
        }
        break;

    // ACTION: Toggle user Account status between 'Active' and 'Suspended'
    case 'toggle_status':
        // CRITICAL CHECK: Main administrator account (admin@nodexplatform.com.ng) cannot be suspended
        if ($targetEmail === 'admin@nodexplatform.com.ng') {
            jsonResponse(['success' => false, 'message' => 'CRITICAL PROTECTION: The main administrator account cannot be suspended.'], 403);
        }

        // Capture current target status
        $currentStatus = strtolower((string)($targetUser['status'] ?? 'active'));
        $newStatus = ($currentStatus === 'suspended') ? 'active' : 'suspended';

        // Update target row
        $updated = $conn->update('users', ['status' => $newStatus], ['id' => $targetUserId]);
        if ($updated > 0) {
            jsonResponse(['success' => true, 'message' => "User status toggled to " . strtoupper($newStatus) . " successfully!"]);
        } else {
            jsonResponse(['success' => false, 'message' => 'Failed to adjust user status.'], 500);
        }
        break;

    // ACTION: Delete user account from system JSON tables completely
    case 'delete_user':
        // CRITICAL PROTECTION: The main administrator account can NEVER be deleted by anyone!
        if ($targetEmail === 'admin@nodexplatform.com.ng') {
            jsonResponse(['success' => false, 'message' => 'CRITICAL PROTECTION: The main administrator account (Cedar Anyanwu) can NEVER be deleted.'], 403);
        }

        // SECURITY CHECK: No administrator, other than the main administrator, can delete any administrator account
        if ($targetRole === 'admin' && $executingAdminEmail !== 'admin@nodexplatform.com.ng') {
            jsonResponse(['success' => false, 'message' => 'CRITICAL SECURITY: Only the main administrator (Cedar Anyanwu) is authorized to delete other administrator accounts.'], 403);
        }

        // Execute row deletion statement
        $deleted = $conn->delete('users', ['id' => $targetUserId]);
        if ($deleted > 0) {
            jsonResponse(['success' => true, 'message' => 'User profile deleted completely from system records!']);
        } else {
            jsonResponse(['success' => false, 'message' => 'Failed to delete user account.'], 500);
        }
        break;

    // DEFAULT Scenario: Return invalid action warning
    default:
        jsonResponse(['success' => false, 'message' => 'Invalid administrative action supplied.'], 400);
        break;
}
?>
