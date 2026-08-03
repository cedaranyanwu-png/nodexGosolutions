<?php
declare(strict_types=1);

$dataFile = __DIR__ . '/bios.json';

function getBios(string $file): array {
    if (!file_exists($file)) {
        file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));
        return [];
    }
    return json_decode(file_get_contents($file), true) ?? [];
}

function saveBios(string $file, array $data): void {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

// ==========================================
// 1. POST ENDPOINT (AJAX Request from jQuery)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    header('Content-Type: application/json');

    $username    = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['username'] ?? '');
    $displayName = trim($_POST['display_name'] ?? '');
    $bio         = trim($_POST['bio'] ?? '');
    $avatarUrl   = filter_var(trim($_POST['avatar_url'] ?? ''), FILTER_VALIDATE_URL) ?: 'https://via.placeholder.com/150';
    $linksJson   = $_POST['links'] ?? '[]';
    $links       = json_decode($linksJson, true) ?? [];

    if (empty($username) || empty($displayName)) {
        echo json_encode(['success' => false, 'error' => 'Username and display name are required.']);
        exit;
    }

    $bios = getBios($dataFile);
    $bios[$username] = [
        'display_name' => htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'),
        'bio'          => htmlspecialchars($bio, ENT_QUOTES, 'UTF-8'),
        'avatar_url'   => htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8'),
        'links'        => $links,
        'updated_at'   => date('Y-m-d H:i:s')
    ];

    saveBios($dataFile, $bios);

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $pageUrl = "{$protocol}{$host}{$scriptDir}/bio_builder.php?u={$username}";

    echo json_encode(['success' => true, 'page_url' => $pageUrl]);
    exit;
}

// ==========================================
// 2. GET ENDPOINT (Public Profile View)
// ==========================================
$requestedUser = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['u'] ?? '');
$bios = getBios($dataFile);

if (empty($requestedUser) || !isset($bios[$requestedUser])) {
    http_response_code(404);
    die("<h3>404 - Bio Page Not Found</h3><p><a href='index.html'>Create your own Bio Page</a></p>");
}

$userBio = $bios[$requestedUser];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $userBio['display_name'] ?> - Links</title>
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
  <img src="<?= $userBio['avatar_url'] ?>" class="avatar-img mb-3" alt="Avatar">
  <h2 class="fw-bold mb-1"><?= $userBio['display_name'] ?></h2>
  <p class="text-white-50 mb-4"><?= nl2br($userBio['bio']) ?></p>

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