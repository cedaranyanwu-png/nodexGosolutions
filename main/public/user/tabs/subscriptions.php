<?php
/**
 * main/public/user/tabs/subscriptions.php
 *
 * User Subscription & Upgrade Control Center
 * Displays active plan details, free trial countdown, custom domain eligibility,
 * available plan upgrade grid, and complete transaction payment history.
 */

declare(strict_types=1);

$tabId = 'subscriptions';
if (!isset($dashboardContext)) {
    $requestedTab = $tabId;
    require_once __DIR__ . '/../dashboard.php';
    exit;
}

require_once __DIR__ . '/../../../../backend/database/db.php';
require_once __DIR__ . '/../../../../backend/services/SubscriptionService.php';
require_once __DIR__ . '/../../../../backend/services/PaymentService.php';

secureSession();

$userEmail = $_SESSION['email'] ?? '';
$user = $conn->selectOne('users', ['email' => $userEmail]);

if ($user) {
    $subService = new SubscriptionService($conn);
    $subData = $subService->getUserSubscription($user);

    $paymentService = new PaymentService($conn);
    $paymentHistory = $paymentService->getUserPaymentHistory((int)$user['id']);
    $activePlans = $subService->getActivePlans();
} else {
    $subData = [];
    $paymentHistory = [];
    $activePlans = [];
}
?>

