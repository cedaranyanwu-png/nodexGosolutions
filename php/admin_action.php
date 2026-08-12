<?php
/**
 * admin_action.php
 *
 * Implements administrative user profile adjustments inside the custom JSON Database.
 * Securely restricts deletion, suspension, and promotion operations to authorized roles using RBAC.
 * Features thorough line-by-line comments for scalability and readability.
 */

// Enable strict typing for safety
declare(strict_types=1);

// Require system connections and helper files
require_once __DIR__ . '/db.php';

// Instantiate secure session configurations
secureSession();

// Access Control: Check if active user holds administrative/super-administrative privilege
$executingRole = strtolower((string)($_SESSION['role'] ?? ''));
if ($executingRole !== 'admin' && $executingRole !== 'super admin') {
    jsonResponse(['success' => false, 'message' => 'Unauthorized access: Administrative privileges required.'], 401);
}

// Restrict authentication requests to POST actions only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

// Extract administrative command parameters
$action       = cleanInput($_POST['action'] ?? '');
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

// Execute matched administrative operation with server-side RBAC enforcement
switch ($action) {

    // ACTION: Toggle Role privilege level between 'super admin', 'admin', 'manager', 'support', 'moderator', and 'tenant'
    case 'toggle_role':
        // RBAC Enforcement Guard: only 'rbac.manage' or Super Admin role is allowed to modify roles
        if (!checkAdminPermission('rbac.manage')) {
            jsonResponse(['success' => false, 'message' => 'Access Denied: You do not possess rbac.manage capability.'], 403);
        }

        // CRITICAL CHECK: Main administrator account (admin@nodexplatform.com.ng) role cannot be demoted
        if ($targetEmail === 'admin@nodexplatform.com.ng') {
            jsonResponse(['success' => false, 'message' => 'CRITICAL PROTECTION: The main administrator account cannot be demoted.'], 403);
        }

        // Only the main administrator or Super Admin is allowed to toggle/promote other accounts to Admin/Super Admin
        if ($executingAdminEmail !== 'admin@nodexplatform.com.ng' && $executingRole !== 'super admin') {
            jsonResponse(['success' => false, 'message' => 'CRITICAL SECURITY: Only Super Administrators can assign administrative roles.'], 403);
        }

        // Accept requested target role
        $newRole = strtolower(cleanInput($_POST['role'] ?? 'tenant'));
        if ($newRole === 'super admin') { $newRole = 'superadmin'; }
        if ($newRole === 'marketing head') { $newRole = 'marketing_head'; }

        // Securely support and validate all 7 administrative roles plus regular tenant
        $validRoles = ['superadmin', 'admin', 'manager', 'support', 'moderator', 'financial', 'marketing_head', 'tenant'];
        if (!in_array($newRole, $validRoles, true)) {
            jsonResponse(['success' => false, 'message' => 'Invalid role assigned.'], 400);
        }

        // Update target row
        $updated = $conn->update('users', ['role' => $newRole], ['id' => $targetUserId]);
        if ($updated > 0) {
            // Log administrative activity audit trace
            $conn->insert('activity_logs', [
                'user_id'    => $user['id'] ?? 0,
                'email'      => $executingAdminEmail,
                'action'     => 'role_modified',
                'details'    => "Updated user ID:{$targetUserId} ({$targetEmail}) role to: " . strtoupper($newRole),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            jsonResponse(['success' => true, 'message' => "User role updated to " . strtoupper($newRole) . " successfully!"]);
        } else {
            jsonResponse(['success' => false, 'message' => 'No changes made or failed to update user role.'], 200);
        }
        break;

    // ACTION: Toggle user Account status between 'Active' and 'Suspended'
    case 'toggle_status':
        // RBAC Enforcement Guard: check if executing admin has users.suspend permission
        if (!checkAdminPermission('users.suspend')) {
            jsonResponse(['success' => false, 'message' => 'Access Denied: You do not possess users.suspend capability.'], 403);
        }

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
            // Log administrative activity audit trace
            $conn->insert('activity_logs', [
                'user_id'    => $user['id'] ?? 0,
                'email'      => $executingAdminEmail,
                'action'     => 'user_status_toggled',
                'details'    => "Toggled status of user ID:{$targetUserId} ({$targetEmail}) to: " . strtoupper($newStatus),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            jsonResponse(['success' => true, 'message' => "User status toggled to " . strtoupper($newStatus) . " successfully!"]);
        } else {
            jsonResponse(['success' => false, 'message' => 'Failed to adjust user status.'], 500);
        }
        break;

    // ACTION: Delete user account from system JSON tables completely
    case 'delete_user':
        // RBAC Enforcement Guard: check if executing admin has users.delete permission
        if (!checkAdminPermission('users.delete')) {
            jsonResponse(['success' => false, 'message' => 'Access Denied: You do not possess users.delete capability.'], 403);
        }

        // CRITICAL PROTECTION: The main administrator account can NEVER be deleted by anyone!
        if ($targetEmail === 'admin@nodexplatform.com.ng') {
            jsonResponse(['success' => false, 'message' => 'CRITICAL PROTECTION: The main administrator account (Cedar Anyanwu) can NEVER be deleted.'], 403);
        }

        // SECURITY CHECK: No administrator, other than the main administrator, can delete any administrator account
        if (($targetRole === 'admin' || $targetRole === 'super admin') && $executingAdminEmail !== 'admin@nodexplatform.com.ng') {
            jsonResponse(['success' => false, 'message' => 'CRITICAL SECURITY: Only Cedar Anyanwu (main administrator) is authorized to delete other administrator accounts.'], 403);
        }

        // Execute row deletion statement
        $deleted = $conn->delete('users', ['id' => $targetUserId]);
        if ($deleted > 0) {
            // Log administrative activity audit trace
            $conn->insert('activity_logs', [
                'user_id'    => $user['id'] ?? 0,
                'email'      => $executingAdminEmail,
                'action'     => 'user_deleted',
                'details'    => "Permanently deleted user ID:{$targetUserId} ({$targetEmail})",
                'created_at' => date('Y-m-d H:i:s')
            ]);
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
