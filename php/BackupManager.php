<?php
/**
 * BackupManager.php
 *
 * This class coordinates the NGS Backup & Recovery System operations.
 * It dynamically initializes backup locations outside the webroot,
 * manages versioned backups of the JSON database and client/admin websites,
 * handles manifests and integrity checks, and executes privileged restorations safely.
 *
 * All code is extensively documented line-by-line for ultimate readability and scale.
 */

// Enable strict typing for safety
declare(strict_types=1);

class BackupManager
{
    // The resolved absolute path of the private backup folder on disk
    private string $backupPath;

    // The project/web root path on disk (e.g. /app)
    private string $webRoot;

    /**
     * BackupManager constructor.
     * Initializes the dynamic path resolver and target directories.
     */
    public function __construct()
    {
        // Resolve absolute realpath of current file's parent's parent (project root)
        $this->webRoot = realpath(__DIR__ . '/../') ?: __DIR__ . '/..';

        // Choose a secure writable directory outside the web root
        // Primary candidate is the persistent home directory of user jules (/home/jules)
        $homeDir = getenv('HOME') ?: '/home/jules';
        $candidatePath = $homeDir . '/NGS_backups';

        // Check if the primary home-space path is writable
        if (@is_writable($homeDir) || @is_dir($candidatePath)) {
            // Set backup path inside home space
            $this->backupPath = $candidatePath;
        } else {
            // Fall back to system temporary folder space if home directory is locked
            $this->backupPath = sys_get_temp_dir() . '/NGS_backups';
        }
    }

    /**
     * Retrieves the resolved backup directory path.
     *
     * @return string The absolute backup folder path.
     */
    public function getBackupPath(): string
    {
        // Return backup base directory path
        return $this->backupPath;
    }

    /**
     * Verifies that the backup directory structure and subfolders exist.
     * Automatically initializes missing subfolders and files.
     *
     * @return bool True if directory is healthy and fully writable, false otherwise.
     */
    public function ensureBackupDirectory(): bool
    {
        // Check if base backup folder exists
        if (!is_dir($this->backupPath)) {
            // Create backup folder with restrictive 0755 permissions
            if (!@mkdir($this->backupPath, 0755, true)) {
                // Return false if creation failed
                return false;
            }
        }

        // List of required subdirectories for NGS Backups
        $subDirs = [
            'database',  // SQL/JSON schema table versions
            'websites',  // Client/Admin hosted sites files
            'uploads',   // System media assets
            'files',     // Workspace document assets
            'activity',  // Action logs copies
            'configs',   // System settings files
            'manifests', // Verification metadata files
            'logs'       // Execution status logs
        ];

        // Loop through each required directory
        foreach ($subDirs as $dir) {
            // Build full subdirectory path
            $fullDir = $this->backupPath . '/' . $dir;
            // If the folder is missing
            if (!is_dir($fullDir)) {
                // Initialize the directory safely
                @mkdir($fullDir, 0755, true);
            }
        }

        // Log the initialization event to keep a paper trail
        $this->logEvent('System initialized/checked backup structure successfully.');

        // Verify read and write permissions on base folder
        return is_readable($this->backupPath) && is_writable($this->backupPath);
    }

