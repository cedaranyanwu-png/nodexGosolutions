<?php
/**
 * admin_support_action.php
 *
 * Dedicated administrative API endpoint for staff members (Support, Admin, Super Admin)
 * to process and manage support tickets, send responses, and resolve user issues.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';

secureSession();
enforceAuth(true);

if (!checkAdminPermission('tickets.view') && !checkAdminPermission('tickets.manage')) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized: Support tickets permission required.'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

$action = cleanInput($_POST['action'] ?? '');
$conn->createTable('tickets');

switch ($action) {

    case 'list_tickets':
        $tickets = $conn->select('tickets') ?: [];
        jsonResponse(['success' => true, 'tickets' => $tickets]);
        break;

    case 'reply_ticket':
        if (!checkAdminPermission('tickets.manage')) {
            jsonResponse(['success' => false, 'message' => 'Access Denied: You do not possess tickets.manage capability.'], 403);
        }

        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $replyText = cleanInput($_POST['reply'] ?? '');
        $newStatus = cleanInput($_POST['status'] ?? 'in-progress');

        if ($ticketId <= 0 || empty($replyText)) {
            jsonResponse(['success' => false, 'message' => 'Please fill in reply details.'], 400);
        }

        $ticket = $conn->selectOne('tickets', ['id' => $ticketId]);
        if (!$ticket) {
            jsonResponse(['success' => false, 'message' => 'Support ticket record not found.'], 404);
        }

        $replies = !empty($ticket['replies']) ? json_decode((string)$ticket['replies'], true) : [];
        if (!is_array($replies)) { $replies = []; }

        $staffName = $_SESSION['fullname'] ?? 'Support Team';
        $replies[] = [
            'sender' => 'support',
            'sender_name' => $staffName,
            'message' => $replyText,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $conn->update('tickets', [
            'replies' => json_encode($replies),
            'status' => $newStatus,
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $ticketId]);

        // Audit log
        $conn->createTable('activity_logs');
        $conn->insert('activity_logs', [
            'user_id' => $_SESSION['user_id'] ?? 0,
            'email' => $_SESSION['email'] ?? 'support',
            'action' => 'ticket_replied',
            'details' => "Replied to ticket ID:{$ticketId} (" . ($ticket['title'] ?? '') . ")",
            'created_at' => date('Y-m-d H:i:s')
        ]);

        jsonResponse(['success' => true, 'message' => 'Support response dispatched successfully!']);
        break;

    case 'update_status':
        if (!checkAdminPermission('tickets.manage')) {
            jsonResponse(['success' => false, 'message' => 'Access Denied: Missing tickets.manage capability.'], 403);
        }

        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $newStatus = cleanInput($_POST['status'] ?? 'resolved');

        $conn->update('tickets', [
            'status' => $newStatus,
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $ticketId]);

        jsonResponse(['success' => true, 'message' => "Ticket status updated to " . strtoupper($newStatus) . "."]);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Unrecognized support action.'], 400);
        break;
}
?>
