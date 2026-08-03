<?php
/**
 * resend_verification.php
 *
 * Handles generating and sending a fresh verification token to unverified users using the custom JSON database.
 */

// Enable strict typing for better reliability
declare(strict_types=1);

// Require central database configuration and security helpers from same folder
require_once __DIR__ . '/db.php';

// ============================================================
// 1. VALIDATE REQUEST METHOD
// ============================================================

// Verify that the request is a POST action
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Return method not allowed response
    jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

// ============================================================
// 2. GET AND VALIDATE INPUT
// ============================================================

// Sanitize inputs
$email = trim(strtolower($_POST['email'] ?? ''));
$clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

// Validate email presence
if (empty($email)) {
    // Return empty email bad request
    jsonResponse([
        'success' => false,
        'message' => 'Email address is required'
    ], 400);
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    // Return invalid email format bad request
    jsonResponse([
        'success' => false,
        'message' => 'Invalid email format'
    ], 400);
}

// Validate email length constraints
if (strlen($email) > 255) {
    // Return email too long bad request
    jsonResponse([
        'success' => false,
        'message' => 'Email is too long'
    ], 400);
}

// ============================================================
// 3. RATE LIMITING (Prevent spam)
// ============================================================

// Rate limit by IP (max 3 resends per hour)
$ipRateCheck = checkRateLimit('resend_ip_' . $clientIp, 3, 3600);

// Reject if IP rate limit exceeded
if (!$ipRateCheck['allowed']) {
    // Log violation
    error_log("Resend rate limit exceeded for IP: $clientIp");
    // Return rate limit response
    jsonResponse([
        'success' => false,
        'message' => $ipRateCheck['message']
    ], 429);
}

// Rate limit by email (max 2 resends per hour per email)
$emailRateCheck = checkRateLimit('resend_email_' . $email, 2, 3600);

// Reject if email rate limit exceeded
if (!$emailRateCheck['allowed']) {
    // Log violation
    error_log("Resend rate limit exceeded for email: $email");
    // Return email rate limit response
    jsonResponse([
        'success' => false,
        'message' => 'Too many resend requests for this email. Please try again later.'
    ], 429);
}

// ============================================================
// 4. FIND USER
// ============================================================

