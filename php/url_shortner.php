<?php
/**
 * url_shortner.php
 *
 * Implements URL shortening, database persistence, and redirect services using the central Database class.
 */

// Enable strict typing for safety
declare(strict_types=1);

// Require the centralized JSON Database class from same directory
require_once __DIR__ . '/database.php';

// Instantiate the Database pointing to /app/databases/url_shortner
$db = new Database(__DIR__ . '/../databases', 'url_shortner');

// Ensure the table 'links' exists
$db->createTable('links');

/**
 * Generates a random alphanumeric code of a specific length.
 *
 * @param int $length The code length.
 * @return string The generated code.
 */
function generateCode(int $length = 6): string {
    // Generate secure bytes and strip to length
    return substr(bin2hex(random_bytes($length)), 0, $length);
}

// ==========================================
// 1. REDIRECT HANDLER (GET request with ?c=)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['c'])) {
    // Extract and sanitize short code
    $code = trim($_GET['c']);

    // Select the record matching the code
    $link = $db->selectOne('links', ['code' => $code]);

    // If a match is found, redirect to long URL
    if ($link !== null && isset($link['long_url'])) {
        // Send redirection header
        header("Location: " . $link['long_url'], true, 301);
        // Terminate execution
        exit;
    } else {
        // Return 404 status
        http_response_code(404);
        // Display not found message
        echo "<h3>404 - Short link not found!</h3>";
        // Terminate
        exit;
    }
}

// ==========================================
// 2. CREATION HANDLER (POST request from form)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['long_url'])) {
    // Return response as JSON payload
    header('Content-Type: application/json');

    // Extract and validate destination URL
    $longUrl = filter_var(trim($_POST['long_url']), FILTER_VALIDATE_URL);

    // Reject invalid formats
    if (!$longUrl) {
        // Output failure response
        echo json_encode(['success' => false, 'error' => 'Please enter a valid destination URL.']);
        // Terminate
        exit;
    }

    // Attempt to locate if this URL has already been shortened to avoid duplicates
    $existing = $db->selectOne('links', ['long_url' => $longUrl]);

    // If already shortened, reuse the code
    if ($existing !== null) {
        // Extract existing code
        $code = $existing['code'];
    } else {
        // Loop until we generate a unique code
        do {
            // Generate next random code
            $code = generateCode(6);
            // Verify uniqueness
            $dup = $db->selectOne('links', ['code' => $code]);
        } while ($dup !== null);

        // Save newly generated short link association in our database
        $db->insert('links', [
            'code' => $code,
            'long_url' => $longUrl
        ]);
    }

    // Determine current protocol
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    // Capture active host
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Format full redirection short URL string
    $shortUrl = "{$protocol}{$host}/php/url_shortner.php?c={$code}";

    // Output shortening success details
    echo json_encode([
        'success' => true,
        'short_url' => $shortUrl
    ]);
    // Terminate
    exit;
}
?>
