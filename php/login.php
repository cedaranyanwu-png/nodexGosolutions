<?php
/**
 * login.php
 *
 * Implements secure login authentication utilizing the custom JSON Database.
 * Authenticates users and administrators purely from the persistent database table.
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
$rateCheck = checkRateLimit($rateKey, 10, 900);

// If the user has exceeded rate limits, reject authorization
if (!$rateCheck['allowed']) {
    // Return too many requests status code
    jsonResponse(['success' => false, 'message' => $rateCheck['message']], 429);
}

// 1. Database User Authentication via custom JSON database engine (works for both tenants and admins)
$user = $conn->selectOne('users', ['email' => $email]);

// Validate user presence and password match
if (!$user || !password_verify($password, $user['password'] ?? '')) {
    // Return unauthenticated response
    jsonResponse(['success' => false, 'message' => 'Invalid credentials provided.'], 401);
}

// 2. Suspended Status Check: Reject authentication if the user's account is suspended
if (strtolower((string)($user['status'] ?? '')) === 'suspended') {
    // Return forbidden/suspended response status
    jsonResponse([
        'success' => false,
        'message' => 'Your account has been suspended by an administrator. Please contact support.'
    ], 403);
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
// Tenants go to standard user dashboard, whereas internal staff (admin, manager, moderator, support) go to /admin/dashboard
$userRole = strtolower((string)($user['role'] ?? 'tenant'));
$staffRoles = ['admin', 'super admin', 'manager', 'moderator', 'support'];
$redirectUrl = in_array($userRole, $staffRoles, true) ? '/admin/dashboard' : '/user/dashboard';

// Return authentication success response
jsonResponse([
    'success'  => true,
    'role'     => $user['role'] ?? 'tenant',
    'redirect' => $redirectUrl,
    'message'  => 'Login successful! Redirecting...'
]);
?>
