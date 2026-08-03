<?php
/**
 * bio_builder.php
 *
 * Implements digital biography creation, persistence, and custom layout rendering using our JSON Database.
 */

// Enable strict typing for safety
declare(strict_types=1);

// Require the centralized JSON Database class from same directory
require_once __DIR__ . '/database.php';

// Instantiate the Database pointing to /app/databases/bio_builder
$db = new Database(__DIR__ . '/../databases', 'bio_builder');

// Ensure table 'bios' is created
$db->createTable('bios');

// ==========================================
// 1. POST ENDPOINT (AJAX Request from jQuery)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    // Send JSON header
    header('Content-Type: application/json');

    // Extract and sanitize inputs
    $username    = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['username'] ?? '');
    $displayName = trim($_POST['display_name'] ?? '');
    $bio         = trim($_POST['bio'] ?? '');
    $avatarUrl   = filter_var(trim($_POST['avatar_url'] ?? ''), FILTER_VALIDATE_URL) ?: 'https://via.placeholder.com/150';
    $linksJson   = $_POST['links'] ?? '[]';
    // Parse social links
    $links       = json_decode($linksJson, true) ?? [];

    // Validate parameters presence
    if (empty($username) || empty($displayName)) {
        // Return bad request error
        echo json_encode(['success' => false, 'error' => 'Username and display name are required.']);
        // Terminate
        exit;
    }

    // Prepare bio payload fields
    $payload = [
        'username'     => $username,
        'display_name' => htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'),
        'bio'          => htmlspecialchars($bio, ENT_QUOTES, 'UTF-8'),
        'avatar_url'   => htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8'),
        'links'        => $links
    ];

    // Check if a bio page already exists for this username to decide update vs insert
    $existing = $db->selectOne('bios', ['username' => $username]);

    if ($existing !== null) {
        // Perform update for existing bio
        $db->update('bios', $payload, ['id' => $existing['id']]);
    } else {
        // Insert new bio record
        $db->insert('bios', $payload);
    }

    // Determine current protocol
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    // Capture current host
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Format redirection URL
    $pageUrl = "{$protocol}{$host}/php/bio_builder.php?u={$username}";

    // Return success response
    echo json_encode(['success' => true, 'page_url' => $pageUrl]);
    // Terminate
    exit;
}

// ==========================================
// 2. GET ENDPOINT (Public Profile View)
// ==========================================
$requestedUser = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['u'] ?? '');

// Try selecting bio page details from our database table
$userBio = null;
if (!empty($requestedUser)) {
    // Select matched bio record
    $userBio = $db->selectOne('bios', ['username' => $requestedUser]);
}

// If no matching profile was found, return a 404 response
if ($userBio === null) {
    // Send 404 header status
    http_response_code(404);
    // Display error message fallback markup
    die("<h3>404 - Bio Page Not Found</h3><p><a href='../apps/bio_builder/index.html'>Create your own Bio Page</a></p>");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($userBio['display_name']) ?> - Links</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
      min-height: 100vh;
      color: #ffffff;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .bio-card {
      width: 100%;
      max-width: 450px;
      text-align: center;
    }
    .avatar-img {
      width: 110px;
      height: 110px;
      object-fit: cover;
      border-radius: 50%;
      border: 4px solid rgba(255, 255, 255, 0.2);
      box-shadow: 0 8px 24px rgba(0,0,0,0.3);
    }
    .bio-btn {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      color: #ffffff;
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 14px;
      padding: 14px 20px;
      margin-bottom: 14px;
      font-weight: 600;
      text-decoration: none;
      display: block;
      transition: all 0.25s ease;
    }
    .bio-btn:hover {
      background: #ffffff;
      color: #0f172a;
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(255, 255, 255, 0.2);
    }
  </style>
</head>
<body>

<div class="bio-card">
  <img src="<?= htmlspecialchars($userBio['avatar_url']) ?>" class="avatar-img mb-3" alt="Avatar">
  <h2 class="fw-bold mb-1"><?= htmlspecialchars($userBio['display_name']) ?></h2>
  <p class="text-white-50 mb-4"><?= nl2br(htmlspecialchars($userBio['bio'])) ?></p>

  <div class="bio-links">
    <?php foreach ($userBio['links'] as $link): ?>
      <a href="<?= htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="bio-btn">
        <?= htmlspecialchars($link['title'], ENT_QUOTES, 'UTF-8') ?>
      </a>
    <?php endforeach; ?>
  </div>
</div>

</body>
</html>
