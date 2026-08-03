<?php
/**
 * Reusable Email Sender for nodexGosolutions
 * Usage: include 'send_email.php'; sendEmail($to, $subject, $message);
 */

function sendEmail($to, $subject, $message, $fromName = 'nodexGosolutions', $fromEmail = 'support@nodexplatform.com.ng') {
    // Validate recipient email
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid email address provided: $to");
        return false;
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . $fromName . ' <' . $fromEmail . '>',
        'Reply-To: ' . $fromEmail,
        'X-Mailer: PHP/' . phpversion(),
        'X-Priority: 3'
    ];

    // Attempt to send
    $result = @mail($to, $subject, $message, implode("\r\n", $headers));

    if (!$result) {
        error_log("Failed to send email to: $to | Subject: $subject");
        return false;
    }

    return true;
}
?>