<?php
/**
 * admin.php
 *
 * This file is part of the Code-First CMS Engine admin dashboard.
 * It provides an interface to edit and save custom pages code payloads.
 * Restricted strictly to logged-in users with administrative privileges.
 */

// Require the secure session and database initialization context from db.php in php/ folder
require_once __DIR__ . '/../php/db.php';

// Instantiate secure session properties configuration
secureSession();

// Restrict access: Check if active user session holds administrative role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Redirection to the central login screen
    header('Location: /login');
    // Terminate script execution to prevent unauthorized payload exposure
    exit;
}

// Require the centralized JSON Database engine
require_once __DIR__ . '/../php/database.php';

// Instantiate the database pointing to /app/databases/site_cms
$db = new Database(__DIR__ . '/../databases', 'site_cms');

// Retrieve all available CMS pages
$pages = $db->select('pages');
// Initialize active page reference variable
$activePage = null;

// Check if an edit request is triggered via GET parameters
if (isset($_GET['edit'])) {
    // Select the target page matching the page ID
    $activePage = $db->selectOne('pages', ['id' => (int)$_GET['edit']]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Code-First CMS Engine</title>
    <!-- Include jQuery via CDN -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        body { font-family: monospace; background: #121212; color: #e0e0e0; display: flex; margin: 0; }
        sidebar { width: 250px; background: #1e1e1e; padding: 20px; height: 100vh; border-right: 1px solid #333; box-sizing: border-box; }
        main { flex: 1; padding: 20px; }
        textarea { width: 100%; height: 250px; background: #000; color: #00ff66; font-family: monospace; padding: 10px; border: 1px solid #444; box-sizing: border-box; }
        input[type="text"] { width: 100%; padding: 8px; margin-bottom: 10px; background: #222; color: #fff; border: 1px solid #444; box-sizing: border-box; }
        button { background: #0088ff; color: #fff; border: none; padding: 10px 15px; cursor: pointer; font-weight: bold; }
        button:disabled { background: #555; }
        a { color: #88ccff; text-decoration: none; }
        #status-msg { margin-top: 10px; padding: 10px; display: none; font-weight: bold; }
        .success { background: #1b4332; color: #2ec4b6; border: 1px solid #2ec4b6; }
        .error { background: #4a0e17; color: #ff4d6d; border: 1px solid #ff4d6d; }
    </style>
</head>
<body>

<sidebar>
    <h3>Pages</h3>
    <ul>
        <?php // Loop through each pages list and output slug lists
        foreach ($pages as $p): ?>
            <li><a href="?edit=<?= $p['id'] ?>"><?= htmlspecialchars($p['slug']) ?></a></li>
        <?php endforeach; ?>
    </ul>
    <a href="admin.php">+ New Page Code</a>
</sidebar>

<main>
    <h2><?= $activePage ? 'Edit Page Code: ' . htmlspecialchars($activePage['slug']) : 'Create New Page Code' ?></h2>

    <!-- Status message bar -->
    <div id="status-msg"></div>

    <form id="cms-code-form">
        <input type="hidden" name="id" id="page_id" value="<?= $activePage['id'] ?? '' ?>">

        <label>URL Slug:</label>
        <input type="text" name="slug" id="slug" value="<?= htmlspecialchars($activePage['slug'] ?? '') ?>" required placeholder="/home">

        <label>Page Title:</label>
        <input type="text" name="title" id="title" value="<?= htmlspecialchars($activePage['title'] ?? '') ?>" required placeholder="My Home Page">

        <label>Custom Head Code (CSS / Meta Tags / Scripts):</label>
        <textarea name="head_code" id="head_code" placeholder="<style> body { background: #111; } </style>"><?= htmlspecialchars($activePage['head_code'] ?? '') ?></textarea>

        <label>Body HTML/JS Content:</label>
        <textarea name="body_code" id="body_code" placeholder="<section><h1>Hello World</h1></section>"><?= htmlspecialchars($activePage['body_code'] ?? '') ?></textarea>

        <br><br>
        <button type="submit" id="save-btn">Save Code Payload</button>
    </form>
</main>

<script>
// Load script context upon DOM readiness
$(document).ready(function() {
    // Intercept form submissions
    $('#cms-code-form').on('submit', function(e) {
        // Prevent default browser form submission
        e.preventDefault();

        // Query element handles
        const $btn = $('#save-btn');
        const $status = $('#status-msg');

        // UI state update
        $btn.prop('disabled', true).text('Saving Code...');
        $status.hide().removeClass('success error');

        // Send payload via AJAX pointing to routed cms/save_page path
        $.ajax({
            url: '/cms/save_page',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                // Check if backend returned success status
                if (response.status === 'success') {
                    // Update status text and display success bar
                    $status.addClass('success').text(response.message).fadeIn();

                    // Update the page ID hidden field if it was a new creation
                    if (response.id) {
                        $('#page_id').val(response.id);
                    }
                } else {
                    // Display error status message
                    $status.addClass('error').text(response.message).fadeIn();
                }
            },
            error: function(xhr, status, error) {
                // Handle Ajax failure scenario
                $status.addClass('error').text('AJAX Error: ' + error).fadeIn();
            },
            complete: function() {
                // Restore submit button state
                $btn.prop('disabled', false).text('Save Code Payload');
            }
        });
    });
});
</script>

</body>
</html>
