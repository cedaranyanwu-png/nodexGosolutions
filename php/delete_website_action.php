<?php
/**
 * delete_website_action.php
 *
 * Dedicated backend controller to handle secure website deletions and suspensions.
 * Standard users can delete their own websites, while administrators can delete
 * or suspend any website on the hosting platform.
 *
 * All code is extensively documented line-by-line for ultimate readability and scale.
 */

// Enable strict typing for safety
declare(strict_types=1);

// Require central database configuration and security helpers
require_once __DIR__ . '/db.php';

// Instantiate secure session configurations
secureSession();

// Restrict to authenticated sessions
if (!isset($_SESSION['email'])) {
    // Return unauthorized JSON error if no session exists
    jsonResponse(['success' => false, 'message' => 'Unauthorized access. Please log in.'], 401);
}

// Restrict to POST actions
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Return method not allowed
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

// Fetch current active user details
$user = $conn->selectOne('users', ['email' => $_SESSION['email']]);
if (!$user) {
    // Return not found if user record is missing
    jsonResponse(['success' => false, 'message' => 'User account not found.'], 404);
}

// Extract payload action and website ID parameters
$action = cleanInput($_POST['action'] ?? '');
$websiteId = (int)($_POST['website_id'] ?? 0);

if ($websiteId <= 0) {
    // Return bad request error if website ID is missing
    jsonResponse(['success' => false, 'message' => 'Valid website ID is required.'], 400);
}

// Ensure websites table is initialized
$conn->createTable('websites');
// Retrieve target website details from database
$site = $conn->selectOne('websites', ['id' => $websiteId]);

if (!$site) {
    // Return not found error if the website record is missing
    jsonResponse(['success' => false, 'message' => 'Website project not found.'], 404);
}

// Resolve user roles and ownership parameters
$userRole = strtolower((string)($user['role'] ?? 'tenant'));
$isAdmin = ($userRole === 'admin' || $userRole === 'super admin' || $userRole === 'manager');
$isOwner = ((int)($site['user_id'] ?? 0) === (int)$user['id']);

// --- ACTION 1: SECURE WEBSITE DELETION ---
if ($action === 'delete_website') {
    // Deletion is restricted to the site owner or administrative accounts
    if (!$isOwner && !$isAdmin) {
        // Return forbidden response if not authorized
        jsonResponse(['success' => false, 'message' => 'Forbidden. You do not own this website project.'], 403);
    }

    // Resolve target website subdomain folder name
    $subdomain = basename((string)($site['subdomain'] ?? ''));
    if (empty($subdomain)) {
        // Return error if subdomain metadata is missing
        jsonResponse(['success' => false, 'message' => 'Corrupt website subdomain metadata.'], 422);
    }

    // Resolve absolute path to the physical directory on disk
    $publicDir = realpath(__DIR__ . '/../public');
    if ($publicDir === false) {
        $publicDir = __DIR__ . '/../public';
    }
    $targetDir = $publicDir . '/' . $subdomain;

    // Boundary Traversal Protection: Ensure target directory resolves strictly inside public/ folder
    $realPublicDir = realpath($publicDir);
    if ($realPublicDir !== false) {
        if (file_exists($targetDir)) {
            $realTargetDir = realpath($targetDir);
            // Verify path bounds to prevent directory injections
            if ($realTargetDir === false || !str_starts_with($realTargetDir, $realPublicDir)) {
                jsonResponse(['success' => false, 'message' => 'Directory traversal boundary violation.'], 403);
            }
        }
    }

    // Helper to recursively delete physical files and directories on disk
    $deleteDirHelper = function(string $dir) use (&$deleteDirHelper): bool {
        if (!is_dir($dir)) {
            return false;
        }
        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $filePath = $dir . '/' . $file;
            if (is_dir($filePath)) {
                // Recurse delete folder
                $deleteDirHelper($filePath);
            } else {
                // Unlink file
                @unlink($filePath);
            }
        }
        // Remove empty base directory
        return @rmdir($dir);
    };

    // Remove the website files from disk if directory exists
    if (is_dir($targetDir)) {
        $deleteDirHelper($targetDir);
    }

    // Delete matching traffic metrics from database
    $conn->createTable('traffic');
    $conn->delete('traffic', ['website_id' => $websiteId]);

    // Delete website project metadata row from websites JSON database
    $conn->delete('websites', ['id' => $websiteId]);

    // Log the operation in system activities audits
    $conn->createTable('activity_logs');
    $conn->insert('activity_logs', [
        'email' => $user['email'],
        'action' => 'website_deleted',
        'details' => 'Permanently deleted website project: ' . ($site['name'] ?? '') . ' (' . $subdomain . ')',
        'created_at' => date('Y-m-d H:i:s')
    ]);

    // Return successful deletion response
    jsonResponse(['success' => true, 'message' => "Website '" . ($site['name'] ?? '') . "' and all hosted file structures have been permanently deleted."]);
}

// --- ACTION 2: ADMIN WEBSITE SUSPENSION TOGGLE ---
elseif ($action === 'toggle_suspension') {
    // Only administrative levels can suspend websites
    if (!$isAdmin) {
        // Return forbidden response if not admin
        jsonResponse(['success' => false, 'message' => 'Forbidden. Privileged administrative action only.'], 403);
    }

    // Isolate current suspension state or default to 0
    $currentSuspended = (int)($site['is_suspended'] ?? 0);
    // Toggle suspension state
    $newSuspended = ($currentSuspended === 1) ? 0 : 1;

    // Update websites table record on database
    $conn->update('websites', ['is_suspended' => $newSuspended], ['id' => $websiteId]);

    // Format logs details message
    $actionMsg = ($newSuspended === 1) ? 'Suspended' : 'Unsuspended';

    // Log suspension action
    $conn->createTable('activity_logs');
    $conn->insert('activity_logs', [
        'email' => $user['email'],
        'action' => 'website_suspension_toggled',
        'details' => $actionMsg . ' website project: ' . ($site['name'] ?? '') . ' (' . ($site['subdomain'] ?? '') . ')',
        'created_at' => date('Y-m-d H:i:s')
    ]);

    // Return successful response
    jsonResponse(['success' => true, 'message' => "Website '" . ($site['name'] ?? '') . "' was successfully " . strtolower($actionMsg) . "."]);
}

// --- FALLBACK UNRECOGNIZED ACTION ---
else {
    // Return unrecognized action error
    jsonResponse(['success' => false, 'message' => 'Unrecognized website action.'], 400);
}
?>
