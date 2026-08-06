<?php
/**
 * save_page.php
 *
 * Persists customized Code-First CMS layout structures to pages JSON database tables.
 */

// Include system configurations and database connection layers
require_once __DIR__ . '/db.php';

// Instantiate secure session configurations
secureSession();

// Access Control: Ensure target user is logged in as system administrator
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    jsonResponse(['success' => false, 'message' => 'Unauthorized access.'], 401);
}

// Restrict authentication requests to POST actions only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

// Extract input fields
$slug      = strtolower(cleanInput($_POST['slug'] ?? ''));
$title     = cleanInput($_POST['title'] ?? 'Custom CMS Page');
$headCode  = $_POST['head_code'] ?? '';
$bodyCode  = $_POST['body_code'] ?? '';

// Check that required fields are supplied
if (empty($slug)) {
    jsonResponse(['success' => false, 'message' => 'Please provide a valid page slug.'], 400);
}

// Initialize the site_cms database instance
$siteCmsDb = new Database(__DIR__ . '/../databases', 'site_cms');
$siteCmsDb->createTable('pages');

// Check if a page record with this slug already exists in pages.json
$existingPage = $siteCmsDb->selectOne('pages', ['slug' => $slug]);

if ($existingPage !== null) {
    // Update existing page record
    $updatedCount = $siteCmsDb->update('pages', [
        'title'     => $title,
        'head_code' => $headCode,
        'body_code' => $bodyCode
    ], [
        'slug' => $slug
    ]);

    if ($updatedCount > 0) {
        jsonResponse(['success' => true, 'message' => "Page '{$slug}' updated successfully!"]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Failed to update page or no values were changed.'], 200);
    }
} else {
    // Insert new page record
    $newPage = $siteCmsDb->insert('pages', [
        'slug'      => $slug,
        'title'     => $title,
        'head_code' => $headCode,
        'body_code' => $bodyCode
    ]);

    if ($newPage !== false) {
        jsonResponse(['success' => true, 'message' => "Page '{$slug}' created and saved successfully!"]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Failed to save new CMS page.'], 500);
    }
}
?>
