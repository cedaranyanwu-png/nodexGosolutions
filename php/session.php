<?php
/**
 * session.php
 *
 * Manages active user sessions, validations, and "Remember Me" cookie tracking using our custom JSON Database.
 */

// Require database connection and security configurations from same directory
require_once __DIR__ . '/db.php';

// Initialize session securely using db.php's function
if (function_exists('secureSession')) {
    // Invoke secure session properties configuration
    secureSession();
} else {
    // Fallback to start standard session if none exists
    if (session_status() === PHP_SESSION_NONE) {
        // Start session
        session_start();
    }
}

// Check if user is already logged in via active session variables
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    // Validate that the request's User Agent has not changed mid-session
    if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
        // Unset session properties
        session_unset();
        // Destroy active session
        session_destroy();
        // Return session security violation error
        jsonResponse(['success' => false, 'message' => 'Session security violation'], 401);
    }

    // Return current authenticated user session data
    jsonResponse([
        'success' => true,
        'logged_in' => true,
        'role' => $_SESSION['role'] ?? 'user',
        'user' => [
            'account_id' => $_SESSION['account_id'] ?? '',
            'name' => $_SESSION['name'] ?? '',
            'email' => $_SESSION['email'] ?? ''
        ]
    ]);
}

// Fallback checking for "Remember Me" cookie to restore session automatically
if (isset($_COOKIE['remember_token'])) {
    // Parse remember me cookie value token parts
    list($accountId, $token) = explode(':', $_COOKIE['remember_token'] . ':', 2);

    // Ensure both account ID and remember token are present
    if (!empty($accountId) && !empty($token)) {
        // Compute SHA-256 hash of the remember me token
        $tokenHash = hash('sha256', $token);

        // Query users table for a matching record
        $accountData = $conn->selectOne('users', [
            'id' => (int)$accountId,
            'remember_token' => $tokenHash
        ]);

        // If user record is matched, check expiration date and status
        if ($accountData) {
            // Verify token expiration status
            $expires = $accountData['remember_expires'] ?? '';
            // If expires is empty or has already passed, invalidate login
            if (!empty($expires) && strtotime($expires) < time()) {
                // Mark token as expired by setting account data reference to null
                $accountData = null;
            }
        }

        // If the matching account is validated, process auto-login
        if ($accountData) {
            // Check if user status is suspended
            if (isset($accountData['status']) && strtolower((string)$accountData['status']) === 'suspended') {
                // Return account suspended forbidden response
                jsonResponse(['success' => false, 'logged_in' => false, 'message' => 'Account suspended'], 403);
            }

            // Regenerate session ID and restore session values
            session_regenerate_id(true);
            $_SESSION['logged_in'] = true;
            $_SESSION['role'] = $accountData['role'] ?? 'user';
            $_SESSION['account_id'] = $accountData['id'];
            $_SESSION['db_id'] = $accountData['id'];
            $_SESSION['email'] = $accountData['email'] ?? '';
            $_SESSION['name'] = $accountData['fullname'] ?? $accountData['full_name'] ?? '';
            $_SESSION['login_time'] = time();
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';

            // Return automatic login success response
            jsonResponse([
                'success' => true,
                'logged_in' => true,
                'role' => $accountData['role'] ?? 'user',
                'user' => [
                    'account_id' => $accountData['id'],
                    'name' => $accountData['fullname'] ?? $accountData['full_name'] ?? '',
                    'email' => $accountData['email'] ?? ''
                ]
            ]);
        }
    }
}

// Return standard unauthenticated error response when no session or cookie is matched
jsonResponse([
    'success' => false,
    'logged_in' => false,
    'message' => 'Not authenticated'
], 401);
?>
