<?php
/**
 * admin_team_action.php
 *
 * Dedicated administrative API controller that handles Staff Teams & Team Leaders AJAX actions.
 * Supports secure operations to create, update, delete, and list team profiles,
 * as well as assigning leaders and members.
 * Guarded strictly by staff sessions and granular RBAC security permission checks.
 *
 * All code is extensively documented line-by-line for ultimate readability and scale.
 */

// Enable strict typing for safety
declare(strict_types=1);

// Require central database configuration and security helpers
require_once __DIR__ . '/db.php';

// Instantiate secure session configurations
secureSession();

// Restrict access to authenticated staff only
enforceAuth(true);

$activeRole = strtolower((string)($_SESSION['role'] ?? ''));
$staffRoles = ['admin', 'super admin', 'manager', 'moderator', 'support'];
if (!in_array($activeRole, $staffRoles, true)) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized. Staff privilege only.'], 401);
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

// Extract target action from request payload
$action = cleanInput($_POST['action'] ?? '');
$conn->createTable('teams');

// Helper to determine if a user is eligible staff (role other than tenant)
function isUserEligibleStaff(int $userId, Database $db): bool {
    $db->createTable('users');
    $user = $db->selectOne('users', ['id' => $userId]);
    if (!$user) return false;
    $role = strtolower((string)($user['role'] ?? 'tenant'));
    return in_array($role, ['admin', 'super admin', 'manager', 'moderator', 'support'], true);
}

// --- ACTION 1: LIST TEAMS ---
if ($action === 'list_teams') {
    enforcePermission('teams.view', true);
    $teams = $conn->select('teams') ?: [];
    jsonResponse(['success' => true, 'teams' => $teams]);
}

// --- ACTION 2: CREATE TEAM ---
elseif ($action === 'create_team') {
    enforcePermission('teams.manage', true);

    $teamName = cleanInput($_POST['name'] ?? '');
    $description = cleanInput($_POST['description'] ?? '');
    $type = cleanInput($_POST['type'] ?? 'General');
    $leaderIdStr = cleanInput($_POST['leader_id'] ?? '');

    if (empty($teamName)) {
        jsonResponse(['success' => false, 'message' => 'Team name is required.'], 400);
    }

    $leaderId = null;
    if ($leaderIdStr !== '') {
        $leaderId = (int)$leaderIdStr;
        if (!isUserEligibleStaff($leaderId, $conn)) {
            jsonResponse(['success' => false, 'message' => 'Assigned leader must be an eligible internal staff member.'], 400);
        }
    }

    $members = [];
    if (isset($_POST['members']) && is_array($_POST['members'])) {
        foreach ($_POST['members'] as $mId) {
            $mIdInt = (int)$mId;
            if (isUserEligibleStaff($mIdInt, $conn)) {
                $members[] = $mIdInt;
            }
        }
    }

    $newTeam = [
        'name' => $teamName,
        'description' => $description,
        'type' => $type,
        'leader_id' => $leaderId,
        'members' => json_encode($members),
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];

    $inserted = $conn->insert('teams', $newTeam);
    if ($inserted) {
        jsonResponse(['success' => true, 'message' => 'Team created successfully!', 'team' => $inserted]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Failed to register team in database.'], 500);
    }
}

// --- ACTION 3: EDIT TEAM ---
elseif ($action === 'edit_team') {
    enforcePermission('teams.manage', true);

    $teamId = (int)($_POST['id'] ?? 0);
    $teamName = cleanInput($_POST['name'] ?? '');
    $description = cleanInput($_POST['description'] ?? '');
    $type = cleanInput($_POST['type'] ?? 'General');
    $leaderIdStr = cleanInput($_POST['leader_id'] ?? '');
    $status = cleanInput($_POST['status'] ?? 'active');

    $existingTeam = $conn->selectOne('teams', ['id' => $teamId]);
    if (!$existingTeam) {
        jsonResponse(['success' => false, 'message' => 'Team profile not found.'], 404);
    }

    if (empty($teamName)) {
        jsonResponse(['success' => false, 'message' => 'Team name is required.'], 400);
    }

    $leaderId = null;
    if ($leaderIdStr !== '') {
        $leaderId = (int)$leaderIdStr;
        if (!isUserEligibleStaff($leaderId, $conn)) {
            jsonResponse(['success' => false, 'message' => 'Assigned leader must be an eligible internal staff member.'], 400);
        }
    }

    $members = [];
    if (isset($_POST['members']) && is_array($_POST['members'])) {
        foreach ($_POST['members'] as $mId) {
            $mIdInt = (int)$mId;
            if (isUserEligibleStaff($mIdInt, $conn)) {
                $members[] = $mIdInt;
            }
        }
    }

    $updatedTeam = [
        'name' => $teamName,
        'description' => $description,
        'type' => $type,
        'leader_id' => $leaderId,
        'members' => json_encode($members),
        'status' => $status,
        'updated_at' => date('Y-m-d H:i:s')
    ];

    if ($conn->update('teams', $updatedTeam, ['id' => $teamId])) {
        jsonResponse(['success' => true, 'message' => 'Team profile updated successfully!']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Failed to apply team updates.'], 500);
    }
}

// --- ACTION 4: DELETE TEAM ---
elseif ($action === 'delete_team') {
    enforcePermission('teams.manage', true);

    $teamId = (int)($_POST['id'] ?? 0);
    $existingTeam = $conn->selectOne('teams', ['id' => $teamId]);
    if (!$existingTeam) {
        jsonResponse(['success' => false, 'message' => 'Team profile not found.'], 404);
    }

    if ($conn->delete('teams', ['id' => $teamId])) {
        jsonResponse(['success' => true, 'message' => 'Team deleted successfully.']);
    } else {
        jsonResponse(['success' => false, 'message' => 'Failed to delete team.'], 500);
    }
}

// --- FALLBACK UNRECOGNIZED ACTION ---
else {
    jsonResponse(['success' => false, 'message' => 'Unrecognized team action.'], 400);
}
?>