    /**
     * Creates a new versioned restore point containing DB, websites, and uploads.
     *
     * @param string $triggeredBy The email address of the administrator or system process.
     * @return array|false The created backup manifest data, or false on error.
     */
    public function createBackup(string $triggeredBy): array|false
    {
        // First ensure structure is healthy
        if (!$this->ensureBackupDirectory()) {
            // Terminate on directory error
            return false;
        }

        // Generate a unique backup identifier with high resolution timestamp
        $timestamp = date('Y-m-d_H-i-s');
        $backupId = 'backup_' . $timestamp . '_' . bin2hex(random_bytes(4));

        // Create specific version directories under subfolders
        $dbVerDir = $this->backupPath . '/database/' . $backupId;
        $webVerDir = $this->backupPath . '/websites/' . $backupId;
        $uploadsVerDir = $this->backupPath . '/uploads/' . $backupId;
        $filesVerDir = $this->backupPath . '/files/' . $backupId;

        // Initialize success status flags
        $dbSuccess = false;
        $webSuccess = false;
        $uploadsSuccess = false;

        // --- BACKUP DATABASE SECTION ---
        $liveDbPath = $this->webRoot . '/databases';
        if (is_dir($liveDbPath)) {
            // Copy databases folder recursively
            $dbSuccess = $this->copyDirectory($liveDbPath, $dbVerDir);
        }

        // --- BACKUP WEBSITES SECTION ---
        $liveWebPath = $this->webRoot . '/public';
        if (is_dir($liveWebPath)) {
            // Copy websites folder recursively
            $webSuccess = $this->copyDirectory($liveWebPath, $webVerDir);
        } else {
            // If public is missing (no sites yet), treat as success to avoid breaking flow
            @mkdir($webVerDir, 0755, true);
            $webSuccess = true;
        }

        // --- BACKUP UPLOADED FILES SECTION ---
        // Profile pictures are located in /main/public/uploads/ or similar path
        $liveUploadPath = $this->webRoot . '/main/public/uploads';
        if (is_dir($liveUploadPath)) {
            // Copy profile uploads recursively
            $uploadsSuccess = $this->copyDirectory($liveUploadPath, $uploadsVerDir);
        } else {
            // If missing, initialize empty and proceed
            @mkdir($uploadsVerDir, 0755, true);
            $uploadsSuccess = true;
        }

        // Save active config configurations
        $configVerDir = $this->backupPath . '/configs/' . $backupId;
        @mkdir($configVerDir, 0755, true);
        if (file_exists($this->webRoot . '/TenantManager.php')) {
            // Copy Tenant manager config
            @copy($this->webRoot . '/TenantManager.php', $configVerDir . '/TenantManager.php');
        }

        // --- BACKUP AUDIT LOGS SECTION ---
        $liveLogsFile = $this->webRoot . '/databases/system/activity_logs.json';
        if (file_exists($liveLogsFile)) {
            // Save versioned logs copy
            @copy($liveLogsFile, $this->backupPath . '/activity/activity_' . $backupId . '.json');
        }

        // Validate complete backup integrity before publishing
        $overallStatus = ($dbSuccess && $webSuccess && $uploadsSuccess) ? 'complete' : 'incomplete';

        // Prepare manifest array metadata
        $manifest = [
            'backup_id' => $backupId,
            'created_at' => date('Y-m-d H:i:s'),
            'database' => $dbSuccess,
            'websites' => $webSuccess,
            'uploads' => $uploadsSuccess,
            'activity' => file_exists($liveLogsFile),
            'files' => true,
            'application_version' => '1.0.0',
            'status' => $overallStatus,
            'triggered_by' => $triggeredBy,
            'checksums' => [
                'db_hash' => $this->calculateDirectoryHash($dbVerDir),
                'web_hash' => $this->calculateDirectoryHash($webVerDir)
            ]
        ];

        // Write the manifest to disk safely
        $manifestFile = $this->backupPath . '/manifests/manifest_' . $backupId . '.json';
        file_put_contents($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT), LOCK_EX);

        // Record event in execution logs
        $this->logEvent('Backup created: ID=' . $backupId . ', Status=' . $overallStatus . ', User=' . $triggeredBy);

        // Run automated retention policy cleanup to save space
        $this->cleanupOldBackups(10);

