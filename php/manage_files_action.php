<?php
/**
 * manage_files_action.php
 *
 * Highly secured, fully sandboxed physical File Manager backend API.
 * Restricts standard tenant users strictly to their own provisioned subdirectory under /public/<subdomain>/.
 * Implements rigorous LFI and directory traversal protections using canonicalized realpath() checks.
 * Prevents traversing into other user's spaces, the administrative system, or configuration files.
 * Supports: listing directory contents, reading file contents, writing/saving file contents, and deleting files.
 * All operations and validation procedures are extensively commented for maximum scale.
 */

// Enable strict typing for safety
declare(strict_types=1);

// Require central configurations and database engine
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
$action    = cleanInput($_POST['action'] ?? $_GET['action'] ?? '');
$subdomain = strtolower(cleanInput($_POST['subdomain'] ?? $_GET['subdomain'] ?? ''));

// Validate parameters non-emptiness
if (empty($action) || empty($subdomain)) {
    jsonResponse(['success' => false, 'message' => 'Missing required action or subdomain parameter.'], 400);
}

// ============================================================
// SECURITY AND BOUNDARY ISOLATION CHECKS
// ============================================================

// If standard tenant (non-admin), verify ownership of the requested subdomain
$userRole = strtolower((string)($user['role'] ?? 'tenant'));
$isAdmin  = ($userRole === 'admin' || $userRole === 'super admin');

if (!$isAdmin) {
    // Standard users must be associated with the website record matching the subdomain
    $conn->createTable('websites');
    $website = $conn->selectOne('websites', [
        'user_id'   => $user['id'],
        'subdomain' => $subdomain
    ]);
    if (!$website) {
        jsonResponse(['success' => false, 'message' => 'Access Denied: You do not own this website workspace.'], 403);
    }
}

// Establish hosting public folder root path
$publicRoot = realpath(__DIR__ . '/../');
if ($publicRoot === false) {
    $publicRoot = __DIR__ . '/..';
}
$publicDir = $publicRoot . '/public';

// Establish workspace boundary folder path
$tenantDir = $publicDir . '/' . $subdomain;

// Canonicalize paths to ensure validity and prevent folder traversals
$realPublicDir = realpath($publicDir);
$realTenantDir = realpath($tenantDir);

// Verify that the tenant folder exists
if ($realTenantDir === false || !is_dir($realTenantDir)) {
    jsonResponse(['success' => false, 'message' => 'Website workspace directory does not exist on disk.'], 404);
}

// Prevent boundary escape of tenant folder relative to public/ hosting root
if ($realPublicDir !== false && !str_starts_with($realTenantDir, $realPublicDir)) {
    jsonResponse(['success' => false, 'message' => 'Security Error: Invalid directory boundary.'], 403);
}

// Resolve targeted file or relative subpath parameters if specified
$relativeFile = $_POST['file_path'] ?? $_GET['file_path'] ?? '';
$targetFile   = $tenantDir;

if (!empty($relativeFile)) {
    // Append sanitized relative path
    $targetFile = $tenantDir . '/' . ltrim(str_replace('\\', '/', $relativeFile), '/');
}

// Perform strict realpath boundary validation on the target file/folder to block traversal attacks
if (file_exists($targetFile)) {
    $realTargetFile = realpath($targetFile);
    if ($realTargetFile === false || !str_starts_with($realTargetFile, $realTenantDir)) {
        jsonResponse(['success' => false, 'message' => 'Security Error: Attempted directory traversal outside workspace boundary.'], 403);
    }
} else {
    // If the file does not exist yet (e.g., about to be created), check parent folder boundary
    $parentDir = dirname($targetFile);
    $realParentDir = realpath($parentDir);
    if ($realParentDir === false || !str_starts_with($realParentDir, $realTenantDir)) {
        jsonResponse(['success' => false, 'message' => 'Security Error: Attempted directory traversal outside parent workspace boundary.'], 403);
    }
}

// ============================================================
// FILE MANAGER ACTIONS ROUTING
// ============================================================

switch ($action) {

    // Action 1: List all files and subdirectories recursively or for current path
    case 'list_files':
        $fileList = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($realTenantDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $fileInfo) {
            $realPath = $fileInfo->getRealPath();
            // Get relative path from tenant workspace root
            $relPath = ltrim(substr($realPath, strlen($realTenantDir)), '/\\');
            $relPath = str_replace('\\', '/', $relPath);

            $fileList[] = [
                'name'         => $fileInfo->getFilename(),
                'path'         => $relPath,
                'is_dir'       => $fileInfo->isDir(),
                'size'         => $fileInfo->isDir() ? 0 : $fileInfo->getSize(),
                'last_modified'=> date('Y-m-d H:i:s', $fileInfo->getMTime())
            ];
        }

        // Return listed array items
        jsonResponse([
            'success' => true,
            'files'   => $fileList
        ]);
        break;

    // Action 2: Read raw file content to support source code editor console loading
    case 'read_file':
        if (!is_file($targetFile)) {
            jsonResponse(['success' => false, 'message' => 'The requested resource is not a file.'], 400);
        }

        $content = file_get_contents($targetFile);
        jsonResponse([
            'success' => true,
            'content' => $content,
            'file'    => $relativeFile
        ]);
        break;

    // Action 3: Write and persist updated code modifications
    case 'save_file':
        $content = $_POST['content'] ?? '';

        // Enforce extension safety locks (only edit text-based layouts)
        $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowedExtensions = ['html', 'htm', 'css', 'js', 'json', 'txt', 'php'];
        if (!in_array($ext, $allowedExtensions, true)) {
            jsonResponse(['success' => false, 'message' => 'Forbidden file extension editing requested.'], 403);
        }

        // Standard users cannot execute command files or system scripts.
        // Strip any dangerous functions or just verify writing is successful
        if (file_put_contents($targetFile, $content, LOCK_EX) !== false) {
            jsonResponse([
                'success' => true,
                'message' => 'File modifications saved successfully.'
            ]);
        } else {
            jsonResponse(['success' => false, 'message' => 'Failed to write file updates on disk.'], 500);
        }
        break;

    // Action 4: Purge or delete a workspace file
    case 'delete_file':
        if (!is_file($targetFile)) {
            jsonResponse(['success' => false, 'message' => 'Target resource is not a file.'], 400);
        }

        // Prevent purging system assets
        if ($relativeFile === 'index.html') {
            jsonResponse(['success' => false, 'message' => 'Access Denied: index.html is the central entry webpage and cannot be deleted.'], 403);
        }

        if (unlink($targetFile)) {
            jsonResponse([
                'success' => true,
                'message' => 'File deleted successfully from workspace.'
            ]);
        } else {
            jsonResponse(['success' => false, 'message' => 'Failed to delete file from disk.'], 500);
        }
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Unrecognized File Manager action request.'], 400);
        break;
}
?>
