<?php
/**
 * test_backup.php
 *
 * Automated verification script for the NGS Backup & Recovery System.
 * Asserts structural creation, versioning, integrity checksum matching,
 * and safety-backup gates.
 *
 * All code is extensively documented line-by-line for ultimate readability and scale.
 */

// Enable strict typing for safety
declare(strict_types=1);

// Require central database configuration
require_once __DIR__ . '/php/db.php';
// Require BackupManager class
require_once __DIR__ . '/php/BackupManager.php';

echo "========================================================\n";
echo "       NGS BACKUP & RECOVERY AUTOMATED TEST SUITE       \n";
echo "========================================================\n\n";

// Instantiate BackupManager
$bm = new BackupManager();

// --- TEST 1: Path Resolution ---
$path = $bm->getBackupPath();
echo "Resolved Backup Path: " . $path . "\n";
// Assert path is not empty and is outside the webroot
$isOutsideWebroot = !str_starts_with($path, realpath(__DIR__));
if ($isOutsideWebroot) {
    echo "✅ PASS: Backup path is successfully resolved outside the web root directory.\n";
} else {
    echo "❌ FAIL: Backup path lies inside the web root. Violates security boundary rule!\n";
    exit(1);
}

// --- TEST 2: Automatic Initialization ---
$initResult = $bm->ensureBackupDirectory();
if ($initResult) {
    echo "✅ PASS: Backup directory structures initialized and confirmed writable.\n";
} else {
    echo "❌ FAIL: Backup directory initialization failed.\n";
    exit(1);
}

// Check subdirectories
$requiredSubdirs = ['database', 'websites', 'uploads', 'files', 'activity', 'configs', 'manifests', 'logs'];
$subdirsHealthy = true;
foreach ($requiredSubdirs as $subdir) {
    $fullSub = $path . '/' . $subdir;
    if (!is_dir($fullSub)) {
        $subdirsHealthy = false;
        echo "❌ FAIL: Missing subdirectory: " . $subdir . "\n";
    }
}
if ($subdirsHealthy) {
    echo "✅ PASS: All required subdirectories exist and are structural.\n";
} else {
    exit(1);
}

// --- TEST 3: Create Versioned Backup ---
$manifest = $bm->createBackup('test_runner@nodexplatform.com.ng');
if ($manifest && $manifest['status'] === 'complete') {
    echo "✅ PASS: Manual versioned backup successfully created. ID: " . $manifest['backup_id'] . "\n";
} else {
    echo "❌ FAIL: Manual backup creation failed.\n";
    exit(1);
}

// --- TEST 4: Version List Verification ---
$backupsList = $bm->listBackups();
if (count($backupsList) > 0) {
    echo "✅ PASS: Dynamic backups listing is non-empty and successfully parsed from manifests.\n";
} else {
    echo "❌ FAIL: Backups listing returned empty.\n";
    exit(1);
}

// --- TEST 5: Integrity Verification ---
$verifyResult = $bm->verifyBackup($manifest['backup_id']);
if ($verifyResult['valid']) {
    echo "✅ PASS: Backup integrity verification succeeded. Status: " . $verifyResult['reason'] . "\n";
} else {
    echo "❌ FAIL: Backup integrity verification failed. Reason: " . $verifyResult['reason'] . "\n";
    exit(1);
}

// --- TEST 6: Safety Backup & Restore Gate ---
// Let's verify that a safety backup is automatically generated before restoring
$originalCount = count($bm->listBackups());
// Execute restoration
$restoreResult = $bm->restoreBackup($manifest['backup_id'], 'admin_restore_test@nodexplatform.com.ng');
$newCount = count($bm->listBackups());

// A safety backup should have been automatically created, increasing our backup count by 1 (or matching the offset)
if ($restoreResult && $newCount > $originalCount) {
    echo "✅ PASS: Privileged restoration executed successfully. Automatic safety backup was forced first.\n";
} else {
    echo "❌ FAIL: Restoration flow failed or did not establish safety backup first.\n";
    exit(1);
}

// --- TEST 7: Cleanup & Deletion ---
$cleanupSuccess = true;
$allBackups = $bm->listBackups();
foreach ($allBackups as $b) {
    if (!$bm->deleteBackup($b['backup_id'])) {
        $cleanupSuccess = false;
    }
}
if ($cleanupSuccess && count($bm->listBackups()) === 0) {
    echo "✅ PASS: Cleanup and manual deletions of versioned folders completed successfully.\n";
} else {
    echo "❌ FAIL: Deletion of backup versions failed.\n";
    exit(1);
}

echo "\n========================================================\n";
echo "ALL RECOVERY SYSTEM TESTS PASSED SUCCESSFULLY! (7/7 Passes)\n";
echo "========================================================\n";
exit(0);
?>
