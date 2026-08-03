<?php
/**
 * save_page.php
 *
 * This file processes AJAX requests to create or update custom pages inside site_cms database.
 * Strictly verified to prevent unauthenticated users or non-admins from executing write actions.
 */

// Return response as JSON payload
header('Content-Type: application/json');

// Require the secure session and database initialization context from db.php
require_once __DIR__ . '/../../main/php/db.php';

// Instantiate secure session properties configuration
secureSession();

// Strictly check if active user session holds administrative role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Send 403 Forbidden header
    http_response_code(403);
    // Return unauthorized JSON payload
    echo json_encode([
        'status' => 'error',
        'message' => 'Access denied. Unauthenticated remote code execution prevented.'
    ]);
    // Terminate script execution immediately
    exit;
}

// Require the centralized JSON Database engine
require_once __DIR__ . '/../../php/database.php';

try {
    // Instantiate the database pointing to /app/databases/site_cms
    $db = new Database(__DIR__ . '/../../databases', 'site_cms');

    // Extract form variables
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $slug = trim($_POST['slug'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $head_code = $_POST['head_code'] ?? '';
    $body_code = $_POST['body_code'] ?? '';

    // Validate page parameters presence
    if (empty($slug) || empty($title)) {
        // Output failure JSON response
        echo json_encode([
            'status' => 'error',
            'message' => 'Slug and Title fields are required.'
        ]);
        // Stop execution
        exit;
    }

    // Prepare page fields payload structure
    $payload = [
        'slug' => $slug,
        'title' => $title,
        'head_code' => $head_code,
        'body_code' => $body_code
    ];

    // If an ID was provided, perform update; otherwise insert a new record
    if ($id) {
        // Perform update
        $db->update('pages', $payload, ['id' => $id]);
        // Hold saved page id
        $savedId = $id;
    } else {
        // Perform insert
        $result = $db->insert('pages', $payload);
        // Extract inserted record ID
        $savedId = $result['id'] ?? null;
    }

    // Return successfully completed page save response
    echo json_encode([
        'status' => 'success',
        'message' => 'Code payload written to JSON successfully!',
        'id' => $savedId
    ]);

} catch (Exception $e) {
    // Return failed database operations exception details
    echo json_encode([
        'status' => 'error',
        'message' => 'Database operation failed: ' . $e->getMessage()
    ]);
}
?>
