<?php
/**
 * db.php
 *
 * This file replaces the MySQLi connection layer with our custom JSON-based
 * Database Engine, providing robust authentication and security helpers.
 * It also automatically seeds the database with the main administrator account.
 */

// Enable strict typing for better reliability and fewer runtime bugs
declare(strict_types=1);

// Require our unified JSON Database engine class from the same directory
require_once __DIR__ . '/database.php';

// Initialize the Database instance targeting the 'system' JSON database directory
// We store this instance in $conn to maintain compatibility across existing files
$conn = new Database(__DIR__ . '/../databases', 'system');

// Ensure tables exist under 'system' database folder
$conn->createTable('users');
$conn->createTable('rate_limits');
$conn->createTable('login_attempts');
$conn->createTable('tickets'); // Support tickets database table

// Dynamic Auto-Seeder: Seed the main administrator account if users table is empty
$usersCount = count($conn->select('users'));
if ($usersCount === 0) {
    // Hash password admin123 securely
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    // Seed admin profile
    $conn->insert('users', [
        'fullname' => 'Cedar Anyanwu',
        'email' => 'admin@nodexplatform.com.ng',
        'password' => $hashedPassword,
        'role' => 'admin',
        'status' => 'active',
        'is_verified' => 1,
        'email_verified' => 1,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Configure secure and isolated session properties.
 */
function secureSession(): void {
    // Prevent PHP from passing session ID in URLs
    ini_set('session.use_strict_mode', '1');
    // Enforce session cookies to be accessed only via HTTP protocols
    ini_set('session.use_only_cookies', '1');
    // Mitigate XSS attacks by locking access to session cookies via JS
    ini_set('session.cookie_httponly', '1');
    // Allow sharing session cookie during standard redirection paths
    ini_set('session.cookie_samesite', 'Lax');

    // Check if the current connection runs over a secured SSL transport layer
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        // Mark session cookie as secure
        ini_set('session.cookie_secure', '1');
    }

    // Define an explicit, standard session name
    session_name('NODX_SESSION');

    // Start session if none is currently active
    if (session_status() === PHP_SESSION_NONE) {
        // Invoke session start
        session_start();
    }

    // Manage session expiration and rotation for heightened security
    if (!isset($_SESSION['_created'])) {
        // Record creation time
        $_SESSION['_created'] = time();
    } elseif (time() - $_SESSION['_created'] > 1800) {
        // Regenerate unique session ID and flush old session data
        session_regenerate_id(true);
        // Reset creation timestamp
        $_SESSION['_created'] = time();
    }
}

/**
 * Generates a cryptographically strong, secure token.
 *
 * @param int $length The byte length of raw data before hex conversion.
 * @return string The generated secure token.
 */
function generateSecureToken(int $length = 32): string {
    // Generate secure bytes and return hex encoded string
    return bin2hex(random_bytes($length));
}

/**
 * Clean and escape dangerous character sequences in user input to mitigate XSS injections.
 *
 * @param string $data Raw input data.
 * @return string Sanitized input string.
 */
function cleanInput(string $data): string {
    // Strip leading/trailing whitespaces
    $data = trim($data);
    // Remove backslashes
    $data = stripslashes($data);
    // HTML escape content using UTF-8 representation
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Checks and updates rate limit values inside the JSON database.
 *
 * @param string $identifier Unique rate limiting key (such as client IP + action name).
 * @param int $maxAttempts Total allowed action occurrences before lockout.
 * @param int $windowSeconds Lockout duration window in seconds.
 * @return array Evaluation details showing if allowed, remaining attempts, or retry delay.
 */
function checkRateLimit(string $identifier, int $maxAttempts = 5, int $windowSeconds = 900): array {
    // Grab global database connection instance
    global $conn;

    // Find the record matching the rate limit identifier
    $row = $conn->selectOne('rate_limits', ['identifier' => $identifier]);
    // Get the current Unix timestamp
    $now = time();

    // If no existing rate limit tracking record is found, initialize one
    if (!$row) {
        // Prepare new rate limit data structure
        $newLimit = [
            'identifier' => $identifier,
            'attempts' => 1,
            'last_attempt' => $now
        ];
        // Insert record into rate_limits JSON table
        $conn->insert('rate_limits', $newLimit);
        // Return success response with remaining attempts
        return ['allowed' => true, 'remaining' => $maxAttempts - 1];
    }

    // Reset rate limit attempts count if the tracking window has elapsed
    if ($now - (int)$row['last_attempt'] > $windowSeconds) {
        // Update tracking values
        $conn->update('rate_limits', ['attempts' => 1, 'last_attempt' => $now], ['identifier' => $identifier]);
        // Return success response with fresh attempts
        return ['allowed' => true, 'remaining' => $maxAttempts - 1];
    }

    // Handle threshold hit where max allowed rate attempts have been exhausted
    if ((int)$row['attempts'] >= $maxAttempts) {
        // Calculate remaining cooldown duration
        $retryAfter = $windowSeconds - ($now - (int)$row['last_attempt']);
        // Return rate limit blocked response
        return [
            'allowed' => false,
            'retry_after' => $retryAfter,
            'message' => "Too many attempts. Please try again in " . (int)ceil($retryAfter / 60) . " minutes."
        ];
    }

    // Update attempt metrics by incrementing the attempts count
    $conn->update('rate_limits', [
        'attempts' => (int)$row['attempts'] + 1,
        'last_attempt' => $now
    ], ['identifier' => $identifier]);

    // Return success response with updated remaining attempts
    return ['allowed' => true, 'remaining' => $maxAttempts - (int)$row['attempts'] - 1];
}

/**
 * Deletes rate limit logs to reset counts on successful actions (e.g., correct login).
 *
 * @param string $identifier Unique rate limiting key.
 */
function resetRateLimit(string $identifier): void {
    // Grab global database connection instance
    global $conn;
    // Remove the corresponding rate limit entry
    $conn->delete('rate_limits', ['identifier' => $identifier]);
}

/**
 * Return JSON response and terminate program execution.
 *
 * @param mixed $data Content to serialize and echo.
 * @param int $statusCode HTTP response status header.
 */
function jsonResponse(mixed $data, int $statusCode = 200): void {
    // Assign HTTP status code
    http_response_code($statusCode);
    // Send correct content encoding header
    header('Content-Type: application/json; charset=utf-8');
    // Output encoded json string
    echo json_encode($data);
    // Exit current execution context
    exit;
}

/**
 * Appends a dynamic auto-increment parameter to asset URLs based on file modification time.
 * Prevents browser caching issues when CSS or JS files are updated.
 *
 * @param string $path Clean relative asset path (e.g. '/css/style.css').
 * @return string Cache-busted asset URL string.
 */
function assetUrl(string $path): string {
    // Resolve absolute path to check file on disk
    $absPath = __DIR__ . '/..' . $path;
    // Fallback to static timestamp
    $version = '1.0.0';
    if (file_exists($absPath)) {
        // Retrieve file modification time to automatically increment versioning
        $version = (string)filemtime($absPath);
    }
    // Append query parameter with auto-increment version number
    return $path . '?v=' . $version;
}
?>
