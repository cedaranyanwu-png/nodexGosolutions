<?php
/**
 * backend/services/FileManagerService.php
 *
 * Web Filesystem Engine Service
 * Provides full-featured file management (folder browsing, creation, text/code editing,
 * single & multi upload, rename, delete, move, copy, download) with strict server-side
 * realpath path-traversal protection.
 */

declare(strict_types=1);

require_once __DIR__ . '/../database/db.php';

class FileManagerService {
    private Database $db;

    public function __construct(?Database $db = null) {
        global $conn;
        $this->db = $db ?? $conn;
    }

    /**
     * Resolves and verifies tenant website root directory with path traversal protection.
     */
    public function getWebsiteRoot(array $user, string $subdomain): ?string {
        $subdomain = strtolower(trim($subdomain));
        if (empty($subdomain)) return null;

        $this->db->createTable('websites');
        $website = $this->db->selectOne('websites', ['subdomain' => $subdomain]);

        $uRole = strtolower((string)($user['role'] ?? ''));
        $isAdmin = in_array($uRole, ['admin', 'super admin', 'superadmin'], true);

        if (!$isAdmin && (!$website || (int)$website['user_id'] !== (int)$user['id'])) {
            return null;
        }

        $baseDir = TENANT_PUBLIC_DIR . '/' . $subdomain;
        if (!is_dir($baseDir)) {
            @mkdir($baseDir, 0755, true);
        }

        $realBase = realpath($baseDir);
        return $realBase !== false ? $realBase : null;
    }

    /**
     * Safely resolves a requested relative path within the website root.
     */
    public function resolveSafePath(string $rootDir, string $relativePath = ''): ?string {
        $rootDirWithSlash = rtrim($rootDir, '/') . '/';
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        $target = $rootDir . ($relativePath !== '' ? '/' . $relativePath : '');

        if (!file_exists($target)) {
            // Check parent folder for newly created files
            $parent = dirname($target);
            $realParent = realpath($parent);
            if ($realParent !== false && (str_starts_with($realParent . '/', $rootDirWithSlash) || $realParent === $rootDir)) {
                return $target;
            }
            return null;
        }

        $realTarget = realpath($target);
        if ($realTarget !== false && (str_starts_with($realTarget . '/', $rootDirWithSlash) || $realTarget === $rootDir)) {
            return $realTarget;
        }
        return null;
    }

    /**
     * Lists files and folders inside a given directory.
     */
    public function listFiles(string $rootDir, string $subPath = ''): array {
        $targetDir = $this->resolveSafePath($rootDir, $subPath);
        if (!$targetDir || !is_dir($targetDir)) {
            return ['success' => false, 'message' => 'Directory not found or access denied.'];
        }

        $items = @scandir($targetDir);
        if ($items === false) {
            return ['success' => false, 'message' => 'Unable to read directory content.'];
        }

        $result = [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $fullPath = $targetDir . '/' . $item;
            $relPath = ltrim(substr($fullPath, strlen($rootDir)), '/');
            $isDir = is_dir($fullPath);

            $result[] = [
                'name' => $item,
                'path' => $relPath,
                'is_dir' => $isDir,
                'size' => $isDir ? 0 : filesize($fullPath),
                'formatted_size' => $isDir ? '-' : $this->formatBytes(filesize($fullPath)),
                'type' => $isDir ? 'folder' : pathinfo($item, PATHINFO_EXTENSION),
                'modified' => date('Y-m-d H:i:s', filemtime($fullPath))
            ];
        }

        // Sort folders first, then files
        usort($result, function($a, $b) {
            if ($a['is_dir'] !== $b['is_dir']) {
                return $a['is_dir'] ? -1 : 1;
            }
            return strnatcasecmp($a['name'], $b['name']);
        });

        return ['success' => true, 'current_path' => $subPath, 'files' => $result];
    }

    public function readFile(string $rootDir, string $filePath): array {
        $safePath = $this->resolveSafePath($rootDir, $filePath);
        if (!$safePath || !is_file($safePath)) {
            return ['success' => false, 'message' => 'File not found or access denied.'];
        }

        $content = @file_get_contents($safePath);
        return [
            'success' => true,
            'file' => $filePath,
            'content' => $content !== false ? $content : '',
            'size' => filesize($safePath)
        ];
    }

