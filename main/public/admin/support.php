<?php
/**
 * support.php
 *
 * Standalone Customer Support Ticket Queues page.
 */

declare(strict_types=1);

$pageTitle = 'Support Tickets Queue';
$pageSubtitle = 'Review customer support inquiries, send responses, and resolve tickets.';

require_once __DIR__ . '/admin_header.php';

$conn->createTable('tickets');
$allTickets = $conn->select('tickets') ?: [];
?>

<div class="card border-0 shadow-sm rounded-xl">
  <div class="card-header bg-white border-b border-gray-100 py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <h3 class="text-base font-bold text-gray-800 m-0"><i class="fas fa-ticket text-emerald-600 me-2"></i> Client Support Queues</h3>
    <div class="d-flex align-items-center gap-2">
      <input type="text" id="ticketSearchInput" class="form-control form-control-sm text-xs rounded-md" placeholder="Search tickets..." style="width: 180px;">
      <select id="ticketStatusFilter" class="form-select form-select-sm text-xs rounded-md" style="width: 140px;">
        <option value="all">All Statuses</option>
        <option value="open">Open</option>
        <option value="in-progress">In-Progress</option>
        <option value="resolved">Resolved</option>
      </select>
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0 text-xs" id="adminTicketsTable">
        <thead class="bg-gray-50 text-gray-600 font-semibold">
          <tr>
            <th class="p-3.5">ID</th>
            <th class="p-3.5">User Email</th>
            <th class="p-3.5">Subject</th>
            <th class="p-3.5">Category</th>
            <th class="p-3.5">Status</th>
            <th class="p-3.5">Date</th>
            <th class="p-3.5 text-end">Actions</th>
          </tr>
        </thead>
        <tbody class="text-gray-700" id="adminTicketsTableBody">
          <?php if (count($allTickets) > 0): ?>
            <?php foreach (array_reverse($allTickets) as $tk):
              $tkStatus = strtolower((string)($tk['status'] ?? 'open'));
            ?>
              <tr class="admin-ticket-row" data-status="<?php echo htmlspecialchars($tkStatus); ?>">
                <td class="p-3.5 font-bold">#<?php echo (int)($tk['id'] ?? 0); ?></td>
                <td class="p-3.5 text-gray-600 font-mono"><?php echo htmlspecialchars($tk['email'] ?? ''); ?></td>
                <td class="p-3.5 font-semibold text-gray-800"><?php echo htmlspecialchars($tk['title'] ?? ''); ?></td>
                <td class="p-3.5 text-gray-500"><?php echo htmlspecialchars($tk['category'] ?? 'General'); ?></td>
                <td class="p-3.5">
                  <?php if ($tkStatus === 'open'): ?>
                    <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill font-bold px-2 py-1">Open</span>
                  <?php elseif ($tkStatus === 'in-progress'): ?>
                    <span class="badge bg-info bg-opacity-10 text-info rounded-pill font-bold px-2 py-1">In-Progress</span>
                  <?php else: ?>
                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill font-bold px-2 py-1">Resolved</span>
                  <?php endif; ?>
                </td>
                <td class="p-3.5 text-gray-400"><?php echo date('M d, Y H:i', strtotime($tk['created_at'] ?? 'now')); ?></td>
                <td class="p-3.5 text-end">
                  <button class="btn btn-xs btn-outline-primary rounded-md btn-admin-reply-ticket"
                    data-id="<?php echo (int)($tk['id'] ?? 0); ?>"
                    data-email="<?php echo htmlspecialchars($tk['email'] ?? ''); ?>"
                    data-title="<?php echo htmlspecialchars($tk['title'] ?? ''); ?>"
                    data-message="<?php echo htmlspecialchars($tk['message'] ?? ''); ?>"
                    data-status="<?php echo htmlspecialchars($tkStatus); ?>"
                    data-replies='<?php echo htmlspecialchars($tk['replies'] ?? '[]'); ?>'>
                    <i class="fa-solid fa-reply me-1"></i> View & Reply
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="text-center text-gray-400 py-8">
                <i class="fas fa-ticket text-3xl mb-2 text-gray-300 block"></i>
                No customer support tickets in queue.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ADMIN SUPPORT TICKET REPLY MODAL -->
