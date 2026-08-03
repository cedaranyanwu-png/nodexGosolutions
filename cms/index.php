<?php
/**
 * index.php
 *
 * Part of the Code-First CMS. Renders customized CMS pages by parsing URL slugs.
 */

// Require the centralized JSON Database class
require_once __DIR__ . '/../php/database.php';

// Instantiate the Database pointing to /app/databases/site_cms
$db = new Database(__DIR__ . '/../databases', 'site_cms');

// Retrieve raw request URI path
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Determine base folder path dynamically to strip it
$basePath = dirname($_SERVER['SCRIPT_NAME']);
// Strip base folder if it's not the root index
if ($basePath !== '/' && $basePath !== '\\') {
    // Strip from requested URI
    $requestUri = preg_replace('#^' . preg_quote($basePath, '#') . '#', '', $requestUri);
}

// Strip index.php suffix if explicitly present in path
$requestUri = str_replace('/index.php', '', $requestUri);

// Fallback empty slash requests to 'home' slug
$slug = ($requestUri === '' || $requestUri === '/') ? 'home' : $requestUri;

// Fetch matched custom page details from database
$page = $db->selectOne('pages', ['slug' => $slug]);

// If no custom CMS page matches the slug, return a 404 response
if (!$page) {
    // Send 404 header status
    http_response_code(404);
    // Display error fallback markup
    echo "<!DOCTYPE html><html><head><title>404 Not Found</title></head><body style='background:#121212;color:#fff;font-family:sans-serif;text-align:center;padding-top:100px;'>";
    echo "<h1>404 - Page Code Not Found</h1>";
    echo "<p>Parsed Slug: <code>" . htmlspecialchars($slug) . "</code></p>";
    echo "<p>Check your <code>pages.json</code> database to ensure a entry exists with this exact slug.</p>";
    echo "</body></html>";
    // Exit script
    exit;
}

/**
 * Utility to securely render raw HTML/JS/PHP CMS payload strings in clean variables context.
 *
 * @param string $code Raw markup or PHP script content.
 * @param array $context Variable key-value map.
 */
function render_code_payload(string $code, array $context = []): void
{
    // Extract variables
    extract($context);
    // Evaluate PHP structures safely
    eval('?>' . $code);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page['title'] ?? 'CMS Page') ?></title>
    <?php // Render head content
    render_code_payload($page['head_code'] ?? '', ['db' => $db, 'page' => $page]); ?>
</head>
<body>
    <?php // Render body content
    render_code_payload($page['body_code'] ?? '', ['db' => $db, 'page' => $page]); ?>
</body>
</html>
