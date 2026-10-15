<?php
/**
 * workspace_action.php
 *
 * Backend processor for Workspace and Team Management operations.
 * Manages workspace creation, workspace switching, team invitations,
 * workspace member roles, and resource scope bounds safely.
 */

// Enable strict typing for safety
declare(strict_types=1);

// Require central system configurations and database engine
require_once __DIR__ . '/db.php';

// Instantiate secure session context
secureSession();

// Restrict to authenticated standard user or administrator sessions
if (!isset($_SESSION['email'])) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized access. Please log in.'], 401);
}

// Fetch current user database reference
$user = $conn->selectOne('users', ['email' => $_SESSION['email']]);
if (!$user) {
    jsonResponse(['success' => false, 'message' => 'User record not found.'], 404);
}

// Extract request action payload parameters
$action = cleanInput($_POST['action'] ?? $_GET['action'] ?? '');
if (empty($action)) {
    jsonResponse(['success' => false, 'message' => 'Missing action parameter.'], 400);
}

$conn->createTable('workspaces');
$conn->createTable('workspace_members');

switch ($action) {

    // Action 1: Switch active working workspace
    case 'switch_workspace':
        $workspaceId = (int)($_POST['workspace_id'] ?? $_GET['workspace_id'] ?? 0);
        if ($workspaceId <= 0) {
            jsonResponse(['success' => false, 'message' => 'Invalid workspace ID.'], 400);
        }

        // Verify user ownership or team membership
        $userWorkspaces = getUserWorkspaces((int)$user['id']);
        $validWorkspace = null;
        foreach ($userWorkspaces as $ws) {
            if ((int)($ws['id'] ?? 0) === $workspaceId) {
                $validWorkspace = $ws;
                break;
            }
        }

        if (!$validWorkspace) {
            jsonResponse(['success' => false, 'message' => 'Access Denied: You do not belong to this workspace.'], 403);
        }

        $_SESSION['active_workspace_id'] = $workspaceId;
        jsonResponse([
            'success' => true,
            'message' => "Switched active workspace to '" . htmlspecialchars($validWorkspace['name']) . "'",
            'workspace' => $validWorkspace
        ]);
        break;

    // Action 2: Create additional workspace environment
    case 'create_workspace':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
        }

        $name = cleanInput($_POST['name'] ?? '');
        $description = cleanInput($_POST['description'] ?? '');
        $type = cleanInput($_POST['type'] ?? 'General');

        if (empty($name)) {
            jsonResponse(['success' => false, 'message' => 'Please provide a workspace name.'], 400);
        }

        $newWs = $conn->insert('workspaces', [
            'user_id' => $user['id'],
            'name' => $name,
            'description' => $description,
            'type' => $type,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        if ($newWs) {
            // Register owner in workspace_members
            $conn->insert('workspace_members', [
                'workspace_id' => $newWs['id'],
                'user_id' => $user['id'],
                'fullname' => $user['fullname'] ?? 'Owner',
                'email' => strtolower((string)$user['email']),
                'role' => 'owner',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $_SESSION['active_workspace_id'] = $newWs['id'];

            jsonResponse([
                'success' => true,
                'message' => "Workspace '" . htmlspecialchars($name) . "' created successfully!",
                'workspace' => $newWs
            ]);
        } else {
            jsonResponse(['success' => false, 'message' => 'Failed to create workspace in database.'], 500);
        }
        break;

    // Action 3: Invite team member into active workspace
    case 'invite_member':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
        }

        $activeWs = getActiveWorkspace($user);
        $wsId = (int)$activeWs['id'];

        $inviteEmail = strtolower(cleanInput($_POST['email'] ?? ''));
        $inviteRole  = strtolower(cleanInput($_POST['role'] ?? 'member'));
        $fullname    = cleanInput($_POST['fullname'] ?? '');

        if (empty($inviteEmail) || !filter_var($inviteEmail, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['success' => false, 'message' => 'Please enter a valid email address.'], 400);
        }

        $validRoles = ['owner', 'admin', 'developer', 'designer', 'editor', 'member', 'viewer'];
        if (!in_array($inviteRole, $validRoles, true)) {
            $inviteRole = 'member';
        }

        // Check if member already exists in this workspace
        $existingMem = $conn->selectOne('workspace_members', [
            'workspace_id' => $wsId,
            'email' => $inviteEmail
        ]);

        if ($existingMem) {
            jsonResponse(['success' => false, 'message' => 'This user is already a member or invited to this workspace.'], 409);
        }

        // Check if invited email matches registered platform user
        $invitedUser = $conn->selectOne('users', ['email' => $inviteEmail]);
        $invitedUserId = $invitedUser ? (int)$invitedUser['id'] : null;
        if ($invitedUser && empty($fullname)) {
            $fullname = $invitedUser['fullname'] ?? '';
        }

        $newMem = $conn->insert('workspace_members', [
            'workspace_id' => $wsId,
            'user_id' => $invitedUserId,
            'fullname' => $fullname ?: $inviteEmail,
            'email' => $inviteEmail,
            'role' => $inviteRole,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        if ($newMem) {
            jsonResponse([
                'success' => true,
                'message' => "Team member '{$inviteEmail}' invited as " . ucfirst($inviteRole) . "!",
                'member' => $newMem
            ]);
        } else {
            jsonResponse(['success' => false, 'message' => 'Failed to add team member.'], 500);
        }
        break;

    // Action 4: Remove team member from active workspace
    case 'remove_member':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
        }

        $activeWs = getActiveWorkspace($user);
        $wsId = (int)$activeWs['id'];
        $memberId = (int)($_POST['member_id'] ?? 0);

        if ($memberId <= 0) {
            jsonResponse(['success' => false, 'message' => 'Invalid team member ID.'], 400);
        }

        $memRecord = $conn->selectOne('workspace_members', ['id' => $memberId, 'workspace_id' => $wsId]);
        if (!$memRecord) {
            jsonResponse(['success' => false, 'message' => 'Workspace team member record not found.'], 404);
        }

        if (strtolower((string)($memRecord['role'] ?? '')) === 'owner') {
            jsonResponse(['success' => false, 'message' => 'Workspace owner cannot be removed.'], 403);
        }

        $conn->delete('workspace_members', ['id' => $memberId]);
        jsonResponse([
            'success' => true,
            'message' => 'Team member removed from workspace successfully.'
        ]);
        break;

    // Action 5: Update workspace member role
    case 'update_member_role':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
        }

        $activeWs = getActiveWorkspace($user);
        $wsId = (int)$activeWs['id'];
        $memberId = (int)($_POST['member_id'] ?? 0);
        $newRole  = strtolower(cleanInput($_POST['role'] ?? 'member'));

        $validRoles = ['owner', 'admin', 'developer', 'designer', 'editor', 'member', 'viewer'];
        if (!in_array($newRole, $validRoles, true)) {
            jsonResponse(['success' => false, 'message' => 'Invalid workspace role.'], 400);
        }

        $memRecord = $conn->selectOne('workspace_members', ['id' => $memberId, 'workspace_id' => $wsId]);
        if (!$memRecord) {
            jsonResponse(['success' => false, 'message' => 'Workspace team member record not found.'], 404);
        }

        if (strtolower((string)($memRecord['role'] ?? '')) === 'owner' && $newRole !== 'owner') {
            jsonResponse(['success' => false, 'message' => 'Cannot downgrade primary workspace owner role.'], 403);
        }

        $conn->update('workspace_members', ['role' => $newRole], ['id' => $memberId]);
        jsonResponse([
            'success' => true,
            'message' => "Updated member role to " . ucfirst($newRole)
        ]);
        break;

    // Action 6: List workspaces
    case 'list_workspaces':
        $workspaces = getUserWorkspaces((int)$user['id']);
        $activeWs = getActiveWorkspace($user);

        jsonResponse([
            'success' => true,
            'workspaces' => $workspaces,
            'active_workspace_id' => $activeWs['id']
        ]);
        break;

    // Action 7: List workspace members
    case 'list_members':
        $activeWs = getActiveWorkspace($user);
        $wsId = (int)($activeWs['id'] ?? 0);

        $members = $conn->select('workspace_members', ['workspace_id' => $wsId]) ?: [];
        jsonResponse([
            'success' => true,
            'workspace' => $activeWs,
            'members' => $members
        ]);
        break;

    // Action 8: Update workspace settings
    case 'update_workspace_settings':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
        }

        $activeWs = getActiveWorkspace($user);
        $wsId = (int)$activeWs['id'];

        $name = cleanInput($_POST['name'] ?? '');
        $description = cleanInput($_POST['description'] ?? '');
        $type = cleanInput($_POST['type'] ?? 'General');

        if (empty($name)) {
            jsonResponse(['success' => false, 'message' => 'Workspace name cannot be left empty.'], 400);
        }

        $conn->update('workspaces', [
            'name' => $name,
            'description' => $description,
            'type' => $type,
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $wsId]);

        jsonResponse([
            'success' => true,
            'message' => 'Workspace details updated successfully!'
        ]);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Unrecognized workspace action request.'], 400);
        break;
}
?>
