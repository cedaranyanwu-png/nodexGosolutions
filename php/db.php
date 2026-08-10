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

/**
 * Checks, defaults, and transitions the subscription and trial status of a tenant user securely.
 * Always resolves correct trial status boundaries and updates the system database if a transition is detected.
 *
 * @param array $user Passed by reference to allow modifying current user array variables directly.
 * @param Database $dbConnection The active system database connection instance.
 * @return string Calculated current status ('trial', 'active', 'expired', 'suspended').
 */
function checkAndUpdateSubscription(array &$user, Database $dbConnection): string {
    // Admin role accounts are fully exempt from subscription constraints and are always active
    if (strtolower((string)($user['role'] ?? '')) === 'admin') {
        // Return active state for administrators
        return 'active';
    }

    // Default migration handler: if an existing user lacks trial/subscription properties, set safe starting defaults
    if (!isset($user['trial_start'])) {
        // Set start of free trial to account creation time or the current timestamp
        $createdAt = $user['created_at'] ?? date('Y-m-d H:i:s');
        // Ensure precise date string representation
        $user['trial_start'] = date('Y-m-d H:i:s', strtotime($createdAt));
        // Free trial runs for exactly one month after initial start date
        $user['trial_end'] = date('Y-m-d H:i:s', strtotime($createdAt . ' +1 month'));
        // Default starting status is 'trial'
        $user['subscription_status'] = 'trial';
        // Assign default subscription starter plan
        $user['subscription_plan'] = 'Starter Space';
        // Set starting paid dates to null as none exists yet
        $user['subscription_start'] = null;
        // Set ending paid dates to null
        $user['subscription_end'] = null;

        // Persist default trial/subscription metadata back to the users JSON database
        $dbConnection->update('users', [
            'trial_start' => $user['trial_start'],
            'trial_end' => $user['trial_end'],
            'subscription_status' => $user['subscription_status'],
            'subscription_plan' => $user['subscription_plan'],
            'subscription_start' => null,
            'subscription_end' => null,
        ], ['id' => $user['id']]);
    }

    // Enforcement guard: check if the account status is suspended globally
    if (strtolower((string)($user['status'] ?? '')) === 'suspended') {
        // Ensure user's internal subscription_status is updated if not already matched
        if (($user['subscription_status'] ?? '') !== 'suspended') {
            // Assign suspended state
            $user['subscription_status'] = 'suspended';
            // Update users table records to state suspended
            $dbConnection->update('users', ['subscription_status' => 'suspended'], ['id' => $user['id']]);
        }
        // Return suspended status
        return 'suspended';
    }

    // Capture current server-side timestamp
    $now = time();
    // Resolve trial boundaries as Unix timestamps
    $trialEnd = strtotime($user['trial_end']);
    // Resolve paid subscription end boundary as timestamp if specified
    $subEnd = !empty($user['subscription_end']) ? strtotime($user['subscription_end']) : 0;

    // Grab current status from the user object
    $currentStatus = $user['subscription_status'] ?? 'trial';
    // Initialize temporary comparison variable
    $newStatus = $currentStatus;

    // Check if the current registered state is active
    if ($currentStatus === 'active') {
        // If a paid subscription end date exists and has elapsed, transition to expired
        if ($subEnd > 0 && $now > $subEnd) {
            // Mark as expired
            $newStatus = 'expired';
        }
    } else {
        // If no active subscription is active, evaluate trial duration
        if ($now < $trialEnd) {
            // Keep or transition back to trial state
            $newStatus = 'trial';
        } else {
            // Mark as expired since trial duration has run out and no paid plan is active
            $newStatus = 'expired';
        }
    }

    // If a transition change has occurred, update the database record and local reference
    if ($newStatus !== $currentStatus) {
        // Update user array reference
        $user['subscription_status'] = $newStatus;
        // Persist status change into the custom users database table
        $dbConnection->update('users', ['subscription_status' => $newStatus], ['id' => $user['id']]);
    }

    // Return the final resolved subscription status
    return $newStatus;
}

/**
 * Enforces active free trial or active subscription checks on pages and API endpoints.
 * Redirects unauthorized standard users or serves JSON error blocks according to environment requests.
 *
 * @param bool $isApi Set true to render a direct JSON error response block instead of a redirect.
 */
function enforceSubscription(bool $isApi = false): void {
    // Grab central global database connector instance
    global $conn;

    // Ensure session is started and active
    secureSession();

    // Administrative accounts hold ultimate bypass privileges on all workspace restrictions
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        // Immediately return without restrictions
        return;
    }

    // Enforce active session requirements
    if (!isset($_SESSION['email'])) {
        // Check if current action is an API query
        if ($isApi) {
            // Output unauthenticated JSON error
            jsonResponse(['success' => false, 'message' => 'Unauthorized access. Please log in.'], 401);
        } else {
            // Redirect standard page loader to the Login Hub page
            header('Location: /login');
            // Halt script execution
            exit;
        }
    }

    // Query active user's credentials from the main users database
    $user = $conn->selectOne('users', ['email' => $_SESSION['email']]);
    // Check user record presence
    if (!$user) {
        // Check if query was made via API
        if ($isApi) {
            // Respond with user not found JSON error
            jsonResponse(['success' => false, 'message' => 'User account not found.'], 404);
        } else {
            // Redirect standard page loaders to login
            header('Location: /login');
            // Halt execution
            exit;
        }
    }

    // Invoke trial/subscription check helper to resolve real-time account status
    $status = checkAndUpdateSubscription($user, $conn);

    // Block workspace access if subscription or free trial is expired or suspended
    if ($status === 'expired' || $status === 'suspended') {
        // Check if query is an API call
        if ($isApi) {
            // Return forbidden HTTP status and detail payload block
            jsonResponse([
                'success' => false,
                'subscription_expired' => true,
                'message' => 'Your subscription or free trial has expired. Please subscribe to continue.'
            ], 403);
        } else {
            // Capture the current target URI path
            $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
            // Allow dashboard.php itself to load so it can render the restricted workspace block
            if (str_contains($requestUri, 'user/dashboard')) {
                // Return gracefully without redirecting to avoid infinite loop
                return;
            } else {
                // Redirect standard workspace loaders to user dashboard page to see subscription options
                header('Location: /user/dashboard');
                // Halt execution
                exit;
            }
        }
    }
}
?>