<div id="subscriptions" class="tab-section <?php echo ($activeTab === 'subscriptions') ? 'active-tab' : ''; ?>">

  <!-- Current Subscription Overview Card -->
  <div class="bg-gradient-to-r from-slate-900 via-blue-900 to-indigo-950 text-white rounded-2xl p-6 shadow-xl mb-6 flex-shrink-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4">
      <div>
        <div class="d-flex align-items-center gap-2 mb-2">
          <span class="text-xs uppercase tracking-wider font-bold text-blue-300">Active Membership</span>
          <?php if (($subData['status'] ?? '') === 'trial'): ?>
            <span class="badge bg-amber-500 text-white text-xs px-2.5 py-1 rounded-full"><i class="fas fa-clock mr-1"></i> Free Trial</span>
          <?php elseif (($subData['status'] ?? '') === 'active'): ?>
            <span class="badge bg-emerald-500 text-white text-xs px-2.5 py-1 rounded-full"><i class="fas fa-check-circle mr-1"></i> Active Paid Plan</span>
          <?php else: ?>
            <span class="badge bg-rose-500 text-white text-xs px-2.5 py-1 rounded-full"><i class="fas fa-exclamation-triangle mr-1"></i> Subscription Expired</span>
          <?php endif; ?>
        </div>

        <h3 class="text-2xl font-extrabold mb-1"><?php echo htmlspecialchars((string)($subData['plan_name'] ?? 'Starter Space')); ?></h3>
        <p class="text-sm text-slate-300 mb-0">
          <?php if (($subData['status'] ?? '') === 'trial'): ?>
            Trial expires in <strong class="text-white"><?php echo (int)($subData['days_remaining'] ?? 0); ?> days</strong> (End Date: <?php echo htmlspecialchars((string)($subData['trial_end'] ?? 'N/A')); ?>).
          <?php elseif (($subData['status'] ?? '') === 'active'): ?>
            Subscription active through <strong class="text-white"><?php echo htmlspecialchars((string)($subData['subscription_end'] ?? 'N/A')); ?></strong>.
          <?php else: ?>
            Your subscription has expired. Please select a plan below to renew hosting features.
          <?php endif; ?>
        </p>
      </div>

      <div class="d-flex flex-wrap gap-3 align-items-center">
        <!-- Domain Eligibility Indicator -->
        <?php if (!empty($subData['allows_custom_domain'])): ?>
          <div class="bg-white/10 backdrop-blur-md rounded-xl p-3 text-center border border-white/20">
            <span class="text-2xs text-blue-200 block uppercase font-bold">Domain Eligibility</span>
            <span class="text-xs font-bold text-emerald-400"><i class="fas fa-globe mr-1"></i> Custom Domain Unlocked</span>
          </div>
        <?php else: ?>
          <div class="bg-white/10 backdrop-blur-md rounded-xl p-3 text-center border border-white/20">
            <span class="text-2xs text-slate-300 block uppercase font-bold">Domain Eligibility</span>
            <span class="text-xs font-bold text-amber-300"><i class="fas fa-link mr-1"></i> Platform Subdomain Only</span>
          </div>
        <?php endif; ?>

        <button class="btn btn-primary bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs py-2.5 px-4 rounded-xl border-0 shadow-lg" data-bs-toggle="modal" data-bs-target="#pricingModal">
          <i class="fas fa-arrow-up-right-from-square me-1"></i> Upgrade Membership
        </button>
      </div>
    </div>
  </div>

  <!-- Available Plans Comparison Grid -->
  <div class="mb-6 flex-shrink-0">
    <h4 class="font-bold text-gray-800 text-lg mb-3"><i class="fas fa-layer-group text-blue-600 me-2"></i> Available Subscription Plans</h4>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <?php foreach ($activePlans as $plan):
        $isCurrentPlan = (strtolower((string)($subData['plan_name'] ?? '')) === strtolower((string)$plan['name']));
        $allowsCustom = !str_contains(strtolower((string)$plan['name']), 'micro') && !str_contains(strtolower((string)$plan['name']), 'starter');
      ?>
        <div class="bg-white border <?php echo $isCurrentPlan ? 'border-blue-500 ring-2 ring-blue-500/20' : 'border-gray-200'; ?> rounded-2xl p-5 shadow-sm flex flex-col justify-between hover-translate">
          <div>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h5 class="font-extrabold text-gray-900 mb-0 text-base"><?php echo htmlspecialchars((string)$plan['name']); ?></h5>
              <?php if ($isCurrentPlan): ?>
                <span class="badge bg-blue-100 text-blue-700 text-2xs uppercase font-bold px-2 py-1 rounded-md">Current</span>
              <?php endif; ?>
            </div>

            <div class="my-3">
              <span class="text-2xl font-black text-gray-900">₦<?php echo number_format((float)$plan['price']); ?></span>
              <span class="text-xs text-gray-500">/ <?php echo htmlspecialchars((string)($plan['billing_period'] ?? 'month')); ?></span>
            </div>

            <p class="text-xs text-gray-600 mb-4"><?php echo htmlspecialchars((string)($plan['description'] ?? '')); ?></p>

            <ul class="space-y-2 text-xs text-gray-600 mb-4 pl-0 list-none">
              <li class="flex items-center gap-2">
                <i class="fas fa-check-circle text-emerald-500 text-xs"></i> 1 Subdomain Workspace
              </li>
              <li class="flex items-center gap-2">
                <i class="fas <?php echo $allowsCustom ? 'fa-check-circle text-emerald-500' : 'fa-times-circle text-gray-300'; ?> text-xs"></i>
                <?php echo $allowsCustom ? 'Connect Custom Domain (yourdomain.com)' : 'Custom Domain (Growth+ plans)'; ?>
              </li>
              <li class="flex items-center gap-2">
                <i class="fas fa-check-circle text-emerald-500 text-xs"></i> Full File Manager & Editor
              </li>
            </ul>
          </div>

          <button class="btn btn-process-payment w-full py-2.5 px-4 font-bold text-xs rounded-xl border-0 transition-all <?php echo $isCurrentPlan ? 'bg-gray-100 text-gray-500 cursor-default' : 'bg-blue-600 text-white hover:bg-blue-700 shadow-md'; ?>"
                  data-plan-id="<?php echo htmlspecialchars((string)$plan['id']); ?>"
                  <?php echo $isCurrentPlan ? 'disabled' : ''; ?>>
            <?php echo $isCurrentPlan ? 'Active Plan' : 'Select & Upgrade'; ?>
          </button>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Payment History Log Card -->
  <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm flex-grow d-flex flex-column min-h-0">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="font-bold text-gray-800 text-base mb-0"><i class="fas fa-receipt text-blue-600 me-2"></i> Payment Transaction History</h4>
      <span class="text-xs text-gray-500">Total Transactions: <?php echo count($paymentHistory); ?></span>
    </div>

    <div class="table-responsive overflow-y-auto flex-grow">
      <table class="table table-hover align-middle text-xs mb-0">
        <thead class="table-light">
          <tr>
            <th>Tx Reference</th>
            <th>Plan</th>
            <th>Amount</th>
            <th>Gateway</th>
            <th>Date</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($paymentHistory)): ?>
            <?php foreach (array_reverse($paymentHistory) as $pay): ?>
              <tr>
                <td class="font-mono text-xs text-gray-700 font-bold"><?php echo htmlspecialchars((string)$pay['tx_ref']); ?></td>
                <td><?php echo htmlspecialchars((string)($pay['plan_name'] ?? 'Membership')); ?></td>
                <td class="font-bold text-gray-900">₦<?php echo number_format((float)($pay['amount'] ?? 0)); ?></td>
                <td><span class="badge bg-slate-100 text-slate-700 uppercase font-mono text-2xs"><?php echo htmlspecialchars((string)($pay['gateway'] ?? 'flutterwave')); ?></span></td>
                <td class="text-gray-500"><?php echo htmlspecialchars((string)($pay['created_at'] ?? '')); ?></td>
                <td>
                  <?php if (($pay['status'] ?? '') === 'successful'): ?>
                    <span class="badge bg-emerald-100 text-emerald-800 text-2xs font-bold px-2 py-1 rounded-md"><i class="fas fa-check-circle mr-1"></i> Successful</span>
                  <?php elseif (($pay['status'] ?? '') === 'pending'): ?>
                    <span class="badge bg-amber-100 text-amber-800 text-2xs font-bold px-2 py-1 rounded-md"><i class="fas fa-spinner fa-spin mr-1"></i> Pending</span>
                  <?php else: ?>
                    <span class="badge bg-rose-100 text-rose-800 text-2xs font-bold px-2 py-1 rounded-md"><i class="fas fa-times-circle mr-1"></i> Failed</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="text-center py-4 text-gray-400">No payment transaction records found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
