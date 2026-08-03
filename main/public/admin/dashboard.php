<?php
/**
 * dashboard.php
 *
 * This is the main dynamic administration dashboard for nodexGosolutions.
 * It restricts access to administrative roles, displays user listings, and integrates the CMS.
 */

// Enable strict typing for safety
declare(strict_types=1);

// Require our system database connection layer
require_once __DIR__ . '/../../php/db.php';

// Instantiate secure session configurations
secureSession();

// Check if user session has administrative authorization
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // If not authorized, redirect back to login screen
    header('Location: /login');
    // Terminate script execution
    exit;
}

// Fetch list of registered users dynamically from system JSON tables database
$usersList = $conn->select('users');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard | nodexGosolutions</title>

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <style>
    :root {
      --bg-dark: #05070f;
      --card-bg: #0a0e27;
      --accent: #00d2ff;
      --accent-hover: #00a2cc;
      --text: #e2e8f0;
      --text-muted: #94a3b8;
    }
    body {
      margin: 0;
      padding: 0;
      background: var(--bg-dark);
      color: var(--text);
      font-family: 'Inter', sans-serif;
    }
    header {
      background: linear-gradient(135deg, #6366f1, #06b6d4);
      padding: 20px 40px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    }
    header h1 {
      margin: 0;
      font-family: 'Orbitron', sans-serif;
      font-size: 24px;
      font-weight: 700;
    }
    .nav-links a {
      color: white;
      text-decoration: none;
      margin-left: 20px;
      font-weight: bold;
      transition: color 0.3s;
    }
    .nav-links a:hover {
      color: var(--accent);
    }
    .container {
      max-width: 1200px;
      margin: 40px auto;
      padding: 0 20px;
    }
    .welcome-section {
      margin-bottom: 30px;
    }
    .welcome-section h2 {
      font-size: 32px;
      margin-bottom: 10px;
      font-family: 'Orbitron', sans-serif;
    }
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 20px;
      margin-bottom: 40px;
    }
    .stat-card {
      background: var(--card-bg);
      border: 1px solid rgba(255,255,255,0.05);
      border-radius: 12px;
      padding: 20px;
      text-align: center;
      transition: transform 0.3s;
    }
    .stat-card:hover {
      transform: translateY(-5px);
    }
    .stat-icon {
      font-size: 32px;
      color: var(--accent);
      margin-bottom: 10px;
    }
    .stat-value {
      font-size: 28px;
      font-weight: bold;
      font-family: 'Orbitron', sans-serif;
      margin: 5px 0;
    }
    .stat-label {
      color: var(--text-muted);
      font-size: 14px;
    }
    .cms-integration {
      background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(6, 182, 212, 0.1));
      border: 1px solid rgba(6, 182, 212, 0.3);
      border-radius: 16px;
      padding: 30px;
      margin-bottom: 40px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 20px;
    }
    .cms-text h3 {
      margin: 0 0 10px 0;
      font-family: 'Orbitron', sans-serif;
      font-size: 22px;
      color: white;
    }
    .cms-text p {
      margin: 0;
      color: var(--text-muted);
    }
    .btn-cms {
      background: linear-gradient(135deg, #6366f1, #06b6d4);
      color: white;
      text-decoration: none;
      padding: 14px 28px;
      border-radius: 50px;
      font-weight: bold;
      font-family: 'Orbitron', sans-serif;
      transition: transform 0.3s, box-shadow 0.3s;
      box-shadow: 0 4px 15px rgba(6, 182, 212, 0.4);
    }
    .btn-cms:hover {
      transform: scale(1.05);
      box-shadow: 0 6px 20px rgba(6, 182, 212, 0.6);
    }
    .card {
      background: var(--card-bg);
      border-radius: 16px;
      border: 1px solid rgba(255,255,255,0.05);
      padding: 30px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.2);
    }
    .card h3 {
      margin-top: 0;
      font-family: 'Orbitron', sans-serif;
      font-size: 20px;
      margin-bottom: 20px;
      border-bottom: 1px solid rgba(255,255,255,0.1);
      padding-bottom: 10px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
    }
    th, td {
      padding: 15px;
      border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    th {
      font-family: 'Orbitron', sans-serif;
      color: var(--accent);
      font-size: 13px;
      text-transform: uppercase;
    }
    tr:hover {
      background: rgba(255,255,255,0.02);
    }
    .role-badge {
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: bold;
    }
    .role-admin {
      background: rgba(239, 68, 68, 0.15);
      color: #ef4444;
      border: 1px solid rgba(239, 68, 68, 0.3);
    }
    .role-tenant {
      background: rgba(16, 185, 129, 0.15);
      color: #10b981;
      border: 1px solid rgba(16, 185, 129, 0.3);
    }
    .status-badge {
      display: inline-block;
      width: 8px;
      height: 8px;
      border-radius: 50%;
      margin-right: 6px;
    }
    .status-verified {
      background-color: #10b981;
      box-shadow: 0 0 8px #10b981;
    }
    .status-unverified {
      background-color: #f59e0b;
      box-shadow: 0 0 8px #f59e0b;
    }
  </style>
</head>
<body>

  <!-- Header Section -->
  <header>
    <h1><i class="fa-solid fa-user-shield me-2"></i>nodexGo Admin</h1>
    <div class="nav-links">
      <a href="/cms/admin.php"><i class="fa-solid fa-code me-1"></i>CMS Code Panel</a>
      <a href="/" target="_blank"><i class="fa-solid fa-globe me-1"></i>Visit Site</a>
    </div>
  </header>

  <!-- Container for Main Grid -->
  <div class="container">

    <!-- Welcome section -->
    <div class="welcome-section">
      <h2>Welcome Back, <?php echo htmlspecialchars($_SESSION['fullname'] ?? 'Administrator'); ?>!</h2>
      <p style="color: var(--text-muted);">You are logged in with full system administrative privileges. Monitor users and manage CMS codes.</p>
    </div>

    <!-- Quick Stats Metrics -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
        <div class="stat-value"><?php echo count($usersList); ?></div>
        <div class="stat-label">Total Registered Users</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-server"></i></div>
        <div class="stat-value">99.98%</div>
        <div class="stat-label">Server Health Status</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-microchip"></i></div>
        <div class="stat-value">4.1 ms</div>
        <div class="stat-label">Average API Latency</div>
      </div>
    </div>

    <!-- CMS Integration Banner (CMS added to Admin Panel) -->
    <div class="cms-integration">
      <div class="cms-text">
        <h3><i class="fa-solid fa-laptop-code me-2"></i>Code-First CMS Engine Control Panel</h3>
        <p>Directly inspect, edit, and write beautiful raw HTML/JS code payloads to expand platform pages instantly.</p>
      </div>
      <a href="/cms/admin.php" class="btn-cms"><i class="fa-solid fa-rocket me-2"></i>Launch CMS Editor</a>
    </div>

    <!-- Registered User Listing Details Table -->
    <div class="card">
      <h3><i class="fa-solid fa-list me-2"></i>Registered System Users</h3>
      <div style="overflow-x: auto;">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Full Name</th>
              <th>Email Address</th>
              <th>Role Privilege</th>
              <th>Email Verified</th>
              <th>Registered At</th>
            </tr>
          </thead>
          <tbody>
            <?php // Loop through dynamic users list and display matching rows
            foreach ($usersList as $user): ?>
              <tr>
                <td><?php echo (int)($user['id'] ?? 0); ?></td>
                <td><strong><?php echo htmlspecialchars($user['fullname'] ?? $user['full_name'] ?? ''); ?></strong></td>
                <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                <td>
                  <?php $role = $user['role'] ?? 'tenant'; ?>
                  <span class="role-badge <?php echo $role === 'admin' ? 'role-admin' : 'role-tenant'; ?>">
                    <?php echo htmlspecialchars(strtoupper($role)); ?>
                  </span>
                </td>
                <td>
                  <?php $verified = (int)($user['is_verified'] ?? $user['email_verified'] ?? 0) === 1; ?>
                  <span class="status-badge <?php echo $verified ? 'status-verified' : 'status-unverified'; ?>"></span>
                  <?php echo $verified ? 'VERIFIED' : 'PENDING'; ?>
                </td>
                <td><?php echo htmlspecialchars($user['created_at'] ?? 'N/A'); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

</body>
</html>