try {
    // Retrieve user record from the custom JSON database 'users' table
    $user = $conn->selectOne('users', ['email' => $email]);

    // Security: Don't reveal if email exists or not
    // Always return success message to prevent email enumeration
    if (!$user) {
        // Log the search miss for security audit trail
        error_log("Resend attempt for non-existent email: $email");

        // Simulate delay to prevent timing attacks
        usleep(500000); // 0.5 seconds

        // Return simulated success response
        jsonResponse([
            'success' => true,
            'message' => 'If an account exists with that email, a verification link has been sent.'
        ]);
    }

    // ============================================================
    // 5. CHECK USER STATUS
    // ============================================================

    // Check if user is already verified (is_verified or email_verified)
    $isVerified = (int)($user['is_verified'] ?? $user['email_verified'] ?? 0);
    if ($isVerified === 1) {
        // Log verified user resend request
        error_log("Resend attempt for already verified email: $email");
        // Return already verified bad request response
        jsonResponse([
            'success' => false,
            'message' => 'This email is already verified. Please login to your account.',
            'already_verified' => true
        ], 400);
    }

    // Reject if account status is suspended
    if (isset($user['status']) && $user['status'] === 'suspended') {
        // Log suspended access attempt
        error_log("Resend attempt for suspended account: $email");
        // Return suspended forbidden response
        jsonResponse([
            'success' => false,
            'message' => 'This account has been suspended. Please contact support.'
        ], 403);
    }

    // ============================================================
    // 6. GENERATE NEW VERIFICATION TOKEN
    // ============================================================

    // Generate new secure verification token
    $newToken = generateSecureToken(32);
    // Assign expiration window (24 hours)
    $tokenExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));

    // Update user record with new verification token and expiration fields
    $updatedCount = $conn->update('users', [
        'verification_token' => $newToken,
        'token_expires' => $tokenExpires
    ], ['id' => $user['id']]);

    // Verify that the record update was saved successfully
    if ($updatedCount === 0) {
        // Log write failure
        error_log("Failed to update verification token for user ID: " . $user['id']);
        // Return internal server error
        jsonResponse([
            'success' => false,
            'message' => 'Failed to generate new verification link. Please try again.'
        ], 500);
    }

    // ============================================================
    // 7. SEND VERIFICATION EMAIL
    // ============================================================

    // Build the verification link pointing directly to verify.html
    $verifyUrl = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/verify.html?email=" . urlencode($email) . "&token=" . $newToken;

    // Set email subject line
    $subject = "Verify Your Email Address - nodexGosolutions";

    // Construct HTML template content
    $message = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Verify Your Email</title>
    </head>
    <body style="margin: 0; padding: 0; background: #05070f; font-family: Arial, sans-serif;">
        <table width="100%" cellpadding="0" cellspacing="0" style="background: #05070f; padding: 40px 20px;">
            <tr>
                <td align="center">
                    <table width="600" cellpadding="0" cellspacing="0" style="background: #0a0e27; border-radius: 16px; overflow: hidden;">
                        <tr>
                            <td style="background: linear-gradient(135deg, #6366f1, #06b6d4); padding: 30px; text-align: center;">
                                <h1 style="margin: 0; color: #fff; font-size: 28px; font-weight: bold;">
                                    nodexGosolutions
                                </h1>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 40px 30px; color: #e2e8f0;">
                                <h2 style="margin: 0 0 20px 0; color: #fff; font-size: 24px;">
                                    Verify Your Email Address
                                </h2>
                                <p style="margin: 0 0 20px 0; line-height: 1.6; font-size: 16px;">
                                    Hello <strong>' . htmlspecialchars($user['fullname'] ?? $user['full_name'] ?? 'User') . '</strong>,
                                </p>
                                <p style="margin: 0 0 25px 0; line-height: 1.6; font-size: 16px;">
                                    We received a request to verify your email address for your nodexGosolutions account.
                                    Click the button below to complete the verification:
                                </p>
                                <p style="text-align: center; margin: 30px 0;">
                                    <a href="' . $verifyUrl . '"
                                       style="display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #6366f1, #06b6d4);
                                              color: #fff; text-decoration: none; border-radius: 50px; font-weight: bold; font-size: 16px;">
                                        Verify Email Address
                                    </a>
                                </p>
                                <p style="margin: 30px 0 20px 0; font-size: 14px; color: #94a3b8; line-height: 1.5;">
                                    If the button doesn\'t work, copy and paste this link into your browser:
                                </p>
                                <p style="margin: 0 0 25px 0; padding: 15px; background: rgba(255,255,255,0.05); border-radius: 8px;
                                          font-size: 13px; word-break: break-all; color: #06b6d4;">
                                    ' . $verifyUrl . '
                                </p>
                                <p style="margin: 0 0 20px 0; padding: 15px; background: rgba(251, 191, 36, 0.1);
                                          border-left: 3px solid #fbbf24; border-radius: 4px; font-size: 14px; color: #fbbf24;">
                                    ⏰ This link will expire in <strong>24 hours</strong> for security reasons.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>
    ';

    // Set headers
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: nodexGosolutions <noreply@nodexplatform.com.ng>',
        'Reply-To: hello@nodexplatform.com.ng',
        'X-Mailer: PHP/' . phpversion()
    ];

    // Dispatch the email
    $emailSent = @mail($email, $subject, $message, implode("\r\n", $headers));

    // Log the action attempt in login_attempts table
    $successVal = $emailSent ? 1 : 0;
    $conn->insert('login_attempts', [
        'email' => $email,
        'ip_address' => $clientIp,
        'user_agent' => $userAgent,
        'success' => $successVal,
        'attempted_at' => date('Y-m-d H:i:s')
    ]);

    // Reset rate limits on success
    resetRateLimit('resend_ip_' . $clientIp);
    resetRateLimit('resend_email_' . $email);

    // Log success
    error_log("✅ Verification email resent to: $email");

    // Return verification resent success response
    jsonResponse([
        'success' => true,
        'message' => 'Verification email sent! Please check your inbox (and spam folder).',
        'email' => $email
    ]);

} catch (Exception $e) {
    // Log exception details
    error_log("Resend Error: " . $e->getMessage());
    // Return standard error response
    jsonResponse([
        'success' => false,
        'message' => 'An error occurred. Please try again.'
    ], 500);
}
?>