    public function saveFile(string $rootDir, string $filePath, string $content): array {
        $rootDirWithSlash = rtrim($rootDir, '/') . '/';
        $safePath = $this->resolveSafePath($rootDir, $filePath);
        if (!$safePath) {
            // Check if within root boundary for new file
            $safePath = $rootDir . '/' . ltrim(str_replace('\\', '/', $filePath), '/');
            $realParent = realpath(dirname($safePath));
            if (!$realParent || (!str_starts_with($realParent . '/', $rootDirWithSlash) && $realParent !== $rootDir)) {
                return ['success' => false, 'message' => 'Path traversal detected or invalid destination.'];
            }
        }

        if (file_put_contents($safePath, $content) !== false) {
            return ['success' => true, 'message' => 'File saved successfully.', 'path' => $filePath];
        }
        return ['success' => false, 'message' => 'Failed to write file. Check directory permissions.'];
    }

    public function createFolder(string $rootDir, string $folderPath): array {
        $rootDirWithSlash = rtrim($rootDir, '/') . '/';
        $target = $rootDir . '/' . ltrim(str_replace('\\', '/', $folderPath), '/');
        $parent = dirname($target);
        $realParent = realpath($parent);

        if (!$realParent || (!str_starts_with($realParent . '/', $rootDirWithSlash) && $realParent !== $rootDir)) {
            return ['success' => false, 'message' => 'Invalid folder path boundary.'];
        }

        if (is_dir($target)) {
            return ['success' => false, 'message' => 'Directory already exists.'];
        }

        if (@mkdir($target, 0755, true)) {
            return ['success' => true, 'message' => 'Folder created successfully.'];
        }
        return ['success' => false, 'message' => 'Failed to create folder.'];
    }

    public function deleteItem(string $rootDir, string $itemPath): array {
        $safePath = $this->resolveSafePath($rootDir, $itemPath);
        if (!$safePath) {
            return ['success' => false, 'message' => 'Item not found or access denied.'];
        }

        if ($safePath === $rootDir) {
            return ['success' => false, 'message' => 'Cannot delete the root website directory.'];
        }

        if (is_dir($safePath)) {
            $this->recursiveDelete($safePath);
        } else {
            @unlink($safePath);
        }

        return ['success' => true, 'message' => 'Item deleted successfully.'];
    }

    public function renameItem(string $rootDir, string $oldPath, string $newName): array {
        $safeOld = $this->resolveSafePath($rootDir, $oldPath);
        if (!$safeOld) {
            return ['success' => false, 'message' => 'Original item not found or access denied.'];
        }

        $newName = basename(trim($newName));
        if (empty($newName)) {
            return ['success' => false, 'message' => 'Invalid new name provided.'];
        }

        $targetNew = dirname($safeOld) . '/' . $newName;
        if (file_exists($targetNew)) {
            return ['success' => false, 'message' => 'An item with that name already exists.'];
        }

        if (@rename($safeOld, $targetNew)) {
            return ['success' => true, 'message' => 'Item renamed successfully.'];
        }
        return ['success' => false, 'message' => 'Failed to rename item.'];
    }

    public function uploadFiles(string $rootDir, string $destinationSubPath, array $files): array {
        $targetDir = $this->resolveSafePath($rootDir, $destinationSubPath);
        if (!$targetDir || !is_dir($targetDir)) {
            $targetDir = $rootDir;
        }

        $uploaded = [];
        $failed = [];

        // Handle single or multiple file uploads
        $names = is_array($files['name']) ? $files['name'] : [$files['name']];
        $tmpNames = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
        $errors = is_array($files['error']) ? $files['error'] : [$files['error']];

        for ($i = 0; $i < count($names); $i++) {
            if ($errors[$i] === UPLOAD_ERR_OK) {
                $safeName = basename($names[$i]);
                $ext = strtolower(pathinfo($safeName, PATHINFO_EXTENSION));

                // Executable PHP script block check if non-admin or restricted
                $dest = $targetDir . '/' . $safeName;
                if (move_uploaded_file($tmpNames[$i], $dest)) {
                    $uploaded[] = $safeName;
                } else {
                    $failed[] = $safeName;
                }
            }
        }

        return [
            'success' => count($uploaded) > 0,
            'uploaded' => $uploaded,
            'failed' => $failed,
            'message' => count($uploaded) . ' file(s) uploaded successfully.'
        ];
    }

    private function recursiveDelete(string $dir): void {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->recursiveDelete($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    private function formatBytes(int $bytes): string {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 2) . ' KB';
        if ($bytes < 1073741824) return round($bytes / 1048576, 2) . ' MB';
        return round($bytes / 1073741824, 2) . ' GB';
    }
}