<div class="modal fade" id="adminTicketReplyModal" tabindex="-1" aria-labelledby="adminTicketReplyModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content rounded-xl border-0 shadow-2xl">
      <div class="modal-header border-b border-gray-100 bg-slate-900 text-white rounded-t-xl py-3">
        <h5 class="modal-title font-bold flex items-center text-sm" id="adminTicketReplyModalLabel"><i class="fa-solid fa-headset me-2 text-emerald-400"></i> Manage Support Ticket</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4 text-xs">
        <input type="hidden" id="adminTicketId" value="">

        <div class="mb-3 border-b pb-2">
          <div class="d-flex justify-between items-center mb-1">
            <h4 class="font-bold text-gray-800 text-sm m-0" id="adminTicketTitle"></h4>
            <span class="text-gray-400 font-mono text-2xs" id="adminTicketEmail"></span>
          </div>
          <div class="bg-gray-50 border border-gray-200 rounded-md p-2.5 mt-1 text-gray-700" id="adminTicketOriginalMsg"></div>
        </div>

        <div class="mb-3">
          <span class="text-gray-400 block font-bold text-2xs uppercase mb-2">Conversation Thread:</span>
          <div id="adminTicketThreadContainer" class="flex flex-col gap-2 max-h-60 overflow-y-auto p-2 bg-gray-50 rounded-lg border border-gray-100">
          </div>
        </div>

        <form id="adminTicketReplyForm" class="mt-3">
          <div class="mb-3">
            <label class="block text-2xs font-bold uppercase text-gray-400 mb-1">Update Status</label>
            <select id="adminTicketStatusSelect" class="form-select form-select-sm text-xs rounded-md" style="width: 180px;">
              <option value="open">Open</option>
              <option value="in-progress">In-Progress</option>
              <option value="resolved">Resolved</option>
            </select>
          </div>
          <div class="mb-2">
            <textarea id="adminTicketReplyInput" class="form-control text-xs rounded-md border-gray-200" rows="3" placeholder="Type support reply to user..." required></textarea>
          </div>
          <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs py-2 px-3 rounded-lg border-0 transition">
            <i class="fas fa-paper-plane me-1"></i> Send Support Response
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function() {
    $(document).on('click', '.btn-admin-reply-ticket', function() {
        const id = $(this).data('id');
        const email = $(this).data('email');
        const title = $(this).data('title');
        const message = $(this).data('message');
        const status = $(this).data('status');
        const replies = $(this).data('replies');

        $('#adminTicketId').val(id);
        $('#adminTicketTitle').text(title);
        $('#adminTicketEmail').text(email);
        $('#adminTicketOriginalMsg').text(message);
        $('#adminTicketStatusSelect').val(status);

        const threadContainer = $('#adminTicketThreadContainer');
        threadContainer.empty();

        if (replies && replies.length > 0) {
            replies.forEach(function(rep) {
                const isSupport = rep.sender === 'support';
                const bgClass = isSupport ? 'bg-blue-100 text-blue-900 self-end border-blue-200' : 'bg-gray-200 text-gray-800 self-start';
                const senderLabel = isSupport ? '<i class="fas fa-headset me-1 text-blue-600"></i> Staff (' + rep.sender_name + ')' : '<i class="fas fa-user me-1 text-gray-600"></i> User';

                const msgHtml = `
                    <div class="p-2.5 rounded-lg border text-xs max-w-lg mb-1 ${bgClass}">
                        <div class="font-bold text-2xs mb-0.5">${senderLabel} <span class="text-gray-400 font-normal ms-2">${rep.created_at || ''}</span></div>
                        <div>${rep.message}</div>
                    </div>
                `;
                threadContainer.append(msgHtml);
            });
        } else {
            threadContainer.html('<div class="text-center text-gray-400 py-3 text-2xs">No replies in thread yet.</div>');
        }

        const ticketModal = new bootstrap.Modal(document.getElementById('adminTicketReplyModal'));
        ticketModal.show();
    });

    $('#adminTicketReplyForm').on('submit', function(e) {
        e.preventDefault();
        const id = $('#adminTicketId').val();
        const reply = $('#adminTicketReplyInput').val().trim();
        const status = $('#adminTicketStatusSelect').val();

        $.ajax({
            url: '/php/admin_support_action.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'reply_ticket', ticket_id: id, reply: reply, status: status },
            success: function(res) {
                if (res.success) {
                    alert(res.message);
                    location.reload();
                } else {
                    alert(res.message || 'Failed to submit response.');
                }
            }
        });
    });

    $('#ticketSearchInput, #ticketStatusFilter').on('input change', function() {
        const search = $('#ticketSearchInput').val().toLowerCase();
        const status = $('#ticketStatusFilter').val().toLowerCase();

        $('.admin-ticket-row').each(function() {
            const rowStatus = $(this).data('status').toLowerCase();
            const rowText = $(this).text().toLowerCase();

            const matchesSearch = rowText.includes(search);
            const matchesStatus = (status === 'all' || rowStatus === status);

            if (matchesSearch && matchesStatus) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