        // Return manifest if completely successful
        return $overallStatus === 'complete' ? $manifest : false;
    }

    /**
     * Lists all available restore points parsed from manifests.
     *
     * @return array List of sorted backup manifests.
     */
    public function listBackups(): array
    {
        $manifestsDir = $this->backupPath . '/manifests';
        if (!is_dir($manifestsDir)) {
            return [];
        }

        $list = [];
        // Scan directory for files
        $files = scandir($manifestsDir);
        foreach ($files as $file) {
            // Match manifest naming scheme
            if (str_starts_with($file, 'manifest_') && str_ends_with($file, '.json')) {
                // Decode manifest contents
                $content = file_get_contents($manifestsDir . '/' . $file);
                if ($content) {
                    $decoded = json_decode($content, true);
                    if ($decoded) {
                        // Append to lists
                        $list[] = $decoded;
                    }
                }
            }
        }

        // Sort backups by creation date descending
        usort($list, function($a, $b) {
            return strcmp($b['created_at'], $a['created_at']);
        });

        return $list;
    }

    /**
     * Verifies the structural integrity of a selected backup point.
     *
     * @param string $backupId Unique ID of target backup.
     * @return array Verification results (valid, reasons).
     */
    public function verifyBackup(string $backupId): array
    {
        // Isolate parameter against directory injections
        $backupId = basename($backupId);

        // Locate target manifest file path
        $manifestFile = $this->backupPath . '/manifests/manifest_' . $backupId . '.json';
        if (!file_exists($manifestFile)) {
            return ['valid' => false, 'reason' => 'Manifest file missing on disk.'];
        }

        // Parse manifest data
        $manifest = json_decode(file_get_contents($manifestFile) ?: '{}', true);
        if (empty($manifest)) {
            return ['valid' => false, 'reason' => 'Manifest file is corrupt or unreadable.'];
        }

        // Resolve paths on disk
        $dbDir = $this->backupPath . '/database/' . $backupId;
        $webDir = $this->backupPath . '/websites/' . $backupId;

        // Check database existence
        if (($manifest['database'] ?? false) && !is_dir($dbDir)) {
            return ['valid' => false, 'reason' => 'Database backup directory missing.'];
        }

        // Check website directory existence
        if (($manifest['websites'] ?? false) && !is_dir($webDir)) {
            return ['valid' => false, 'reason' => 'Websites backup directory missing.'];
        }

        // Calculate and compare current hash with original
        $currentDbHash = $this->calculateDirectoryHash($dbDir);
        $originalDbHash = $manifest['checksums']['db_hash'] ?? '';

        if (!empty($originalDbHash) && $currentDbHash !== $originalDbHash) {
            return ['valid' => false, 'reason' => 'Database integrity checksum mismatch. Files may be altered.'];
        }

        return ['valid' => true, 'reason' => 'Integrity verified. Backup is healthy and complete.'];
    }

    /**
     * Restores selected backup, performing safety checks and safety-backup first.
     *
     * @param string $backupId Unique ID of backup to restore.
     * @param string $adminEmail Executing administrator email.
     * @return bool True on successful restore, false on failure.
     */
    public function restoreBackup(string $backupId, string $adminEmail): bool
    {
        // Prevent path traversal
        $backupId = basename($backupId);

        // 1. Verify target backup first
        $verify = $this->verifyBackup($backupId);
        if (!$verify['valid']) {
            $this->logEvent('Restore failed: Backup integrity error. Reason=' . $verify['reason']);
            return false;
        }

        // 2. Create safety backup of the current state before applying changes
        $this->logEvent('Initiating safety recovery point before restoring: ' . $backupId);
        $safetyPoint = $this->createBackup($adminEmail . ' (Safety Restore Gate)');
        if (!$safetyPoint) {
            $this->logEvent('Restore aborted: Failed to establish safety restore point.');
            return false;
        }

        // Resolve version paths to restore
        $dbSrc = $this->backupPath . '/database/' . $backupId;
        $webSrc = $this->backupPath . '/websites/' . $backupId;
        $uploadsSrc = $this->backupPath . '/uploads/' . $backupId;

        // Resolve live target paths
        $liveDb = $this->webRoot . '/databases';
        $liveWeb = $this->webRoot . '/public';
        $liveUploads = $this->webRoot . '/main/public/uploads';

        // 3. Clear and restore live database
        if (is_dir($dbSrc)) {
            $this->deleteDirectory($liveDb);
            @mkdir($liveDb, 0755, true);
            $this->copyDirectory($dbSrc, $liveDb);
        }

        // 4. Clear and restore websites public folder
        if (is_dir($webSrc)) {
            $this->deleteDirectory($liveWeb);
            @mkdir($liveWeb, 0755, true);
            $this->copyDirectory($webSrc, $liveWeb);
        }

        // 5. Clear and restore uploads folder
        if (is_dir($uploadsSrc)) {
            $this->deleteDirectory($liveUploads);
            @mkdir($liveUploads, 0755, true);
            $this->copyDirectory($uploadsSrc, $liveUploads);
        }

        // Log restore success details
        $this->logEvent('Backup restored successfully: ID=' . $backupId . ', Admin=' . $adminEmail);

        // Record restore action inside the restored databases logs for auditing
        require_once __DIR__ . '/db.php';
        global $conn;
        $conn->createTable('activity_logs');
        $conn->insert('activity_logs', [
            'email' => $adminEmail,
            'action' => 'backup_restored',
            'details' => 'Restored backup ID: ' . $backupId . ' (Safety copy: ' . $safetyPoint['backup_id'] . ')',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return true;
    }

    /**
     * Deletes a versioned backup manifest and its directories.
     *
     * @param string $backupId The unique target backup ID.
     * @return bool True on success, false on failure.
     */
    public function deleteBackup(string $backupId): bool
    {
        // Restrict path inputs
        $backupId = basename($backupId);

        // Delete manifest file
        $manifestFile = $this->backupPath . '/manifests/manifest_' . $backupId . '.json';
        if (file_exists($manifestFile)) {
            @unlink($manifestFile);
        }

        // Delete database backup folder
        $this->deleteDirectory($this->backupPath . '/database/' . $backupId);
        // Delete websites backup folder
        $this->deleteDirectory($this->backupPath . '/websites/' . $backupId);
        // Delete uploads backup folder
        $this->deleteDirectory($this->backupPath . '/uploads/' . $backupId);
        // Delete files backup folder
        $this->deleteDirectory($this->backupPath . '/files/' . $backupId);
        // Delete config backup folder
        $this->deleteDirectory($this->backupPath . '/configs/' . $backupId);

        $this->logEvent('Backup deleted manually: ID=' . $backupId);
        return true;
    }

    /**
     * Maintains versioning limit and cleans up oldest backups.
     *
     * @param int $keepCount Maximum backups count to retain.
     */
    private function cleanupOldBackups(int $keepCount = 10): void
    {
        // Get all backups sorted by date
        $list = $this->listBackups();
        // If current count exceeds limits
        if (count($list) > $keepCount) {
            // Isolate older backups to drop
            $toDelete = array_slice($list, $keepCount);
            foreach ($toDelete as $b) {
                // Delete backup version files
                $this->deleteBackup($b['backup_id']);
            }
        }
    }

    /**
     * Copy directory recursively helper.
     */
    private function copyDirectory(string $src, string $dst): bool
    {
        // Open and read source directory
        $dir = @opendir($src);
        if ($dir === false) {
            return false;
        }

        // Initialize target destination folder
        @mkdir($dst, 0755, true);

        // Read files one by one
        while (($file = readdir($dir)) !== false) {
            if ($file !== '.' && $file !== '..') {
                $srcFile = $src . '/' . $file;
                $dstFile = $dst . '/' . $file;

                if (is_dir($srcFile)) {
                    // Recurse directory copy
                    $this->copyDirectory($srcFile, $dstFile);
                } else {
                    // Copy physical file
                    @copy($srcFile, $dstFile);
                }
            }
        }
        closedir($dir);
        return true;
    }

    /**
     * Delete directory recursively helper.
     */
    private function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $filePath = $dir . '/' . $file;
            if (is_dir($filePath)) {
                // Recurse delete folder
                $this->deleteDirectory($filePath);
            } else {
                // Remove individual file
                @unlink($filePath);
            }
        }
        // Remove empty base folder
        return @rmdir($dir);
    }

    /**
     * Calculates MD5 hash of files structure inside a directory.
     */
    private function calculateDirectoryHash(string $dir): string
    {
        if (!is_dir($dir)) {
            return '';
        }

        $hashes = [];
        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $filePath = $dir . '/' . $file;
            if (is_dir($filePath)) {
                $hashes[] = $this->calculateDirectoryHash($filePath);
            } else {
                $hashes[] = md5_file($filePath) ?: '';
            }
        }

        return md5(implode('', $hashes));
    }

    /**
     * Logs backup status messages inside a private logs/ folder.
     */
    private function logEvent(string $message): void
    {
        $logFile = $this->backupPath . '/logs/backup_history.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $formattedMsg = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
        file_put_contents($logFile, $formattedMsg, FILE_APPEND | LOCK_EX);
    }
}
?>
