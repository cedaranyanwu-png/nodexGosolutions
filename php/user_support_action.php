<?php
/**
 * user_support_action.php
 *
 * Dedicated client-side API endpoint for standard tenant users to manage support tickets.
 * Allows tenants to open new tickets, view their own ticket history, and send follow-up replies.
 * Strictly enforces user ownership and session authentication.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

secureSession();
enforceAuth(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$action = cleanInput($_POST['action'] ?? '');
$userId = (int)($_SESSION['user_id'] ?? 0);
$userEmail = $_SESSION['email'] ?? '';

$conn->createTable('tickets');

switch ($action) {

    case 'create_ticket':
        $title = cleanInput($_POST['title'] ?? '');
        $category = cleanInput($_POST['category'] ?? 'General');
        $message = cleanInput($_POST['message'] ?? '');
        $websiteId = (int)($_POST['website_id'] ?? 0);

        if (empty($title) || empty($message)) {
            jsonResponse(['success' => false, 'message' => 'Please provide both ticket title and message details.'], 400);
        }

        $newTicket = [
            'user_id' => $userId,
            'email' => $userEmail,
            'title' => $title,
            'category' => $category,
            'message' => $message,
            'website_id' => $websiteId,
            'status' => 'open',
            'replies' => json_encode([]),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $inserted = $conn->insert('tickets', $newTicket);
        if ($inserted) {
            jsonResponse(['success' => true, 'message' => 'Support ticket created successfully!', 'ticket' => $inserted]);
        } else {
            jsonResponse(['success' => false, 'message' => 'Failed to save support ticket.'], 500);
        }
        break;

    case 'add_reply':
        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $replyText = cleanInput($_POST['reply'] ?? '');

        if (empty($replyText)) {
            jsonResponse(['success' => false, 'message' => 'Reply message cannot be empty.'], 400);
        }

        $ticket = $conn->selectOne('tickets', ['id' => $ticketId, 'user_id' => $userId]);
        if (!$ticket) {
            jsonResponse(['success' => false, 'message' => 'Ticket not found or ownership mismatch.'], 404);
        }

        $replies = !empty($ticket['replies']) ? json_decode((string)$ticket['replies'], true) : [];
        if (!is_array($replies)) { $replies = []; }

        $replies[] = [
            'sender' => 'user',
            'sender_name' => $_SESSION['fullname'] ?? 'Tenant User',
            'message' => $replyText,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $conn->update('tickets', [
            'replies' => json_encode($replies),
            'status' => 'open',
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $ticketId]);

        jsonResponse(['success' => true, 'message' => 'Reply sent successfully!']);
        break;

    case 'get_tickets':
        $tickets = $conn->select('tickets', ['user_id' => $userId]) ?: [];
        jsonResponse(['success' => true, 'tickets' => $tickets]);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Unrecognized support action.'], 400);
        break;
}
?>
