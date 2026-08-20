<?php
/**
 * tabs/profile.php
 * User Profile Tab - Profile details & password security
 * Can be included in dashboard.php or accessed directly as a full page
 */
$tabId = 'profile';
if (!isset($dashboardContext)) {
    $requestedTab = $tabId;
    require_once __DIR__ . '/../dashboard.php';
    exit;
}
?>
<!-- TAB 9: USER PROFILE PAGE -->
<div id="profile" class="tab-section <?php echo ($activeTab === 'profile') ? 'active-tab' : ''; ?>">
  <div class="full-page-card border-0 bg-transparent">
    <div class="row g-4 h-100">
      <div class="col-md-6 h-100">
        <div class="card border-0 shadow-sm rounded-xl p-4 bg-white h-100">
          <h3 class="text-base font-bold text-gray-800 mb-3"><i class="fas fa-user-circle text-primary me-2"></i> Profile Details</h3>
          <form action="/user/dashboard#profile" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_profile" />
            <div class="mb-3">
              <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Full Name</label>
              <input type="text" name="fullname" value="<?php echo htmlspecialchars((string)($user['fullname'] ?? '')); ?>" class="form-control text-sm rounded-lg border-gray-200 p-2.5" required />
            </div>
            <div class="mb-3">
              <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Avatar File</label>
              <input type="file" name="avatar_file" class="form-control text-xs rounded-lg border-gray-200" accept="image/*" />
            </div>
            <button type="submit" class="bg-blue-600 text-white font-bold text-xs py-2 px-4 rounded-lg border-0">Save Profile</button>
          </form>
        </div>
      </div>
      <div class="col-md-6 h-100">
        <div class="card border-0 shadow-sm rounded-xl p-4 bg-white h-100">
          <h3 class="text-base font-bold text-gray-800 mb-3"><i class="fas fa-key text-primary me-2"></i> Password & Security</h3>
          <form action="/user/dashboard#profile" method="POST">
            <input type="hidden" name="action" value="update_password" />
            <div class="mb-3">
              <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Current Password</label>
              <input type="password" name="current_password" class="form-control text-sm rounded-lg border-gray-200 p-2.5" required />
            </div>
            <div class="mb-3">
              <label class="block text-xs font-bold text-gray-500 uppercase mb-1">New Password</label>
              <input type="password" name="new_password" class="form-control text-sm rounded-lg border-gray-200 p-2.5" required />
            </div>
            <div class="mb-3">
              <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Confirm New Password</label>
              <input type="password" name="confirm_password" class="form-control text-sm rounded-lg border-gray-200 p-2.5" required />
            </div>
            <button type="submit" class="bg-slate-800 text-white font-bold text-xs py-2 px-4 rounded-lg border-0">Update Password</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>