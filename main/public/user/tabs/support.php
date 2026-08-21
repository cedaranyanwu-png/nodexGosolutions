<?php
declare(strict_types=1);

$tabId = 'support';
if (!isset($dashboardContext)) {
    $requestedTab = $tabId;
    require_once __DIR__ . '/../dashboard.php';
    exit;
}

$conn->createTable('tickets');
$userTickets = $conn->select('tickets', ['user_id' => (int)$user['id']]) ?: [];
?>

<div id="support" class="tab-section <?php echo ($activeTab === 'support') ? 'active-tab' : ''; ?>">
  <div class="card border-0 shadow-sm rounded-2xl">
    <div class="card-header bg-white border-b border-gray-100 p-4">
      <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
        <div>
          <h3 class="text-base font-bold text-gray-800 mb-1"><i class="fas fa-headset text-blue-600 me-2"></i>Support Tickets</h3>
          <p class="text-xs text-gray-500 mb-0">Open a support request and keep the conversation in one place.</p>
        </div>
        <span class="badge bg-blue-50 text-blue-700 rounded-pill px-3 py-2"><?php echo count($userTickets); ?> ticket<?php echo count($userTickets) === 1 ? '' : 's'; ?></span>
      </div>
    </div>
    <div class="card-body p-4">
      <form id="userSupportTicketForm" class="border rounded-2xl p-3 mb-4 bg-slate-50">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label text-xs fw-bold text-gray-600">Subject</label>
            <input type="text" name="title" class="form-control text-sm rounded-lg" maxlength="120" required>
          </div>
          <div class="col-md-3">
            <label class="form-label text-xs fw-bold text-gray-600">Category</label>
            <select name="category" class="form-select text-sm rounded-lg">
              <option>General</option><option>Website</option><option>File Manager</option><option>Billing</option><option>Technical</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label text-xs fw-bold text-gray-600">Website</label>
            <select name="website_id" class="form-select text-sm rounded-lg">
              <option value="0">Not specific to a website</option>
              <?php foreach ($myWebsites as $website): ?>
                <option value="<?php echo (int)($website['id'] ?? 0); ?>"><?php echo htmlspecialchars((string)($website['name'] ?? $website['subdomain'] ?? 'Website')); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label text-xs fw-bold text-gray-600">Message</label>
            <textarea name="message" class="form-control text-sm rounded-lg" rows="4" maxlength="5000" required></textarea>
          </div>
          <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary bg-blue-600 border-0 rounded-lg text-xs fw-bold px-4">Submit Ticket</button>
          </div>
        </div>
      </form>

      <div id="userSupportTicketList" class="d-flex flex-column gap-2">
        <?php if (empty($userTickets)): ?>
          <div class="text-center text-gray-400 text-xs py-5">No support tickets yet.</div>
        <?php else: ?>
          <?php foreach (array_reverse($userTickets) as $ticket): ?>
            <div class="border rounded-xl p-3 bg-white">
              <div class="d-flex justify-content-between gap-3">
                <div>
                  <div class="text-sm fw-bold text-gray-800"><?php echo htmlspecialchars((string)($ticket['title'] ?? 'Support request')); ?></div>
                  <div class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars((string)($ticket['message'] ?? '')); ?></div>
                </div>
                <span class="badge <?php echo strtolower((string)($ticket['status'] ?? 'open')) === 'closed' ? 'bg-gray-100 text-gray-600' : 'bg-green-50 text-green-700'; ?> align-self-start rounded-pill"><?php echo htmlspecialchars(ucfirst((string)($ticket['status'] ?? 'open'))); ?></span>
              </div>
              <div class="text-2xs text-gray-400 mt-2"><?php echo htmlspecialchars((string)($ticket['created_at'] ?? '')); ?></div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  const form = document.getElementById('userSupportTicketForm');
  if (!form || form.dataset.bound === '1') return;
  form.dataset.bound = '1';
  form.addEventListener('submit', async function (event) {
    event.preventDefault();
    const button = form.querySelector('button[type="submit"]');
    button.disabled = true;
    try {
      const data = new FormData(form);
      data.append('action', 'create_ticket');
      const response = await fetch('/php/user_support_action.php', { method: 'POST', body: data, headers: { 'Accept': 'application/json' } });
      const result = await response.json();
      if (!response.ok || !result.success) throw new Error(result.message || 'Unable to create ticket.');
      window.location.reload();
    } catch (error) {
      window.alert(error.message || 'Unable to create ticket.');
      button.disabled = false;
    }
  });
})();
</script>

<?php
?>
