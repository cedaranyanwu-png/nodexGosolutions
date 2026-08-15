<?php
/**
 * admin_rbac_action.php
 *
 * Secure backend processor that handles administrative RBAC (Role-Based Access Control).
 * Manages roles and dynamic permissions matrices inside the system database.
 * Restricts actions strictly to Super Admins or roles with rbac.manage capabilities.
 * Fully documented line-by-line.
 */

// Enable strict typing for safety
declare(strict_types=1);

// Require central system configurations
require_once __DIR__ . '/db.php';

// Instantiate secure session configurations
secureSession();

// RBAC Enforcement Guard: only super admins or users with rbac.manage capability can enter
if (!checkAdminPermission('rbac.manage')) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized: RBAC modification privileges required.'], 401);
}

// Restrict requests to POST actions only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

// Extract parameters
$action = cleanInput($_POST['action'] ?? '');
if (empty($action)) {
    jsonResponse(['success' => false, 'message' => 'Missing RBAC action.'], 400);
}

$conn->createTable('permissions');
$conn->createTable('roles');

switch ($action) {

    // Action 1: Toggle granular permission for a role
    case 'toggle_permission':
        $role       = strtolower(cleanInput($_POST['role'] ?? ''));
        $permission = cleanInput($_POST['permission'] ?? '');

        if (empty($role) || empty($permission)) {
            jsonResponse(['success' => false, 'message' => 'Please provide both role and permission keys.'], 400);
        }

        // Locate existing record
        $record = $conn->selectOne('permissions', ['role' => $role, 'permission' => $permission]);
        $newValue = 1;
        if ($record) {
            $newValue = (int)($record['is_allowed'] ?? 0) === 1 ? 0 : 1;
            $conn->update('permissions', ['is_allowed' => $newValue], ['role' => $role, 'permission' => $permission]);
        } else {
            $conn->insert('permissions', [
                'role'       => $role,
                'permission' => $permission,
                'is_allowed' => 1
            ]);
        }

        // Log administrative action
        $conn->createTable('activity_logs');
        $conn->insert('activity_logs', [
            'user_id'    => $_SESSION['user_id'] ?? 0,
            'email'      => $_SESSION['email'] ?? 'admin',
            'action'     => 'rbac_permission_toggled',
            'details'    => "Toggled permission '{$permission}' for role '{$role}' to: " . ($newValue === 1 ? 'ALLOWED' : 'DENIED'),
            'created_at' => date('Y-m-d H:i:s')
        ]);

        jsonResponse([
            'success' => true,
            'message' => "Permission updated successfully. Role '{$role}' is now " . ($newValue === 1 ? 'ALLOWED' : 'DENIED') . " to perform '{$permission}' actions."
        ]);
        break;

    // Action 2: Add custom administrative role
    case 'add_role':
        $roleId   = strtolower(cleanInput($_POST['role_id'] ?? ''));
        $roleName = cleanInput($_POST['role_name'] ?? '');
        $desc     = cleanInput($_POST['description'] ?? '');

        if (empty($roleId) || empty($roleName)) {
            jsonResponse(['success' => false, 'message' => 'Please provide role ID and name.'], 400);
        }

        // Check pre-existing
        $existing = $conn->selectOne('roles', ['id' => $roleId]);
        if ($existing) {
            jsonResponse(['success' => false, 'message' => 'This role ID already exists.'], 409);
        }

        $conn->insert('roles', [
            'id'          => $roleId,
            'name'        => $roleName,
            'description' => $desc
        ]);

        // Log administrative action
        $conn->insert('activity_logs', [
            'user_id'    => $_SESSION['user_id'] ?? 0,
            'email'      => $_SESSION['email'] ?? 'admin',
            'action'     => 'rbac_role_created',
            'details'    => "Created custom role '{$roleName}' ID:{$roleId}",
            'created_at' => date('Y-m-d H:i:s')
        ]);

        jsonResponse(['success' => true, 'message' => "Role '{$roleName}' created successfully."]);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Unrecognized RBAC action requested.'], 400);
        break;
}
?>
