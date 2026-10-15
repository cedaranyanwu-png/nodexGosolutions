<?php
/**
 * admin_backup_action.php
 *
 * Dedicated administrative API controller that handles NGS Backup and Recovery AJAX actions.
 * Supports secure operations to list, create, verify, delete, and explicitly restore recovery points.
 * Guarded strictly by admin sessions and granular RBAC security permission checks.
 *
 * All code is extensively documented line-by-line for ultimate readability and scale.
 */

// Enable strict typing for safety
declare(strict_types=1);

// Require central database configuration and security helpers
require_once __DIR__ . '/db.php';
// Require the BackupManager backend class
require_once __DIR__ . '/BackupManager.php';

// Instantiate secure session configurations
secureSession();

// Restrict access to authenticated administrator accounts only
$activeRole = strtolower((string)($_SESSION['role'] ?? ''));
if ($activeRole !== 'admin' && $activeRole !== 'super admin') {
    // Return unauthorized JSON response if not administrator
    jsonResponse(['success' => false, 'message' => 'Unauthorized. Privileged access only.'], 401);
}

// Check that the request is a POST action
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Return invalid method error
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

// Extract target action from request
$action = cleanInput($_POST['action'] ?? '');
// Instantiate BackupManager
$backupManager = new BackupManager();

// --- ACTION 1: LIST RECOVERY POINTS ---
if ($action === 'list_backups') {
    // Verify that the user possesses permission to view backup streams
    if (!checkAdminPermission('backups.view')) {
        // Return forbidden response
        jsonResponse(['success' => false, 'message' => 'Forbidden. Missing backups.view permission.'], 403);
    }

    // Retrieve all parsed manifest details from the NGS backup folder
    $backups = $backupManager->listBackups();
    // Return success response with dynamic backups list
    jsonResponse(['success' => true, 'backups' => $backups]);
}

// --- ACTION 2: TRIGGER MANUAL BACKUP ---
elseif ($action === 'create_backup') {
    // Verify permission to create new backups
    if (!checkAdminPermission('backups.create')) {
        // Return forbidden response
        jsonResponse(['success' => false, 'message' => 'Forbidden. Missing backups.create permission.'], 403);
    }

    // Fetch the email of the active administrator creating the backup
    $adminEmail = (string)$_SESSION['email'];
    // Trigger the dynamic backup routine
    $manifest = $backupManager->createBackup($adminEmail);

    if ($manifest) {
        // Log the event in system activity log database
        $conn->createTable('activity_logs');
        $conn->insert('activity_logs', [
            'email' => $adminEmail,
            'action' => 'backup_created',
            'details' => 'Created manual backup ID: ' . $manifest['backup_id'],
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // Return success response containing created manifest details
        jsonResponse(['success' => true, 'message' => 'Backup created successfully!', 'backup' => $manifest]);
    } else {
        // Return internal server error response on failure
        jsonResponse(['success' => false, 'message' => 'Backup failed. Please check filesystem write permissions.'], 500);
    }
}

// --- ACTION 3: VERIFY INTEGRITY CHECKSUM ---
elseif ($action === 'verify_backup') {
    // Verify permission to execute integrity tests
    if (!checkAdminPermission('backups.verify')) {
        // Return forbidden response
        jsonResponse(['success' => false, 'message' => 'Forbidden. Missing backups.verify permission.'], 403);
    }

    // Extract target backup ID safely
    $backupId = cleanInput($_POST['backup_id'] ?? '');
    if (empty($backupId)) {
        // Return bad request error
        jsonResponse(['success' => false, 'message' => 'Backup ID is required.'], 400);
    }

    // Execute backup checksum verification routine
    $result = $backupManager->verifyBackup($backupId);

    if ($result['valid']) {
        // Return verified response
        jsonResponse(['success' => true, 'message' => $result['reason']]);
    } else {
        // Return failed verification response
        jsonResponse(['success' => false, 'message' => $result['reason']], 422);
    }
}

// --- ACTION 4: DELETE VERSIONED RESTORE POINT ---
elseif ($action === 'delete_backup') {
    // Verify permission to delete backup points
    if (!checkAdminPermission('backups.delete')) {
        // Return forbidden response
        jsonResponse(['success' => false, 'message' => 'Forbidden. Missing backups.delete permission.'], 403);
    }

    // Extract backup ID safely
    $backupId = cleanInput($_POST['backup_id'] ?? '');
    if (empty($backupId)) {
        // Return bad request error
        jsonResponse(['success' => false, 'message' => 'Backup ID is required.'], 400);
    }

    // Execute deletion sequence
    if ($backupManager->deleteBackup($backupId)) {
        // Get administrator email
        $adminEmail = (string)$_SESSION['email'];
        // Log delete operation in activity database
        $conn->createTable('activity_logs');
        $conn->insert('activity_logs', [
            'email' => $adminEmail,
            'action' => 'backup_deleted',
            'details' => 'Deleted backup ID: ' . $backupId,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // Return success response
        jsonResponse(['success' => true, 'message' => 'Backup point deleted successfully.']);
    } else {
        // Return failure response
        jsonResponse(['success' => false, 'message' => 'Failed to delete backup point.'], 500);
    }
}

// --- ACTION 5: EXPLICIT RESTORE ---
elseif ($action === 'restore_backup') {
    // Verify permission to restore systems (highly privileged operation)
    if (!checkAdminPermission('backups.restore')) {
        // Return forbidden response
        jsonResponse(['success' => false, 'message' => 'Forbidden. Missing backups.restore permission.'], 403);
    }

    // Extract target backup ID
    $backupId = cleanInput($_POST['backup_id'] ?? '');
    if (empty($backupId)) {
        // Return bad request error
        jsonResponse(['success' => false, 'message' => 'Backup ID is required.'], 400);
    }

    // Fetch administrator email
    $adminEmail = (string)$_SESSION['email'];

    // Execute the complete restoration routine
    if ($backupManager->restoreBackup($backupId, $adminEmail)) {
        // Return successful restore response
        jsonResponse(['success' => true, 'message' => 'System successfully restored to point: ' . $backupId]);
    } else {
        // Return failure response
        jsonResponse(['success' => false, 'message' => 'System restore failed. Please verify manifest and checksums.'], 500);
    }
}

// --- FALLBACK UNRECOGNIZED ACTION ---
else {
    // Return unrecognized action error
    jsonResponse(['success' => false, 'message' => 'Unrecognized backup action.'], 400);
}
?>
