<?php
/**
 * save_page.php
 *
 * Persists customized Code-First CMS layout structures to pages JSON database tables.
 * Supports both system administrators and standard subscribed tenant users,
 * automatically registering newly published pages inside the user's Websites directory.
 */

// Include system configurations and database connection layers
require_once __DIR__ . '/db.php';

// Instantiate secure session configurations
secureSession();

// Access Control: Ensure target user is authenticated in the system
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized access. Please log in.'], 401);
}

// Restrict authentication requests to POST actions only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

// Isolate session user parameters
$userId   = (int)$_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'tenant';

// Security Gate: Standard tenant users must hold an active subscription to publish layouts
if ($userRole !== 'admin') {
    enforceSubscription(true);
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

// Sanitize slug alphanumeric boundaries to avoid directory traversal or injection
$slug = preg_replace('/[^a-z0-9_-]/', '', $slug);
if (empty($slug)) {
    jsonResponse(['success' => false, 'message' => 'Invalid slug format.'], 400);
}

// Initialize the site_cms database instance
$siteCmsDb = new Database(__DIR__ . '/../databases', 'site_cms');
$siteCmsDb->createTable('pages');

// Check if a page record with this slug already exists for this specific user/admin
$existingPage = $siteCmsDb->selectOne('pages', [
    'slug'    => $slug,
    'user_id' => $userRole === 'admin' ? 0 : $userId
]);

if ($existingPage !== null) {
    // Update existing page record
    $updatedCount = $siteCmsDb->update('pages', [
        'title'     => $title,
        'head_code' => $headCode,
        'body_code' => $bodyCode
    ], [
        'id' => $existingPage['id']
    ]);

    $message = "Page '{$slug}' updated successfully!";
} else {
    // Insert new page record isolated by user_id
    $newPage = $siteCmsDb->insert('pages', [
        'user_id'   => $userRole === 'admin' ? 0 : $userId,
        'slug'      => $slug,
        'title'     => $title,
        'head_code' => $headCode,
        'body_code' => $bodyCode
    ]);

    if ($newPage === false) {
        jsonResponse(['success' => false, 'message' => 'Failed to save new CMS page.'], 500);
    }

    $message = "Page '{$slug}' created and saved successfully!";
}

// If saved by a standard tenant, automatically register this page in their Websites directory list!
if ($userRole !== 'admin') {
    // Initialize websites table in system database
    $conn->createTable('websites');

    // Build the dynamic URL address for page previewing
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
    $pageUrl = "{$protocol}://{$host}/{$slug}";

    // Check if this website is already registered under their websites list
    $existingWeb = $conn->selectOne('websites', [
        'user_id' => $userId,
        'url'     => $pageUrl
    ]);

    if ($existingWeb === null) {
        // Register newly published CMS page inside the tenant's Websites list table
        $conn->insert('websites', [
            'user_id' => $userId,
            'name'    => "CMS: " . $title,
            'url'     => $pageUrl
        ]);
    } else {
        // Update website name if already exists
        $conn->update('websites', [
            'name' => "CMS: " . $title
        ], [
            'id' => $existingWeb['id']
        ]);
    }
}

// Return success confirmation response
jsonResponse(['success' => true, 'message' => $message]);
?>
