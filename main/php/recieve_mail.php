<?php
/**
 * Email Receiver using IMAP
 * Fetches unread emails and returns them as JSON.
 */

// IMAP Configuration (Update with your actual cPanel/hosting email credentials)
$hostname = '{mail.nodexplatform.com.ng:993/imap/ssl}INBOX'; // Adjust port/ssl as per your host
$username = 'support@nodexplatform.com.ng';
$password = 'YOUR_EMAIL_ACCOUNT_PASSWORD'; // Use an App Password if using Gmail/Outlook

// Connect to mailbox
$inbox = imap_open($hostname, $username, $password) or die(json_encode([
    'success' => false,
    'message' => 'Cannot connect to mailbox: ' . imap_last_error()
]));

// Search for unread emails
$emails = imap_search($inbox, 'UNSEEN');
$response = [];

if ($emails) {
    // Sort emails, newest first
    rsort($emails);

    foreach ($emails as $email_number) {
        $overview = imap_fetch_overview($inbox, $email_number, 0);

        // Fetch body: 2 = HTML, 1 = Plain text
        $message = imap_fetchbody($inbox, $email_number, 2);
        if (empty(trim($message))) {
            $message = imap_fetchbody($inbox, $email_number, 1);
        }

        $response[] = [
            'id' => $email_number,
            'from' => $overview[0]->from,
            'subject' => $overview[0]->subject,
            'date' => $overview[0]->date,
            'message' => quoted_printable_decode($message),
            'seen' => (bool)$overview[0]->seen
        ];

        // Mark as seen after reading
        imap_setflag_full($inbox, $email_number, "\\Seen");
    }
}

imap_close($inbox);

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'count' => count($response),
    'emails' => $response
]);
?>