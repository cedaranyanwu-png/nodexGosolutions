<?php
/**
 * register.php
 *
 * Handles account creations and workspace initialization using the custom JSON database.
 */

// Include system configurations and security helpers
require_once __DIR__ . '/db.php';

// Instantiate secure session configurations
secureSession();

// Restrict authentication requests to POST actions only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

// Extract and sanitize input payload
$fullname = cleanInput($_POST['fullname'] ?? '');
$email    = strtolower(cleanInput($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';

// Check that required fields are not empty
if (empty($fullname) || empty($email) || empty($password)) {
    jsonResponse(['success' => false, 'message' => 'Please fill in all registration fields.'], 400);
}

// Check if email conforms to typical regex patterns
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['success' => false, 'message' => 'Please provide a valid email address.'], 400);
}

// Enforce password complexity criteria
if (strlen($password) < 6) {
    jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters long.'], 400);
}

// Check database for pre-existing email registrations
$existingUser = $conn->selectOne('users', ['email' => $email]);
if ($existingUser !== null) {
    jsonResponse(['success' => false, 'message' => 'This email address is already registered.'], 409);
}

// Generate unique verification token
$token = generateSecureToken(16);

// Hash password securely before database persistence
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Save user structure to database table
$newUser = $conn->insert('users', [
    'fullname'           => $fullname,
    'email'              => $email,
    'password'           => $hashedPassword,
    'role'               => 'tenant',
    'status'             => 'active',
    'is_verified'        => 0, // Email verification required
    'email_verified'     => 0,
    'verification_token' => $token
]);

if ($newUser !== false) {
    // Determine the web protocol
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Construct the verification link pointing directly to verify.html
    $verifyUrl = "{$protocol}://{$host}/verify.html?email=" . urlencode($email) . "&token=" . $token;

    // Return success response with verification link details
    jsonResponse([
        'success' => true,
        'message' => 'Registration successful! For demo/testing, click the verification link below to verify your account.',
        'verify_url' => $verifyUrl
    ]);
} else {
    jsonResponse(['success' => false, 'message' => 'Internal database error. Please try again.'], 500);
}
?>
