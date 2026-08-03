<?php
/**
 * register.php
 *
 * Implements user registration and handles secure insertion into our JSON Database.
 */

// Require the connection and security configurations
require_once 'db.php';
// Require email dispatch service interface
require_once 'send_email.php';

// Initiate secure session tracking
secureSession();

// Restrict registration processing to POST requests only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Return method not allowed response
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

// Extract and sanitize input parameters
$fullname = cleanInput($_POST['fullname'] ?? '');
$email    = strtolower(cleanInput($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';

// Check that all required inputs are present
if (empty($fullname) || empty($email) || empty($password)) {
    // Return bad request error response
    jsonResponse(['success' => false, 'message' => 'All fields are required.'], 400);
}

// Validate email address pattern format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    // Return bad email format validation error
    jsonResponse(['success' => false, 'message' => 'Invalid email address format.'], 400);
}

// 1. Check if user already exists using selectOne JSON API
$existingUser = $conn->selectOne('users', ['email' => $email]);

// If the email is already registered, reject registration
if ($existingUser !== null) {
    // Return conflict error response
    jsonResponse(['success' => false, 'message' => 'An account with this email already exists.'], 409);
}

// 2. Prepare user entity payload structure
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
// Create secure verification token
$token          = generateSecureToken(32);
// Assign default role
$role           = 'tenant';

// Prepare data for JSON database storage
$newUser = [
    'fullname' => $fullname,
    'full_name' => $fullname,
    'email' => $email,
    'password' => $hashedPassword,
    'role' => $role,
    'is_verified' => 0,
    'email_verified' => 0,
    'verification_token' => $token
];

// Perform record insertion via unified JSON database connection
$insertedUser = $conn->insert('users', $newUser);

// Check if user record was successfully created
if ($insertedUser !== false) {
    // 3. Construct the verification link pointing directly to verify.html
    $protocol  = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    // Fetch server host
    $host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Build verification URL string
    $verifyUrl = "{$protocol}://{$host}/verify.html?email=" . urlencode($email) . "&token=" . $token;

    // Define email subject header
    $subject = "Verify Your Account - nodexGosolutions";

    // Construct HTML formatted message body
    $body = "
    <h2>Hello, {$fullname}!</h2>
    <p>Thank you for registering on <strong>nodexGosolutions</strong>.</p>
    <p>Please click the button below to verify your email address and activate your account:</p>
    <p><a href='{$verifyUrl}' style='padding: 10px 20px; background: #00d2ff; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;'>Verify Email Address</a></p>
    <p>Or copy and paste this link into your browser:</p>
    <p><a href='{$verifyUrl}'>{$verifyUrl}</a></p>
    <br>
    <p>If you did not request this, please ignore this message.</p>
    ";

    // 4. Send email
    $mailSent = sendEMail($email, $subject, $body);

    // Return success response to the frontend client
    jsonResponse([
        'success' => true,
        'message' => 'Registration successful! A verification email has been sent to ' . htmlspecialchars($email) . '. Please check your inbox to activate your account.'
    ]);
} else {
    // Return database insertion failed status
    jsonResponse(['success' => false, 'message' => 'Account creation failed. Please try again later.'], 500);
}
?>
