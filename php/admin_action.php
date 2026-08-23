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

// For template, category, or admin page editor actions, targetUserId is optional
$isTemplateAction = in_array($action, ['add_category', 'upload_template', 'toggle_template_status', 'delete_template', 'edit_template_metadata'], true);
$isPageAction     = in_array($action, ['admin_load_page', 'admin_save_draft', 'admin_publish_page', 'admin_list_versions', 'admin_restore_version'], true);

$targetUser = null;
if (!$isTemplateAction && !$isPageAction) {
    // Reject requests with missing targets for user-based actions
    if ($targetUserId <= 0) {
        jsonResponse(['success' => false, 'message' => 'Invalid target user ID.'], 400);
    }

    // Fetch the target user details from the database
    $targetUser = $conn->selectOne('users', ['id' => $targetUserId]);
    if ($targetUser === null) {
        jsonResponse(['success' => false, 'message' => 'Designated user account not found.'], 404);
    }
}

// Capture targets emails and roles
$targetEmail = strtolower((string)($targetUser['email'] ?? ''));
$targetRole  = strtolower((string)($targetUser['role'] ?? 'tenant'));
$isProtectedSuperAdmin = in_array($targetRole, ['super admin', 'superadmin'], true);

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

        // Every super-admin account is immutable. This cannot be bypassed by the super-admin themselves.
        if ($isProtectedSuperAdmin || $targetEmail === 'admin@nodexplatform.com.ng') {
            jsonResponse(['success' => false, 'message' => 'CRITICAL PROTECTION: Super-admin accounts cannot be modified or demoted.'], 403);
        }

        // Only the main administrator or Super Admin is allowed to toggle/promote other accounts to Admin/Super Admin
        if ($executingAdminEmail !== 'admin@nodexplatform.com.ng' && $executingRole !== 'super admin') {
            jsonResponse(['success' => false, 'message' => 'CRITICAL SECURITY: Only Super Administrators can assign administrative roles.'], 403);
        }

        // Accept requested target role
        $newRole = strtolower(cleanInput($_POST['role'] ?? 'tenant'));
        if ($newRole === 'super admin') { $newRole = 'superadmin'; }
        if ($newRole === 'marketing head') { $newRole = 'marketing_head'; }

        // Super-admin is reserved for the protected bootstrap account and cannot be assigned through this action.
        $validRoles = ['admin', 'manager', 'support', 'moderator', 'financial', 'marketing_head', 'tenant'];
        if (!in_array($newRole, $validRoles, true)) {
            jsonResponse(['success' => false, 'message' => 'Invalid role assigned.'], 400);
        }

        // Update target row
        $updated = $conn->update('users', ['role' => $newRole], ['id' => $targetUserId]);
        if ($updated > 0) {
            // Log administrative activity audit trace
            $conn->insert('activity_logs', [
                'user_id'    => (int)($_SESSION['user_id'] ?? 0),
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

        // Super-admin accounts must remain active and cannot be suspended.
        if ($isProtectedSuperAdmin || $targetEmail === 'admin@nodexplatform.com.ng') {
            jsonResponse(['success' => false, 'message' => 'CRITICAL PROTECTION: Super-admin accounts cannot be suspended.'], 403);
        }

        // Capture current target status
        $currentStatus = strtolower((string)($targetUser['status'] ?? 'active'));
        $newStatus = ($currentStatus === 'suspended') ? 'active' : 'suspended';

        // Update target row
        $updated = $conn->update('users', ['status' => $newStatus], ['id' => $targetUserId]);
        if ($updated > 0) {
            // Log administrative activity audit trace
            $conn->insert('activity_logs', [
                'user_id'    => (int)($_SESSION['user_id'] ?? 0),
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

        // No super-admin account can ever be deleted, including by itself.
        if ($isProtectedSuperAdmin || $targetEmail === 'admin@nodexplatform.com.ng') {
            jsonResponse(['success' => false, 'message' => 'CRITICAL PROTECTION: Super-admin accounts can never be deleted.'], 403);
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
                'user_id'    => (int)($_SESSION['user_id'] ?? 0),
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

    // ACTION: Add New Template Category
    case 'add_category':
        $catName = cleanInput($_POST['category_name'] ?? '');
        $catSlug = strtolower(cleanInput($_POST['category_slug'] ?? ''));

        if (empty($catName) || empty($catSlug)) {
            jsonResponse(['success' => false, 'message' => 'Category title and slug are required.'], 400);
        }

        // Sanitize slug
        $catSlug = preg_replace('/[^a-z0-9_-]/', '', $catSlug);
        if (empty($catSlug)) {
            jsonResponse(['success' => false, 'message' => 'Invalid category slug format.'], 400);
        }

        $conn->createTable('categories');
        $existingCat = $conn->selectOne('categories', ['slug' => $catSlug]);
        if ($existingCat) {
            jsonResponse(['success' => false, 'message' => 'A category with this slug already exists.'], 409);
        }

        $templatesRootDir = realpath(__DIR__ . '/../templates');
        if ($templatesRootDir === false) {
            $templatesRootDir = __DIR__ . '/../templates';
        }
        $catDir = $templatesRootDir . '/' . $catSlug;
        if (!is_dir($catDir)) {
            @mkdir($catDir, 0755, true);
        }

        $conn->insert('categories', [
            'name' => $catName,
            'slug' => $catSlug,
            'count' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $conn->insert('activity_logs', [
            'user_id'    => $_SESSION['user_id'] ?? 0,
            'email'      => $executingAdminEmail,
            'action'     => 'category_created',
            'details'    => "Created category '{$catName}' ({$catSlug})",
            'created_at' => date('Y-m-d H:i:s')
        ]);

        jsonResponse(['success' => true, 'message' => "Category '{$catName}' created successfully!"]);
        break;

    // ACTION: Toggle Template Active / Disabled Status
    case 'toggle_template_status':
        $tplId = (int)($_POST['template_id'] ?? 0);
        if ($tplId <= 0) {
            jsonResponse(['success' => false, 'message' => 'Invalid template ID.'], 400);
        }

        $conn->createTable('templates');
        $tpl = $conn->selectOne('templates', ['id' => $tplId]);
        if (!$tpl) {
            jsonResponse(['success' => false, 'message' => 'Template record not found.'], 404);
        }

        $currentStatus = strtolower((string)($tpl['status'] ?? 'active'));
        $newStatus = ($currentStatus === 'disabled') ? 'active' : 'disabled';

        $conn->update('templates', ['status' => $newStatus], ['id' => $tplId]);

        // Also update template.json if it exists on disk
        $catSlug = $tpl['category'] ?? '';
        $folderSlug = $tpl['folder'] ?? '';
        if (!empty($catSlug) && !empty($folderSlug)) {
            $metaPath = __DIR__ . "/../templates/{$catSlug}/{$folderSlug}/template.json";
            if (file_exists($metaPath)) {
                $meta = json_decode((string)file_get_contents($metaPath), true) ?? [];
                $meta['status'] = $newStatus;
                file_put_contents($metaPath, json_encode($meta, JSON_PRETTY_PRINT), LOCK_EX);
            }
        }

        jsonResponse(['success' => true, 'message' => "Template status toggled to " . strtoupper($newStatus)]);
        break;

    // ACTION: Delete Template
    case 'delete_template':
        $tplId = (int)($_POST['template_id'] ?? 0);
        if ($tplId <= 0) {
            jsonResponse(['success' => false, 'message' => 'Invalid template ID.'], 400);
        }

        $conn->createTable('templates');
        $tpl = $conn->selectOne('templates', ['id' => $tplId]);
        if (!$tpl) {
            jsonResponse(['success' => false, 'message' => 'Template record not found.'], 404);
        }

        $catSlug = $tpl['category'] ?? '';
        $folderSlug = $tpl['folder'] ?? '';

        if (!empty($catSlug) && !empty($folderSlug)) {
            $tplDir = realpath(__DIR__ . "/../templates/{$catSlug}/{$folderSlug}");
            $rootDir = realpath(__DIR__ . "/../templates");
            if ($tplDir !== false && $rootDir !== false && str_starts_with($tplDir, $rootDir) && is_dir($tplDir)) {
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($tplDir, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($files as $fileinfo) {
                    $todo = $fileinfo->isDir() ? 'rmdir' : 'unlink';
                    $todo($fileinfo->getRealPath());
                }
                @rmdir($tplDir);
            }
        }

        $conn->delete('templates', ['id' => $tplId]);

        jsonResponse(['success' => true, 'message' => 'Template deleted successfully from disk and system database.']);
        break;

    // ACTION: Upload New Template Package (ZIP Upload)
    case 'upload_template':
        $catSlug    = strtolower(cleanInput($_POST['category'] ?? ''));
        $name       = cleanInput($_POST['name'] ?? '');
        $description = cleanInput($_POST['description'] ?? 'Uploaded starter template.');
        $previewImg = cleanInput($_POST['preview_img'] ?? '');

        if (empty($catSlug) || empty($name)) {
            jsonResponse(['success' => false, 'message' => 'Please select a category and enter template name.'], 400);
        }

        if (!isset($_FILES['template_zip']) || $_FILES['template_zip']['error'] !== UPLOAD_ERR_OK) {
            jsonResponse(['success' => false, 'message' => 'Please select a valid ZIP template archive to upload.'], 400);
        }

        $zipFile = $_FILES['template_zip']['tmp_name'];
        $zipName = $_FILES['template_zip']['name'];

        $ext = strtolower(pathinfo($zipName, PATHINFO_EXTENSION));
        if ($ext !== 'zip') {
            jsonResponse(['success' => false, 'message' => 'Uploaded file must be a .zip archive.'], 400);
        }

        // Generate folder slug
        $folderSlug = strtolower(preg_replace('/[^a-z0-9_-]/', '', str_replace(' ', '-', $name)));
        if (empty($folderSlug)) {
            $folderSlug = 'template-' . time();
        }

        $templatesRootDir = realpath(__DIR__ . '/../templates');
        if ($templatesRootDir === false) {
            $templatesRootDir = __DIR__ . '/../templates';
        }

        $targetCatDir = $templatesRootDir . '/' . $catSlug;
        if (!is_dir($targetCatDir)) {
            @mkdir($targetCatDir, 0755, true);
        }

        $targetTplDir = $targetCatDir . '/' . $folderSlug;
        if (is_dir($targetTplDir)) {
            $folderSlug .= '-' . time();
            $targetTplDir = $targetCatDir . '/' . $folderSlug;
        }

        @mkdir($targetTplDir, 0755, true);

        $zip = new ZipArchive();
        if ($zip->open($zipFile) === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);

                if (str_contains($filename, '..') || str_starts_with($filename, '/') || str_starts_with($filename, '\\')) {
                    continue;
                }

                $entryExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                $disallowedExts = ['php', 'phtml', 'php3', 'php4', 'php5', 'phps', 'phar', 'exe', 'sh', 'cgi', 'pl', 'asp', 'aspx'];
                if (in_array($entryExt, $disallowedExts, true)) {
                    continue;
                }

                $targetFilePath = $targetTplDir . '/' . ltrim(str_replace('\\', '/', $filename), '/');
                if (str_ends_with($filename, '/')) {
                    @mkdir($targetFilePath, 0755, true);
                } else {
                    $parentFolder = dirname($targetFilePath);
                    if (!is_dir($parentFolder)) {
                        @mkdir($parentFolder, 0755, true);
                    }
                    file_put_contents($targetFilePath, $zip->getFromIndex($i), LOCK_EX);
                }
            }
            $zip->close();
        } else {
            jsonResponse(['success' => false, 'message' => 'Failed to open or extract template ZIP archive.'], 500);
        }

        if (isset($_FILES['preview_file']) && $_FILES['preview_file']['error'] === UPLOAD_ERR_OK) {
            $prevExt = strtolower(pathinfo($_FILES['preview_file']['name'], PATHINFO_EXTENSION));
            if (in_array($prevExt, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                $prevDest = $targetTplDir . '/preview.' . $prevExt;
                move_uploaded_file($_FILES['preview_file']['tmp_name'], $prevDest);
                $previewImg = "/templates/{$catSlug}/{$folderSlug}/preview." . $prevExt;
            }
        }

        if (empty($previewImg)) {
            $previewImg = "/templates/{$catSlug}/{$folderSlug}/preview.jpg";
        }

        $metaData = [
            'name' => $name,
            'description' => $description,
            'category' => $catSlug,
            'preview_img' => $previewImg,
            'author' => $_SESSION['fullname'] ?? 'Platform Admin',
            'version' => '1.0.0',
            'status' => 'active'
        ];
        file_put_contents($targetTplDir . '/template.json', json_encode($metaData, JSON_PRETTY_PRINT), LOCK_EX);

        $conn->createTable('templates');
        $conn->insert('templates', [
            'name' => $name,
            'category' => $catSlug,
            'folder' => $folderSlug,
            'description' => $description,
            'preview_img' => $previewImg,
            'author' => $_SESSION['fullname'] ?? 'Platform Admin',
            'version' => '1.0.0',
            'downloads' => 0,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        jsonResponse(['success' => true, 'message' => "Template '{$name}' uploaded and made available successfully!"]);
        break;

    // ============================================================
    // ADMIN-ONLY PLATFORM PAGE EDITOR ACTIONS
    // ============================================================

    // ACTION 1: Load Page Content & Draft
    case 'admin_load_page':
        if (!isStaff() || !hasAdminPagePermission($_SESSION['role'] ?? '', '/admin/pages')) {
            jsonResponse(['success' => false, 'message' => 'Access Denied: Administrative page editor permissions required.'], 403);
        }

        $filename = cleanInput($_POST['filename'] ?? $_GET['filename'] ?? '');
        $approvedPages = getApprovedPublicPages();
        $approvedMap = array_column($approvedPages, null, 'filename');

        if (empty($filename) || !isset($approvedMap[$filename])) {
            jsonResponse(['success' => false, 'message' => 'Security Error: Requested page is not in the approved whitelist.'], 403);
        }

        $pageMeta = $approvedMap[$filename];
        $fullPath = $pageMeta['full_path'];

        $liveContent = file_exists($fullPath) ? file_get_contents($fullPath) : '';

        $cmsDb = new Database(__DIR__ . '/../databases', 'site_cms');
        $cmsDb->createTable('pages');
        $draftRecord = $cmsDb->selectOne('pages', ['slug' => $filename, 'user_id' => 0]);

        $draftContent = $draftRecord['body_code'] ?? null;
        $hasDraft     = (!empty($draftContent) && (int)($draftRecord['is_draft'] ?? 0) === 1);

        jsonResponse([
            'success'       => true,
            'filename'      => $filename,
            'live_content'  => $liveContent,
            'draft_content' => $draftContent,
            'has_draft'     => $hasDraft,
            'last_modified' => $pageMeta['last_modified']
        ]);
        break;

    // ACTION 2: Save Draft
    case 'admin_save_draft':
        if (!isStaff() || !hasAdminPagePermission($_SESSION['role'] ?? '', '/admin/pages')) {
            jsonResponse(['success' => false, 'message' => 'Access Denied: Administrative page editor permissions required.'], 403);
        }

        $filename = cleanInput($_POST['filename'] ?? '');
        $content  = $_POST['content'] ?? '';

        $approvedPages = getApprovedPublicPages();
        $approvedMap = array_column($approvedPages, null, 'filename');

        if (empty($filename) || !isset($approvedMap[$filename])) {
            jsonResponse(['success' => false, 'message' => 'Security Error: Requested page is not in the approved whitelist.'], 403);
        }

        $cmsDb = new Database(__DIR__ . '/../databases', 'site_cms');
        $cmsDb->createTable('pages');

        $existing = $cmsDb->selectOne('pages', ['slug' => $filename, 'user_id' => 0]);
        if ($existing) {
            $cmsDb->update('pages', [
                'body_code'  => $content,
                'is_draft'   => 1,
                'updated_at' => date('Y-m-d H:i:s')
            ], ['id' => $existing['id']]);
        } else {
            $cmsDb->insert('pages', [
                'title'      => ucwords(str_replace(['-', '_', '.php', '.html'], [' ', ' ', '', ''], $filename)),
                'slug'       => $filename,
                'user_id'    => 0,
                'body_code'  => $content,
                'is_draft'   => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }

        $conn->insert('activity_logs', [
            'user_id'    => $_SESSION['user_id'] ?? 0,
            'email'      => $executingAdminEmail,
            'action'     => 'page_draft_saved',
            'details'    => "Saved draft for platform page: {$filename}",
            'created_at' => date('Y-m-d H:i:s')
        ]);

        jsonResponse(['success' => true, 'message' => "Draft for '{$filename}' saved successfully!"]);
        break;

    // ACTION 3: Publish Page Live
    case 'admin_publish_page':
        if (!isStaff() || !hasAdminPagePermission($_SESSION['role'] ?? '', '/admin/pages')) {
            jsonResponse(['success' => false, 'message' => 'Access Denied: Administrative page editor permissions required.'], 403);
        }

        $filename = cleanInput($_POST['filename'] ?? '');
        $content  = $_POST['content'] ?? '';

        $approvedPages = getApprovedPublicPages();
        $approvedMap = array_column($approvedPages, null, 'filename');

        if (empty($filename) || !isset($approvedMap[$filename])) {
            jsonResponse(['success' => false, 'message' => 'Security Error: Requested page is not in the approved whitelist.'], 403);
        }

        $pageMeta = $approvedMap[$filename];
        $fullPath = $pageMeta['full_path'];

        // 1. Create a version snapshot before publishing
        $oldContent = file_exists($fullPath) ? file_get_contents($fullPath) : '';

        $cmsDb = new Database(__DIR__ . '/../databases', 'site_cms');
        $cmsDb->createTable('page_versions');

        $existingVersions = $cmsDb->select('page_versions', ['filename' => $filename]) ?: [];
        $versionNum = count($existingVersions) + 1;

        $cmsDb->insert('page_versions', [
            'filename'    => $filename,
            'version_num' => $versionNum,
            'content'     => $oldContent,
            'admin_email' => $executingAdminEmail,
            'created_at'  => date('Y-m-d H:i:s')
        ]);

        // 2. Write new content to live file
        if (file_put_contents($fullPath, $content, LOCK_EX) !== false) {
            // 3. Mark draft as published in site_cms
            $cmsDb->createTable('pages');
            $existingDraft = $cmsDb->selectOne('pages', ['slug' => $filename, 'user_id' => 0]);
            if ($existingDraft) {
                $cmsDb->update('pages', [
                    'body_code'  => $content,
                    'is_draft'   => 0,
                    'updated_at' => date('Y-m-d H:i:s')
                ], ['id' => $existingDraft['id']]);
            }

            // 4. Log audit trace
            $conn->insert('activity_logs', [
                'user_id'    => $_SESSION['user_id'] ?? 0,
                'email'      => $executingAdminEmail,
                'action'     => 'page_published',
                'details'    => "Published live page: {$filename} (Version v{$versionNum} backed up)",
                'created_at' => date('Y-m-d H:i:s')
            ]);

            jsonResponse(['success' => true, 'message' => "Page '{$filename}' published live successfully! Version v{$versionNum} backed up."]);
        } else {
            jsonResponse(['success' => false, 'message' => 'Failed to write updated page content to disk.'], 500);
        }
        break;

    // ACTION 4: List Page Backup Versions
    case 'admin_list_versions':
        if (!isStaff() || !hasAdminPagePermission($_SESSION['role'] ?? '', '/admin/pages')) {
            jsonResponse(['success' => false, 'message' => 'Access Denied: Administrative page editor permissions required.'], 403);
        }

        $filename = cleanInput($_POST['filename'] ?? $_GET['filename'] ?? '');

        $approvedPages = getApprovedPublicPages();
        $approvedMap = array_column($approvedPages, null, 'filename');

        if (empty($filename) || !isset($approvedMap[$filename])) {
            jsonResponse(['success' => false, 'message' => 'Security Error: Requested page is not in the approved whitelist.'], 403);
        }

        $cmsDb = new Database(__DIR__ . '/../databases', 'site_cms');
        $cmsDb->createTable('page_versions');

        $versions = $cmsDb->select('page_versions', ['filename' => $filename]) ?: [];

        jsonResponse([
            'success'  => true,
            'filename' => $filename,
            'versions' => array_reverse($versions)
        ]);
        break;

    // ACTION 5: Restore Version Snapshot
    case 'admin_restore_version':
        if (!isStaff() || !hasAdminPagePermission($_SESSION['role'] ?? '', '/admin/pages')) {
            jsonResponse(['success' => false, 'message' => 'Access Denied: Administrative page editor permissions required.'], 403);
        }

        $versionId = (int)($_POST['version_id'] ?? 0);
        if ($versionId <= 0) {
            jsonResponse(['success' => false, 'message' => 'Invalid version ID.'], 400);
        }

        $cmsDb = new Database(__DIR__ . '/../databases', 'site_cms');
        $cmsDb->createTable('page_versions');

        $vRecord = $cmsDb->selectOne('page_versions', ['id' => $versionId]);
        if (!$vRecord) {
            jsonResponse(['success' => false, 'message' => 'Version snapshot record not found.'], 404);
        }

        $filename = $vRecord['filename'] ?? '';

        $approvedPages = getApprovedPublicPages();
        $approvedMap = array_column($approvedPages, null, 'filename');

        if (empty($filename) || !isset($approvedMap[$filename])) {
            jsonResponse(['success' => false, 'message' => 'Security Error: Requested page is not in the approved whitelist.'], 403);
        }

        $pageMeta = $approvedMap[$filename];
        $fullPath = $pageMeta['full_path'];

        // Backup current live before restoring
        $currentLive = file_exists($fullPath) ? file_get_contents($fullPath) : '';
        $existingVersions = $cmsDb->select('page_versions', ['filename' => $filename]) ?: [];
        $newVNum = count($existingVersions) + 1;

        $cmsDb->insert('page_versions', [
            'filename'    => $filename,
            'version_num' => $newVNum,
            'content'     => $currentLive,
            'admin_email' => $executingAdminEmail,
            'created_at'  => date('Y-m-d H:i:s')
        ]);

        // Restore version content
        if (file_put_contents($fullPath, $vRecord['content'], LOCK_EX) !== false) {
            // Sync CMS draft status
            $cmsDb->createTable('pages');
            $existingDraft = $cmsDb->selectOne('pages', ['slug' => $filename, 'user_id' => 0]);
            if ($existingDraft) {
                $cmsDb->update('pages', [
                    'body_code'  => $vRecord['content'],
                    'is_draft'   => 0,
                    'updated_at' => date('Y-m-d H:i:s')
                ], ['id' => $existingDraft['id']]);
            }

            $conn->insert('activity_logs', [
                'user_id'    => $_SESSION['user_id'] ?? 0,
                'email'      => $executingAdminEmail,
                'action'     => 'page_restored',
                'details'    => "Restored version v{$vRecord['version_num']} for platform page: {$filename}",
                'created_at' => date('Y-m-d H:i:s')
            ]);

            jsonResponse(['success' => true, 'message' => "Version v{$vRecord['version_num']} restored to live page '{$filename}' successfully!"]);
        } else {
            jsonResponse(['success' => false, 'message' => 'Failed to write restored version content to disk.'], 500);
        }
        break;

    // DEFAULT Scenario: Return invalid action warning
    default:
        jsonResponse(['success' => false, 'message' => 'Invalid administrative action supplied.'], 400);
        break;
}
?>
