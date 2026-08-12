<?php
/**
 * verify.php
 *
 * Handles account validation processes using the custom JSON database driver.
 */

// Include the unified database and helper functions file from same directory
require_once __DIR__ . '/db.php';

// Extract and sanitize input query criteria
$email = cleanInput($_POST['email'] ?? $_GET['email'] ?? '');
$token = cleanInput($_POST['token'] ?? $_GET['token'] ?? '');

// Make sure both email and token are supplied
if (empty($email) || empty($token)) {
    // Return bad request error response
    jsonResponse(['success' => false, 'message' => 'Missing email or verification token.'], 400);
}

// Check database for token and email matching
$user = $conn->selectOne('users', [
    'email' => $email,
    'verification_token' => $token
]);

// If matching record is found, verify the account status
if ($user !== null) {
    // Check if the user is already marked verified
    if ((int)($user['is_verified'] ?? 0) === 1) {
        // Return already verified status response
        jsonResponse(['success' => true, 'message' => 'Account is already verified. You can log in.']);
    }

    // Mark user as verified and clear verification tokens
    $updatedRows = $conn->update('users', [
        'is_verified' => 1,
        'email_verified' => 1,
        'verification_token' => null
    ], [
        'id' => $user['id']
    ]);

    // Check if the update statement succeeded
    if ($updatedRows > 0) {
        // Return verification success message
        jsonResponse(['success' => true, 'message' => 'Account verified successfully!']);
    } else {
        // Return internal update error response
        jsonResponse(['success' => false, 'message' => 'Failed to update verification status.'], 500);
    }
} else {
    // Return token mismatch error response
    jsonResponse(['success' => false, 'message' => 'Invalid or expired verification link.'], 400);
}
?>
