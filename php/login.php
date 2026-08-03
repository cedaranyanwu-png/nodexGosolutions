<?php
/**
 * login.php
 *
 * Implements secure login authentication utilizing the custom JSON Database.
 */

// Require the connection and security helper configurations from same folder
require_once __DIR__ . '/db.php';

// Instantiate secure session management
secureSession();

// Restrict authentication requests to POST actions only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Return method not allowed response
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

// Extract and sanitize input credentials
$email    = strtolower(cleanInput($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';

// Check that required credentials fields are not empty
if (empty($email) || empty($password)) {
    // Return bad request error
    jsonResponse(['success' => false, 'message' => 'Please provide both email and password.'], 400);
}

// Restrict auth frequency based on unique IP and Email identifier combination
$rateKey = 'login_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '_' . $email;
// Invoke the rate limits validator
$rateCheck = checkRateLimit($rateKey, 5, 900);

// If the user has exceeded rate limits, reject authorization
if (!$rateCheck['allowed']) {
    // Return too many requests status code
    jsonResponse(['success' => false, 'message' => $rateCheck['message']], 429);
}

// 1. Dual Login Check: Fallback for hardcoded administrative access using requested credentials (admin123)
if ($email === 'admin@nodexplatform.com.ng' && $password === 'admin123') {
    // Reset rate limits on successful authentication
    resetRateLimit($rateKey);
    // Assign admin session parameters
    $_SESSION['user_id']   = 0;
    $_SESSION['email']     = $email;
    $_SESSION['role']      = 'admin';
    $_SESSION['fullname']  = 'System Administrator';

    // Return administrative success response
    jsonResponse([
        'success'  => true,
        'role'     => 'admin',
        'redirect' => '/admin/dashboard.php',
        'message'  => 'Admin authentication successful! Redirecting...'
    ]);
}

// 2. Database User Authentication via custom JSON database engine (works for both tenants and admin)
$user = $conn->selectOne('users', ['email' => $email]);

// Validate user presence and password match
if (!$user || !password_verify($password, $user['password'] ?? '')) {
    // Return unauthenticated response
    jsonResponse(['success' => false, 'message' => 'Invalid credentials provided.'], 401);
}

// 3. Email Verification Enforcer
if ((int)($user['is_verified'] ?? 0) !== 1) {
    // Return unverified response status
    jsonResponse([
        'success' => false,
        'status'  => 'unverified',
        'message' => 'Your email address is not verified yet. Please verify your email before logging in.'
    ], 403);
}

// Clean rate limit track history upon successful validation
resetRateLimit($rateKey);

// Set authorization session fields
$_SESSION['user_id']  = $user['id'];
$_SESSION['email']    = $user['email'];
$_SESSION['role']     = $user['role'] ?? 'tenant';
$_SESSION['fullname'] = $user['fullname'] ?? '';

// Direct authenticated user to appropriate dashboard path based on role (admin or tenant)
$redirectUrl = ($user['role'] === 'admin') ? '/admin/dashboard.php' : 'user/dashboard.php';

// Return authentication success response
jsonResponse([
    'success'  => true,
    'role'     => $user['role'] ?? 'tenant',
    'redirect' => $redirectUrl,
    'message'  => 'Login successful! Redirecting...'
]);
?>
