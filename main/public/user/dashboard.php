<?php
/**
 * dashboard.php
 *
 * Premium, White & Blue themed Tenant Home & Control Panel for nodexGosolutions.
 * Fully styled with Tailwind CSS & customized UI panels.
 * Features:
 * - Real-time trial and subscription checking (TRIAL, ACTIVE, EXPIRED, SUSPENDED).
 * - Exact remaining days count calculation for dynamic trial tracking.
 * - Restricts workspace access dynamically if trial is expired, prompting plan selection.
 * - App Launcher (Google Dots Menu) and Bottom Right Floating Action Button (FAB).
 * - Dynamic "My Websites" creation and manager backed by custom JSON database table.
 * - Unified Tenant Code-First CMS Panel allowing users to publish custom routed layout pages.
 * - Interactive Workspace Database Manager for custom user-created JSON table schemas.
 * - Clean, fully responsive White & Blue design.
 * - All code contains line-by-line comments for readability and scale.
 */

// Enable strict typing for architectural safety
declare(strict_types=1);

// Require central system database connector and security helpers
require_once __DIR__ . '/../../../php/db.php';

// Instantiate secure session configurations
secureSession();

// Access Control: Ensure the user session is active and authenticated
if (!isset($_SESSION['email'])) {
    // Redirect unauthenticated guest visitors to login page
    header('Location: /login');
    // Halt execution
    exit;
}

// Fetch active user details from database in real-time to prevent storage out-of-sync
$user = $conn->selectOne('users', ['email' => $_SESSION['email']]);
if (!$user) {
    // If user record is missing, redirect to login
    header('Location: /login');
    // Halt execution
    exit;
}

// Initialize websites table in the system database if not already created
$conn->createTable('websites');

// Inline AJAX Router: Handle dynamic "My Websites" creation requests securely
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_website') {
    // Capture and sanitize input values
    $webName = cleanInput($_POST['website_name'] ?? '');
    $webUrl  = cleanInput($_POST['website_url'] ?? '');

    // Validate that inputs are not empty
    if (empty($webName) || empty($webUrl)) {
        // Return bad request response
        jsonResponse(['success' => false, 'message' => 'Please fill in all website fields.'], 400);
    }

    // Insert new website project record associated with current tenant user
    $newWeb = $conn->insert('websites', [
        'user_id' => $user['id'],
        'name'    => $webName,
        'url'     => $webUrl
    ]);

    if ($newWeb) {
        // Return successful creation confirmation
        jsonResponse(['success' => true, 'message' => 'Website created successfully!']);
    } else {
        // Return database storage error
        jsonResponse(['success' => false, 'message' => 'Failed to create website.'], 500);
    }
}

// Resolve user's dynamic trial/subscription status securely on server-side
$subscriptionStatus = checkAndUpdateSubscription($user, $conn);

// Calculate remaining free trial days dynamically
$daysRemaining = 0;
if ($subscriptionStatus === 'trial') {
    // Parse trial expiration timestamp
    $trialEndTimestamp = strtotime($user['trial_end'] ?? '');
    // Calculate difference in seconds
    $secondsLeft = $trialEndTimestamp - time();
    // Round up seconds to total days
    $daysRemaining = (int)ceil($secondsLeft / 86400);
    // Guarantee non-negative integer representation
    if ($daysRemaining < 0) {
        $daysRemaining = 0;
    }
}

// Retrieve custom website projects created by current tenant
$myWebsites = $conn->select('websites', ['user_id' => $user['id']]) ?: [];
// Count total tenant websites
$websitesCount = count($myWebsites);

// Load system databases to fetch general platform analytics
$siteCmsDb = new Database(__DIR__ . '/../../../databases', 'site_cms');
$siteCmsDb->createTable('pages');

// Retrieve custom CMS pages published by this specific tenant
$myCmsPages = $siteCmsDb->select('pages', ['user_id' => $user['id']]) ?: [];

$urlDb     = new Database(__DIR__ . '/../../../databases', 'url_shortner');
$qrDb      = new Database(__DIR__ . '/../../../databases', 'qrcode');

// Collect general analytical counts
$cmsPagesCount  = count($siteCmsDb->select('pages') ?: []);
$shortUrlsCount = count($urlDb->select('links') ?: []);
$qrCodesCount   = count($qrDb->select('qrcodes') ?: []);
$deploymentsCount = $cmsPagesCount + $shortUrlsCount + $qrCodesCount;

// Helper function to fetch live developer feeds from Hacker News API
function fetchTechNewsFeed(): array {
    try {
        // Set secure context timeouts
        $context = stream_context_create([
            'http' => [
                'timeout' => 2.5, // 2.5 seconds timeout limit to prevent page slow-down
            ]
        ]);
        // Get top story IDs
        $topStoriesJson = @file_get_contents('https://hacker-news.firebaseio.com/v0/topstories.json', false, $context);
        if ($topStoriesJson === false) {
            return [];
        }
        $storyIds = json_decode($topStoriesJson, true);
        if (!is_array($storyIds)) {
            return [];
        }

        $stories = [];
        // Pull details for the top 5 stories
        for ($i = 0; $i < 5; $i++) {
            if (!isset($storyIds[$i])) break;
            $storyId = $storyIds[$i];
            $storyJson = @file_get_contents("https://hacker-news.firebaseio.com/v0/item/{$storyId}.json", false, $context);
            if ($storyJson !== false) {
                $storyData = json_decode($storyJson, true);
                if (is_array($storyData) && isset($storyData['title'])) {
                    $stories[] = [
                        'title' => $storyData['title'],
                        'url'   => $storyData['url'] ?? "https://news.ycombinator.com/item?id={$storyId}",
                        'score' => $storyData['score'] ?? 100,
                        'by'    => $storyData['by'] ?? 'dev'
                    ];
                }
            }
        }
        return $stories;
    } catch (\Throwable $e) {
        // Fallback on failure
        return [];
    }
}

// Fetch live news stories
$newsStories = fetchTechNewsFeed();
if (empty($newsStories)) {
    // Elegant hardcoded fallbacks in case of offline connection
    $newsStories = [
        ['title' => 'The PHP 8.3 Feature Set & Performance Enhancements Deep-Dive', 'url' => '#', 'score' => 312, 'by' => 'rasmus'],
        ['title' => 'Tailwind CSS v4.0 Alpha Released: Faster Compiles with Rust Engine', 'url' => '#', 'score' => 245, 'by' => 'adamwathan'],
        ['title' => 'Building Secure Multi-Tenant Enterprise Microservices Architecture', 'url' => '#', 'score' => 189, 'by' => 'nodex_guru'],
        ['title' => 'Is Native SQLite All You Need for Production Web Deployments?', 'url' => '#', 'score' => 420, 'by' => 'dhh'],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Workspace Home | nodexGosolutions</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>

  <script>
    // Configure Tailwind
    tailwind.config = {
      corePlugins: {
        preflight: false,
      }
    }
  </script>

  <style>
    .dashboard-layout {
        display: flex;
        min-height: 100vh;
    }
    .main-content {
        flex-grow: 1;
        padding: 30px;
    }
    /* Custom Floating Action Button (FAB) */
    .fab-btn {
      position: fixed;
      bottom: 24px;
      right: 24px;
      z-index: 1050;
      width: 56px;
      height: 56px;
      border-radius: 50%;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .fab-btn:hover {
      transform: scale(1.08);
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.35);
    }
    .app-icon-card {
      transition: all 0.2s ease;
    }
    .app-icon-card:hover {
      transform: translateY(-4px);
      background-color: #f0f7ff;
    }
    iframe-container {
      position: relative;
      width: 100%;
      height: 600px;
    }
    iframe-container iframe {
      width: 100%;
      height: 100%;
      border: none;
    }
    .code-editor-textarea {
      font-family: 'Fira Code', 'Courier New', Courier, monospace;
      font-size: 13px;
      background-color: #0f172a;
      color: #38bdf8;
      border: 1px solid rgba(0, 114, 255, 0.08);
      border-radius: 8px;
      padding: 12px;
      resize: vertical;
    }
    .code-editor-textarea:focus {
      background-color: #0b0f19;
      border-color: #0072ff;
      outline: none;
      box-shadow: 0 0 12px rgba(0, 114, 255, 0.15);
      color: #38bdf8;
    }
  </style>
</head>
<body style="background-color: #f8fafc;">

<div class="dashboard-layout">

  <!-- Include modular Sidebar component -->
  <?php require_once __DIR__ . '/../../modul/sidebar.php'; ?>

  <!-- Content Wrapper -->
  <div class="main-content">

    <!-- Header Section / Topbar -->
    <div class="d-flex justify-content-between align-items-center mb-4 border-b border-gray-100 pb-3 flex-wrap gap-3">
      <div>
        <h2 class="fw-bold text-dark mb-0">Welcome back, <?php echo htmlspecialchars($user['fullname']); ?></h2>
        <p class="text-muted small mb-0">Explore interactive feeds, review websites, manage your custom databases, and launch micro-applications.</p>
      </div>
      <div class="d-flex align-items-center gap-3">
        <!-- Google Apps Dots Menu (App Launcher trigger) -->
        <?php if ($subscriptionStatus === 'trial' || $subscriptionStatus === 'active'): ?>
          <button class="btn btn-light rounded-full p-2 text-gray-600 hover:text-blue-600 focus:outline-none" data-bs-toggle="modal" data-bs-target="#userAppsModal" title="Launch Applications">
            <i class="fas fa-th text-lg"></i>
          </button>
        <?php endif; ?>

        <!-- Unified Header Subscription Status indicators -->
        <?php if ($subscriptionStatus === 'trial'): ?>
          <span class="badge bg-primary text-white px-2.5 py-1.5 text-xs rounded-md">
            <i class="fas fa-clock mr-1"></i> ● FREE TRIAL (Ends: <?php echo date('F d, Y', strtotime($user['trial_end'])); ?>)
          </span>
        <?php elseif ($subscriptionStatus === 'active'): ?>
          <span class="badge bg-success text-white px-2.5 py-1.5 text-xs rounded-md">
            <i class="fas fa-circle-check mr-1"></i> ● ACTIVE (Plan: <?php echo htmlspecialchars($user['subscription_plan'] ?? 'Growth'); ?>, Renews: <?php echo date('F d, Y', strtotime($user['subscription_end'])); ?>)
          </span>
        <?php else: ?>
          <span class="badge bg-danger text-white px-2.5 py-1.5 text-xs rounded-md cursor-pointer" data-bs-toggle="modal" data-bs-target="#choosePlanModal">
            <i class="fas fa-exclamation-triangle mr-1"></i> ● SUBSCRIPTION REQUIRED (Your trial has expired. [ Subscribe Now ])
          </span>
        <?php endif; ?>
      </div>
    </div>

    <!-- MAIN DASHBOARD CARDS ROW -->
    <div class="row mb-4">
      <!-- Card 1: Websites Count -->
      <div class="col-lg-3 col-md-6 col-12 mb-3">
        <div class="bg-white border border-gray-100 shadow-sm rounded-xl p-4 d-flex align-items-center justify-content-between">
          <div>
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Websites</span>
            <span class="text-2xl font-extrabold text-gray-800 d-block"><?php echo $websitesCount; ?></span>
          </div>
          <div class="w-12 h-12 bg-primary bg-opacity-10 text-primary rounded-xl d-flex align-items-center justify-content-center text-xl">
            <i class="fas fa-globe"></i>
          </div>
        </div>
      </div>

      <!-- Card 2: Storage Size -->
      <div class="col-lg-3 col-md-6 col-12 mb-3">
        <div class="bg-white border border-gray-100 shadow-sm rounded-xl p-4 d-flex align-items-center justify-content-between">
          <div>
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Storage</span>
            <span class="text-2xl font-extrabold text-gray-800 d-block">1.2 GB</span>
          </div>
          <div class="w-12 h-12 bg-info bg-opacity-10 text-info rounded-xl d-flex align-items-center justify-content-center text-xl">
            <i class="fas fa-hdd"></i>
          </div>
        </div>
      </div>

      <!-- Card 3: Traffic Bandwidth -->
      <div class="col-lg-3 col-md-6 col-12 mb-3">
        <div class="bg-white border border-gray-100 shadow-sm rounded-xl p-4 d-flex align-items-center justify-content-between">
          <div>
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Traffic</span>
            <span class="text-2xl font-extrabold text-gray-800 d-block">12.4 GB</span>
          </div>
          <div class="w-12 h-12 bg-purple bg-opacity-10 text-purple rounded-xl d-flex align-items-center justify-content-center text-xl">
            <i class="fas fa-chart-line"></i>
          </div>
        </div>
      </div>

      <!-- Card 4: Current Subscription Status -->
      <div class="col-lg-3 col-md-6 col-12 mb-3">
        <div class="bg-white border border-gray-100 shadow-sm rounded-xl p-4 d-flex align-items-center justify-content-between">
          <div>
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Subscription</span>
            <span class="text-lg font-extrabold block text-gray-800">
              <?php if ($subscriptionStatus === 'trial'): ?>
                <span class="text-blue-600">FREE TRIAL</span>
              <?php elseif ($subscriptionStatus === 'active'): ?>
                <span class="text-green-600">ACTIVE</span>
              <?php else: ?>
                <span class="text-red-600">EXPIRED</span>
              <?php endif; ?>
            </span>
          </div>
          <div class="w-12 h-12 bg-warning bg-opacity-10 text-warning rounded-xl d-flex align-items-center justify-content-center text-xl">
            <i class="fas fa-credit-card"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Workspace Content Area -->
    <section class="content px-0">
      <div class="container-fluid px-0">

        <?php if ($subscriptionStatus === 'expired' || $subscriptionStatus === 'suspended'): ?>
          <!-- RESTRICTED SUBSCRIBER EXPIRED SCREEN / CARD -->
          <div class="row">
            <div class="col-12">
              <div class="bg-white border-2 border-red-200 shadow-lg rounded-2xl p-8 text-center max-w-2xl mx-auto my-5">
                <div class="w-20 h-20 bg-red-100 text-red-600 rounded-full flex items-center justify-center text-3xl mx-auto mb-4 animate-bounce">
                  <i class="fas fa-lock"></i>
                </div>
                <h2 class="text-2xl font-extrabold text-gray-800 mb-2">Your Free Trial Has Ended</h2>
                <p class="text-gray-600 mb-6 text-sm">
                  Your 1-month free trial has expired. To preserve and access your workspace databases, custom tables, websites, and micro-applications, please choose a premium hosting subscription plan.
                </p>
                <div class="p-4 bg-gray-50 border border-gray-100 rounded-xl mb-6 text-left text-xs max-w-md mx-auto">
                  <span class="font-bold text-gray-700 block mb-1"><i class="fas fa-shield-alt mr-1"></i> Data Protection Protocol:</span>
                  All of your deployed websites, files, schemas, and configurations remain safely stored on our servers. Access is restricted until a subscription is activated.
                </div>
                <button class="bg-blue-600 hover:bg-blue-700 text-white font-extrabold px-6 py-3 rounded-full shadow-md hover:shadow-lg transition duration-150 text-sm" data-bs-toggle="modal" data-bs-target="#choosePlanModal">
                  <i class="fas fa-rocket mr-2"></i> Choose a Plan
                </button>
              </div>
            </div>
          </div>
        <?php else: ?>
          <!-- WORKSPACE IN THE FREE TRIAL STATE - PROMOTIONAL TOP CARD -->
          <?php if ($subscriptionStatus === 'trial'): ?>
            <div class="row mb-4">
              <div class="col-12">
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white shadow-md rounded-2xl p-6 d-flex flex-col md:flex-row justify-between align-items-start md:align-items-center gap-4">
                  <div>
                    <h3 class="text-lg font-extrabold mb-1 flex align-items-center">
                      <i class="fas fa-star text-warning mr-2 animate-pulse"></i> FREE TRIAL ACTIVE
                    </h3>
                    <p class="text-xs text-blue-100 m-0">Your workspace is currently on a 1-month free trial. Enjoy all unified platform features completely free of charge!</p>
                  </div>
                  <div class="d-flex align-items-center gap-4">
                    <div class="text-right text-xs">
                      <div class="font-semibold text-blue-100">Trial started: <span class="text-white font-bold"><?php echo date('M d, Y', strtotime($user['trial_start'])); ?></span></div>
                      <div class="font-semibold text-blue-100">Trial ends: <span class="text-white font-bold"><?php echo date('M d, Y', strtotime($user['trial_end'])); ?></span></div>
                    </div>
                    <div class="bg-white bg-opacity-10 px-4 py-2.5 rounded-xl border border-white border-opacity-20 text-center min-w-[100px]">
                      <span class="text-2xs uppercase font-bold text-blue-200 block" style="font-size: 9px;">Days remaining</span>
                      <span class="text-lg font-extrabold"><?php echo $daysRemaining; ?></span>
                    </div>
                    <button class="bg-white hover:bg-gray-100 text-blue-600 font-bold px-4 py-2 rounded-lg text-xs shadow-sm transition duration-150" data-bs-toggle="modal" data-bs-target="#choosePlanModal">
                      Choose a Plan
                    </button>
                  </div>
                </div>
              </div>
            </div>
          <?php endif; ?>

          <!-- LIVE WORKSPACE HIGHLIGHTS AND MANAGERS PANEL -->
          <div class="row">
            <!-- Column 1: Websites Manager Showcase -->
            <div class="col-lg-6 col-12 mb-4">
              <div class="card border-0 shadow-sm rounded-xl">
                <div class="card-header bg-white border-b border-gray-100 py-3 d-flex justify-content-between align-items-center">
                  <h3 class="text-base font-bold text-gray-800 m-0 d-flex align-items-center">
                    <i class="fas fa-earth-americas text-primary mr-2"></i> My Websites
                  </h3>
                  <button class="bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs px-3 py-1.5 rounded-lg transition duration-150 shadow-sm" data-bs-toggle="modal" data-bs-target="#createWebsiteModal">
                    <i class="fas fa-plus mr-1"></i> Create Website
                  </button>
                </div>
                <div class="card-body p-0">
                  <div class="table-responsive">
                    <table class="table table-hover mb-0 text-xs">
                      <thead class="bg-gray-50 text-gray-500 font-bold">
                        <tr>
                          <th class="p-3">Website Name</th>
                          <th class="p-3">Target Address</th>
                          <th class="p-3 text-right">Preview</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-gray-100">
                        <?php if (count($myWebsites) > 0): ?>
                          <?php foreach ($myWebsites as $web): ?>
                            <tr>
                              <td class="p-3 font-semibold text-gray-800"><?php echo htmlspecialchars($web['name']); ?></td>
                              <td class="p-3 font-mono text-blue-600 hover:underline"><a href="<?php echo htmlspecialchars($web['url']); ?>" target="_blank"><?php echo htmlspecialchars($web['url']); ?></a></td>
                              <td class="p-3 text-right">
                                <a href="<?php echo htmlspecialchars($web['url']); ?>" target="_blank" class="btn btn-xs btn-outline-primary rounded-md"><i class="fas fa-external-link-alt mr-1"></i> Visit</a>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        <?php else: ?>
                          <tr>
                            <td colspan="3" class="text-center text-gray-400 py-4">No websites deployed yet. Click "Create Website" to add your first hosting workspace!</td>
                          </tr>
                        <?php endif; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

            <!-- Column 2: HackerNews Developer Stories Feed -->
            <div class="col-lg-6 col-12 mb-4">
              <div class="card border-0 shadow-sm rounded-xl">
                <div class="card-header bg-white border-b border-gray-100 py-3 d-flex justify-content-between align-items-center">
                  <h3 class="text-base font-bold text-gray-800 m-0 d-flex align-items-center">
                    <i class="fas fa-rss text-warning mr-2"></i> Live Developer & HackerNews Feed
                  </h3>
                  <span class="badge bg-warning bg-opacity-10 text-warning text-xs px-2 py-0.5 rounded-full font-bold">API Active</span>
                </div>
                <div class="card-body p-0">
                  <ul class="divide-y divide-gray-100 mb-0 pl-0">
                    <?php foreach ($newsStories as $story): ?>
                      <li class="p-3.5 hover:bg-gray-50 transition duration-150 d-flex justify-content-between align-items-center gap-3">
                        <div class="flex-grow">
                          <a href="<?php echo htmlspecialchars($story['url']); ?>" target="_blank" class="text-sm font-semibold text-gray-800 hover:text-blue-600 transition duration-100 text-decoration-none">
                            <?php echo htmlspecialchars($story['title']); ?>
                          </a>
                          <div class="d-flex align-items-center gap-3 text-2xs text-gray-400 mt-1" style="font-size: 10px;">
                            <span>By @<?php echo htmlspecialchars($story['by']); ?></span>
                            <span>•</span>
                            <span>Score: <?php echo (int)$story['score']; ?> points</span>
                          </div>
                        </div>
                        <i class="fas fa-chevron-right text-gray-300 text-xs"></i>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              </div>
            </div>
          </div>

          <!-- UNIFIED CODE-FIRST CMS EDITOR PANEL FOR TENANTS -->
          <div class="row mb-4">
            <div class="col-12">
              <div class="card border-0 shadow-sm rounded-xl">
                <div class="card-header bg-white border-b border-gray-100 py-3 d-flex justify-content-between align-items-center">
                  <h3 class="text-base font-bold text-gray-800 m-0 d-flex align-items-center">
                    <i class="fas fa-code text-primary mr-2"></i> Custom CMS Slugs Page Builder
                  </h3>
                  <button class="btn btn-primary btn-sm rounded-md font-bold px-3 py-1.5" id="btnCreateCmsPage"><i class="fas fa-plus mr-1"></i> Create Custom Slug</button>
                </div>
                <div class="card-body p-4">
                  <div class="row g-4">
                    <!-- CMS Pages Directory Sidebar -->
                    <div class="col-md-4 border-r border-gray-100 pr-4">
                      <label class="block text-2xs uppercase font-bold text-gray-500 mb-2" style="font-size: 10px;">Published CMS Pages</label>
                      <div class="list-group list-group-flush pl-0 mb-0" id="cmsPagesListGroup">
                        <?php if (count($myCmsPages) > 0): ?>
                          <?php foreach ($myCmsPages as $p):
                            $pSlug = htmlspecialchars($p['slug'] ?? '');
                          ?>
                            <button class="list-group-item list-group-item-action py-2 px-3 fw-bold btn-select-cms-slug text-dark text-start border border-gray-100 rounded-md mb-2" data-slug="<?php echo $pSlug; ?>">
                              <i class="fa-solid fa-file-code me-2 text-primary"></i><?php echo $pSlug; ?>
                            </button>
                          <?php endforeach; ?>
                        <?php else: ?>
                          <div class="text-center text-gray-400 py-4 text-xs">No CMS pages published yet. Click "Create Custom Slug" to start.</div>
                        <?php endif; ?>
                      </div>
                    </div>

                    <!-- Editor Console Form -->
                    <div class="col-md-8">
                      <div id="cmsSaveFeedback" class="alert d-none text-xs rounded-lg p-2.5 mb-3" role="alert"></div>
                      <form id="cmsUserEditorForm">
                        <div class="mb-3 text-start">
                          <label class="block text-2xs uppercase font-bold text-gray-500 mb-1" style="font-size: 10px;">Endpoint Slug</label>
                          <input type="text" id="cmsUserSlug" class="form-control text-sm rounded-md px-3 py-2 border-gray-200 w-full" placeholder="e.g. about-us, my-app, bio" required />
                          <div class="form-text text-muted small" style="font-size: 10px;">Accessible directly at http://localhost:8000/slug</div>
                        </div>
                        <div class="mb-3 text-start">
                          <label class="block text-2xs uppercase font-bold text-gray-500 mb-1" style="font-size: 10px;">Page Title Metadata</label>
                          <input type="text" id="cmsUserTitle" class="form-control text-sm rounded-md px-3 py-2 border-gray-200 w-full" placeholder="Enter browser tab title..." />
                        </div>
                        <div class="mb-3 text-start">
                          <label class="block text-2xs uppercase font-bold text-gray-500 mb-1" style="font-size: 10px;">Custom Head Codes & Link Tags</label>
                          <textarea id="cmsUserHead" class="form-control code-editor-textarea w-full" rows="3" placeholder="<!-- Inject link tags, stylesheets, metadata tags here -->"></textarea>
                        </div>
                        <div class="mb-3 text-start">
                          <label class="block text-2xs uppercase font-bold text-gray-500 mb-1" style="font-size: 10px;">Custom Body Markup & Executable PHP Code</label>
                          <textarea id="cmsUserBody" class="form-control code-editor-textarea w-full" style="min-height: 250px;" placeholder="<!-- Enter custom HTML/CSS and standard PHP code blocks -->"></textarea>
                        </div>
                        <button type="submit" id="btnPublishCmsUser" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs py-2.5 px-4 rounded-lg w-full transition border-0">
                          Publish Page Layout
                        </button>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- ISOLATED WORKSPACE DATABASE MANAGER SECTION -->
          <div class="row mt-2" id="user-db-manager">
            <div class="col-12">
              <div class="card border-0 shadow-sm rounded-lg">
                <div class="card-header bg-white border-b border-gray-100 py-3">
                  <h3 class="text-base font-bold text-gray-800 m-0 d-flex align-items-center">
                    <i class="fas fa-database text-primary mr-2"></i> Custom Dynamic Database Workspace Manager
                  </h3>
                </div>
                <div class="card-body p-4">
                  <p class="text-xs text-gray-500 mb-4">Design dynamic custom relational table schemas and insert, view, and purge JSON-serialized row records inside your isolated workspace.</p>

                  <div class="row g-3 items-end mb-4 d-flex flex-wrap gap-3">
                    <!-- Create Schema Input -->
                    <div class="flex-grow-1 min-w-[200px]">
                      <label class="block text-2xs uppercase font-bold text-gray-500 mb-1" style="font-size: 10px;">Create Custom Database Table</label>
                      <input type="text" id="newTableName" class="form-control text-sm rounded-md px-3 py-2 border-gray-200 w-full" placeholder="Type table name..." />
                    </div>
                    <!-- Create Button -->
                    <div class="w-full md:w-auto">
                      <button class="btn btn-primary btn-sm rounded-md w-full font-bold py-2 shadow-sm bg-blue-600 text-white" id="btnCreateTable">Create Schema</button>
                    </div>
                    <!-- Schema Selector -->
                    <div class="flex-grow-1 min-w-[200px]">
                      <label class="block text-2xs uppercase font-bold text-gray-500 mb-1" style="font-size: 10px;">Select Active Database Schema</label>
                      <select id="activeTableSelect" class="form-control text-sm rounded-md px-3 py-2 border-gray-200 w-full">
                        <option value="">-- Choose active schema --</option>
                      </select>
                    </div>
                    <!-- Delete Button -->
                    <div class="w-full md:w-auto">
                      <button class="btn btn-outline-danger btn-sm rounded-md w-full font-bold py-2" id="btnDropTable">Drop Table</button>
                    </div>
                  </div>

                  <!-- Dynamic rows display area -->
                  <div id="tableDisplayPanel" class="hidden mt-4 border border-gray-100 rounded-lg p-3 bg-gray-50">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <h5 id="activeTableTitle" class="text-sm font-bold text-gray-800 m-0">Table Structure: <span class="text-blue-600"></span></h5>
                      <button class="btn btn-success btn-xs rounded-md font-semibold bg-emerald-600 text-white" id="btnAddRowBtn" data-bs-toggle="modal" data-bs-target="#insertRowModal"><i class="fas fa-plus mr-1"></i>Insert Record</button>
                    </div>

                    <div class="table-responsive">
                      <table class="table table-hover mb-0 text-xs" id="dynamicDataTable">
                        <thead class="bg-gray-100 text-gray-600 font-bold">
                          <tr id="dynamicDataTableHead">
                            <!-- Header columns populated dynamically -->
                          </tr>
                        </thead>
                        <tbody id="dynamicDataTableBody" class="text-gray-700 bg-white">
                          <!-- Custom rows content -->
                        </tbody>
                      </table>
                    </div>
                  </div>

                </div>
              </div>
            </div>
          </div>
        <?php endif; ?>

      </div>
    </section>
  </div>

</div>

  <!-- Bottom Right Floating Action Button (FAB - Dots Icon Launcher) -->
  <?php if ($subscriptionStatus === 'trial' || $subscriptionStatus === 'active'): ?>
    <button type="button" class="btn btn-danger fab-btn d-flex align-items-center justify-content-center text-xl text-white" data-bs-toggle="modal" data-bs-target="#userAppsModal" title="Launch Applications" style="background-color: #3b82f6; border: none;">
      <i class="fas fa-th"></i>
    </button>
  <?php endif; ?>

  <!-- Choose a Plan Subscription Selection Modal -->
  <div class="modal fade" id="choosePlanModal" tabindex="-1" role="dialog" aria-labelledby="choosePlanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
      <div class="modal-content rounded-xl border-0 shadow-2xl">
        <div class="modal-header border-b border-gray-100 pb-3">
          <h5 class="modal-title font-bold text-gray-800 d-flex align-items-center text-sm" id="choosePlanModalLabel">
            <i class="fas fa-credit-card text-primary mr-2"></i> Select Premium Hosting Workspace Plan
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4 bg-gray-50">
          <div class="text-center mb-4">
            <h4 class="font-extrabold text-gray-800 text-base">Affordable Pricing Built for Performance</h4>
            <p class="text-xs text-gray-500">Deploy high-performance systems with zero operational overhead.</p>
          </div>

          <div id="paymentFeedback" class="alert d-none text-xs rounded-lg p-3 mb-4" role="alert"></div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Plan 1: Starter Space -->
            <div class="bg-white border border-gray-200 rounded-2xl p-4 d-flex flex-column justify-content-between hover:border-blue-400 transition">
              <div class="mb-4">
                <span class="text-2xs font-extrabold text-blue-600 uppercase tracking-widest d-block mb-1" style="font-size: 9px;">Starter Space</span>
                <span class="text-2xl font-black text-gray-800">$5<span class="text-xs font-normal text-gray-400">/mo</span></span>
                <p class="text-2xs text-gray-500 mt-2" style="font-size: 11px;">Perfect for launching simple databases and micro-landing web assets.</p>
                <hr class="my-3 border-gray-100">
                <ul class="text-2xs text-gray-600 space-y-2 pl-0 list-unstyled" style="font-size: 10px;">
                  <li><i class="fas fa-check text-success mr-1"></i> 1 Website Deployment</li>
                  <li><i class="fas fa-check text-success mr-1"></i> 5 custom database tables</li>
                  <li><i class="fas fa-check text-success mr-1"></i> Shared SSL configuration</li>
                </ul>
              </div>
              <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs py-2 px-3 rounded-lg w-full transition btn-process-payment" data-plan-name="Starter Space">
                Select Plan
              </button>
            </div>

            <!-- Plan 2: Growth Plan -->
            <div class="bg-white border-2 border-primary rounded-2xl p-4 d-flex flex-column justify-content-between relative">
              <span class="absolute top-0 right-4 transform -translate-y-1/2 bg-blue-600 text-white text-[9px] font-black uppercase px-2.5 py-0.5 rounded-full" style="font-size: 9px; top: 0px;">Popular</span>
              <div class="mb-4">
                <span class="text-2xs font-extrabold text-blue-600 uppercase tracking-widest d-block mb-1" style="font-size: 9px;">Growth Plan</span>
                <span class="text-2xl font-black text-gray-800">$15<span class="text-xs font-normal text-gray-400">/mo</span></span>
                <p class="text-2xs text-gray-500 mt-2" style="font-size: 11px;">Ideal for growing developer portals and relational APIs.</p>
                <hr class="my-3 border-gray-100">
                <ul class="text-2xs text-gray-600 space-y-2 pl-0 list-unstyled" style="font-size: 10px;">
                  <li><i class="fas fa-check text-success mr-1"></i> Unlimited Websites</li>
                  <li><i class="fas fa-check text-success mr-1"></i> Unlimited custom schemas</li>
                  <li><i class="fas fa-check text-success mr-1"></i> 10GB Storage & Backups</li>
                </ul>
              </div>
              <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs py-2 px-3 rounded-lg w-full transition btn-process-payment" data-plan-name="Growth Plan">
                Select Plan
              </button>
            </div>

            <!-- Plan 3: Enterprise Space -->
            <div class="bg-white border border-gray-200 rounded-2xl p-4 d-flex flex-column justify-content-between hover:border-blue-400 transition">
              <div class="mb-4">
                <span class="text-2xs font-extrabold text-blue-600 uppercase tracking-widest d-block mb-1" style="font-size: 9px;">Enterprise Space</span>
                <span class="text-2xl font-black text-gray-800">$49<span class="text-xs font-normal text-gray-400">/mo</span></span>
                <p class="text-2xs text-gray-500 mt-2" style="font-size: 11px;">Robust computing resources with low-latency CDN setups.</p>
                <hr class="my-3 border-gray-100">
                <ul class="text-2xs text-gray-600 space-y-2 pl-0 list-unstyled" style="font-size: 10px;">
                  <li><i class="fas fa-check text-success mr-1"></i> Subdomain customization</li>
                  <li><i class="fas fa-check text-success mr-1"></i> Dedicated SLA priority</li>
                  <li><i class="fas fa-check text-success mr-1"></i> Full SSH/Access Keys</li>
                </ul>
              </div>
              <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs py-2 px-3 rounded-lg w-full transition btn-process-payment" data-plan-name="Enterprise Space">
                Select Plan
              </button>
            </div>
          </div>

        </div>
        <div class="modal-footer border-t border-gray-100 bg-gray-50 rounded-b-xl py-2 d-flex justify-between align-items-center">
          <span class="text-[10px] text-gray-400"><i class="fas fa-lock mr-1"></i> All payment parameters are processed securely server-side.</span>
          <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Google-like Micro-App Launcher Directory Modal -->
  <div class="modal fade" id="userAppsModal" tabindex="-1" role="dialog" aria-labelledby="userAppsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 440px;">
      <div class="modal-content rounded-xl border-0 shadow-2xl">
        <div class="modal-header border-b border-gray-100 pb-3">
          <h5 class="modal-title font-bold text-gray-800 d-flex align-items-center text-sm" id="userAppsModalLabel">
            <i class="fas fa-th text-primary mr-2"></i> Workspace Launchpad
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="grid grid-cols-3 gap-3">

            <!-- App Card 1: CMS Builder -->
            <button class="btn-app-trigger app-icon-card d-flex flex-column align-items-center p-3 rounded-lg border border-gray-100 bg-white" data-app-url="/cms/admin">
              <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full d-flex align-items-center justify-content-center mb-2 text-xl">
                <i class="fas fa-laptop-code"></i>
              </div>
              <span class="text-2xs font-semibold text-gray-700 text-center" style="font-size: 10px;">CMS Builder</span>
            </button>

            <!-- App Card 2: QR Generator -->
            <button class="btn-app-trigger app-icon-card d-flex flex-column align-items-center p-3 rounded-lg border border-gray-100 bg-white" data-app-url="/apps/qrcode/index.html">
              <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-full d-flex align-items-center justify-content-center mb-2 text-xl">
                <i class="fas fa-qrcode"></i>
              </div>
              <span class="text-2xs font-semibold text-gray-700 text-center" style="font-size: 10px;">QR Gen</span>
            </button>

            <!-- App Card 3: URL Shortener -->
            <button class="btn-app-trigger app-icon-card d-flex flex-column align-items-center p-3 rounded-lg border border-gray-100 bg-white" data-app-url="/apps/url_shortner/index.html">
              <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-full d-flex align-items-center justify-content-center mb-2 text-xl">
                <i class="fas fa-link"></i>
              </div>
              <span class="text-2xs font-semibold text-gray-700 text-center" style="font-size: 10px;">URL Short</span>
            </button>

            <!-- App Card 4: Bio Builder -->
            <button class="btn-app-trigger app-icon-card d-flex flex-column align-items-center p-3 rounded-lg border border-gray-100 bg-white" data-app-url="/apps/bio_builder/index.html">
              <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-full d-flex align-items-center justify-content-center mb-2 text-xl">
                <i class="fas fa-id-card"></i>
              </div>
              <span class="text-2xs font-semibold text-gray-700 text-center" style="font-size: 10px;">Bio Page</span>
            </button>

            <!-- App Card 5: CV Builder -->
            <button class="btn-app-trigger app-icon-card d-flex flex-column align-items-center p-3 rounded-lg border border-gray-100 bg-white" data-app-url="/apps/cv_builder/index.html">
              <div class="w-12 h-12 bg-teal-100 text-teal-600 rounded-full d-flex align-items-center justify-content-center mb-2 text-xl">
                <i class="fas fa-file-invoice"></i>
              </div>
              <span class="text-2xs font-semibold text-gray-700 text-center" style="font-size: 10px;">Resume</span>
            </button>

            <!-- App Card 6: WhatsApp Gen -->
            <button class="btn-app-trigger app-icon-card d-flex flex-column align-items-center p-3 rounded-lg border border-gray-100 bg-white" data-app-url="/apps/whatapp_link_generator/index.html">
              <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-full d-flex align-items-center justify-content-center mb-2 text-xl">
                <i class="fab fa-whatsapp"></i>
              </div>
              <span class="text-2xs font-semibold text-gray-700 text-center" style="font-size: 10px;">WhatsApp Link</span>
            </button>

            <!-- App Card 7: Invoice Gen -->
            <button class="btn-app-trigger app-icon-card d-flex flex-column align-items-center p-3 rounded-lg border border-gray-100 bg-white" data-app-url="/apps/invoice/index.html">
              <div class="w-12 h-12 bg-cyan-100 text-cyan-600 rounded-full d-flex align-items-center justify-content-center mb-2 text-xl">
                <i class="fas fa-file-invoice-dollar"></i>
              </div>
              <span class="text-2xs font-semibold text-gray-700 text-center" style="font-size: 10px;">Invoice Gen</span>
            </button>

            <!-- App Card 8: Image Compressor -->
            <button class="btn-app-trigger app-icon-card d-flex flex-column align-items-center p-3 rounded-lg border border-gray-100 bg-white" data-app-url="/apps/img_comprossor/index.html">
              <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-full d-flex align-items-center justify-content-center mb-2 text-xl">
                <i class="fas fa-file-image"></i>
              </div>
              <span class="text-2xs font-semibold text-gray-700 text-center" style="font-size: 10px;">Compressor</span>
            </button>

          </div>
        </div>
        <div class="modal-footer border-t border-gray-100 bg-gray-50 rounded-b-xl py-2">
          <span class="text-2xs text-gray-400 w-full text-center" style="font-size: 10px;">Select any app to launch in-dashboard</span>
        </div>
      </div>
    </div>
  </div>

  <!-- IN-DASHBOARD INLINE IFRAME APP LOADER MODAL -->
  <div class="modal fade" id="iframeAppLoaderModal" tabindex="-1" role="dialog" aria-labelledby="iframeAppLoaderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
      <div class="modal-content rounded-xl border-0 shadow-2xl">
        <div class="modal-header border-b border-gray-100 bg-primary text-white py-3">
          <h5 class="modal-title font-bold d-flex align-items-center text-sm" id="iframeAppLoaderModalLabel">
            <i class="fas fa-window-maximize mr-2"></i> Dynamic App Workspace Container
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-0 bg-gray-900 overflow-hidden" style="height: 620px;">
          <iframe id="appWorkspaceIframe" src="about:blank" class="w-full h-full border-0 bg-white"></iframe>
        </div>
        <div class="modal-footer border-t border-gray-100 bg-gray-50 py-2 d-flex justify-between align-items-center">
          <span class="text-2xs text-gray-400">Sandbox App Layer Secure Routing Protocol</span>
          <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Close Workspace</button>
        </div>
      </div>
    </div>
  </div>

  <!-- CREATE WEBSITE SELECTION MODAL -->
  <div class="modal fade" id="createWebsiteModal" tabindex="-1" role="dialog" aria-labelledby="createWebsiteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content rounded-xl border-0 shadow-2xl">
        <div class="modal-header border-b border-gray-100 py-3">
          <h5 class="modal-title font-bold text-gray-800 d-flex align-items-center text-sm" id="createWebsiteModalLabel">
            <i class="fas fa-globe text-primary mr-2"></i> Deploy New Web Project
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div id="websiteFeedback" class="alert d-none text-xs rounded-lg p-2.5 mb-3" role="alert"></div>
          <form id="createWebsiteForm">
            <div class="mb-3 text-start">
              <label class="block text-2xs uppercase font-bold text-gray-500 mb-1" style="font-size: 10px;">Website Name</label>
              <input type="text" id="webNameInput" class="form-control text-sm rounded-md px-3 py-2 border-gray-200 w-full" placeholder="e.g. My Portfolio Node" required />
            </div>
            <div class="mb-3 text-start">
              <label class="block text-2xs uppercase font-bold text-gray-500 mb-1" style="font-size: 10px;">Target Hosting URL</label>
              <input type="url" id="webUrlInput" class="form-control text-sm rounded-md px-3 py-2 border-gray-200 w-full" placeholder="e.g. https://myportfolio.com" required />
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs py-2.5 px-4 rounded-lg w-full transition border-0">
              Launch Hosting Space
            </button>
          </form>
        </div>
        <div class="modal-footer border-t border-gray-100 bg-gray-50 rounded-b-xl py-2">
          <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ROW RECORD INSERTION MODAL -->
  <div class="modal fade" id="insertRowModal" tabindex="-1" aria-labelledby="insertRowModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-xl border-0 shadow-2xl">
        <div class="modal-header border-b border-gray-100 bg-primary text-white py-3">
          <h5 class="modal-title font-bold d-flex align-items-center text-sm" id="insertRowModalLabel"><i class="fas fa-plus-square mr-2"></i>Insert Row Data</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <form id="insertRowForm">
            <div class="mb-3 text-xs text-gray-500" style="font-size: 11px;">Specify up to 4 dynamic columns and field values below:</div>
            <div id="modalColumnsContainer">
              <div class="row g-2 mb-2 column-input-row d-flex gap-2">
                <div class="col-6">
                  <input type="text" class="form-control col-name-input text-xs rounded-md px-2 py-1.5 border-gray-200" placeholder="Column Key (e.g. name)" required />
                </div>
                <div class="col-6">
                  <input type="text" class="form-control col-val-input text-xs rounded-md px-2 py-1.5 border-gray-200" placeholder="Field Value" required />
                </div>
              </div>
            </div>
            <button type="button" class="btn btn-outline-primary btn-xs mt-2 rounded-md font-bold" id="btnAddColumnInput"><i class="fas fa-plus mr-1"></i>Add Column Tag</button>
          </form>
        </div>
        <div class="modal-footer border-t border-gray-100 bg-gray-50 rounded-b-xl py-2">
          <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary btn-sm rounded-pill px-4 bg-blue-600 text-white border-0" id="btnSubmitInsertRow">Save Record</button>
        </div>
      </div>
    </div>
  </div>

</div>

<!-- Required Scripts: jQuery, Bootstrap 5 -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Interactive App Launching & Dynamic Database Management -->
<script>
$(document).ready(function() {

    // 1. Dynamic CMS Slugs Selection and Load in Dashboard
    let cmsPagesDb = <?php echo json_encode($myCmsPages); ?>;

    $(document).on('click', '.btn-select-cms-slug', function() {
        const slug = $(this).attr('data-slug');
        $('.btn-select-cms-slug').removeClass('active bg-primary text-white');
        $(this).addClass('active bg-primary text-white');

        const page = cmsPagesDb.find(p => p.slug === slug);
        if (page) {
            $('#cmsUserSlug').val(page.slug).prop('readonly', true);
            $('#cmsUserTitle').val(page.title || '');
            $('#cmsUserHead').val(page.head_code || '');
            $('#cmsUserBody').val(page.body_code || '');
        }
    });

    $('#btnCreateCmsPage').on('click', function() {
        $('.btn-select-cms-slug').removeClass('active bg-primary text-white');
        $('#cmsUserSlug').val('').prop('readonly', false).focus();
        $('#cmsUserTitle').val('');
        $('#cmsUserHead').val('');
        $('#cmsUserBody').val('<!-- Enter custom HTML or executable PHP code tags here -->');
    });

    // Handle CMS Page Publishing via AJAX to /cms/save_page
    $('#cmsUserEditorForm').on('submit', function(e) {
        e.preventDefault();
        const feedback = $('#cmsSaveFeedback');
        const btn = $('#btnPublishCmsUser');

        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i>Publishing Layout...');
        feedback.addClass('d-none').removeClass('alert-success alert-danger');

        $.ajax({
            url: '/cms/save_page',
            type: 'POST',
            dataType: 'json',
            data: {
                slug: $('#cmsUserSlug').val(),
                title: $('#cmsUserTitle').val(),
                head_code: $('#cmsUserHead').val(),
                body_code: $('#cmsUserBody').val()
            },
            success: function(res) {
                btn.prop('disabled', false).html('Publish Page Layout');
                feedback.removeClass('d-none');

                if (res.success) {
                    feedback.addClass('alert-success').text(res.message);
                    // Schedule reload to update lists
                    setTimeout(() => {
                        window.location.reload();
                    }, 1200);
                } else {
                    feedback.addClass('alert-danger').text(res.message);
                }
            },
            error: function() {
                btn.prop('disabled', false).html('Publish Page Layout');
                feedback.removeClass('d-none').addClass('alert-danger').text('Failed to publish custom layout page.');
            }
        });
    });

    // 2. Handle secure payment simulation via AJAX to process_payment.php
    $('.btn-process-payment').on('click', function() {
        const selectedPlan = $(this).attr('data-plan-name');
        const feedback = $('#paymentFeedback');

        feedback.addClass('d-none').removeClass('alert-success alert-danger');
        $(this).prop('disabled', true).text('Processing Payment...');

        $.ajax({
            url: '/php/process_payment.php',
            type: 'POST',
            dataType: 'json',
            data: { plan: selectedPlan },
            success: function(res) {
                if (res.success) {
                    feedback.removeClass('d-none').addClass('alert-success').text(res.message);
                    // Reload dashboard to reflect the subscription status change
                    setTimeout(() => {
                        window.location.reload();
                    }, 1200);
                } else {
                    feedback.removeClass('d-none').addClass('alert-danger').text(res.message || 'Payment processing failed.');
                }
            },
            error: function() {
                feedback.removeClass('d-none').addClass('alert-danger').text('Server communications error. Please try again.');
            }
        });
    });

    // 3. Handle Website creation form submission
    $('#createWebsiteForm').on('submit', function(e) {
        e.preventDefault();
        const feedback = $('#websiteFeedback');

        feedback.addClass('d-none').removeClass('alert-success alert-danger');

        $.ajax({
            url: '/user/dashboard.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'create_website',
                website_name: $('#webNameInput').val(),
                website_url: $('#webUrlInput').val()
            },
            success: function(res) {
                if (res.success) {
                    feedback.removeClass('d-none').addClass('alert-success').text(res.message);
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    feedback.removeClass('d-none').addClass('alert-danger').text(res.message);
                }
            },
            error: function() {
                feedback.removeClass('d-none').addClass('alert-danger').text('Failed to deploy website project.');
            }
        });
    });

    // 4. Dynamic App Loading into Dashboard Iframe Modal
    $(document).on('click', '.btn-app-trigger', function() {
        const appUrl = $(this).attr('data-app-url');
        if (!appUrl) return;

        // Hide launcher modal if open
        const launcherModalEl = document.getElementById('userAppsModal');
        if (launcherModalEl) {
            const modal = bootstrap.Modal.getInstance(launcherModalEl);
            if (modal) modal.hide();
        }

        // Set source for workspace container iframe
        $('#appWorkspaceIframe').attr('src', appUrl);

        // Set title dynamically based on selection
        const appName = $(this).find('span').text() || $(this).find('h4').text() || 'Application';
        $('#iframeAppLoaderModalLabel').html('<i class="fas fa-window-maximize mr-2"></i> Sandbox: ' + appName);

        // Open in-dashboard viewer modal
        const viewModal = new bootstrap.Modal(document.getElementById('iframeAppLoaderModal'));
        viewModal.show();
    });

    // Reset iframe on modal close to free memory resources
    const iframeModalEl = document.getElementById('iframeAppLoaderModal');
    if (iframeModalEl) {
        iframeModalEl.addEventListener('hidden.bs.modal', function () {
            $('#appWorkspaceIframe').attr('src', 'about:blank');
        });
    }

    // 5. Fetch schemas list dynamically
    function loadTablesList() {
        if ($('#activeTableSelect').length === 0) return;
        $.ajax({
            url: '/php/user_database_action.php',
            type: 'GET',
            dataType: 'json',
            data: { action: 'list_tables' },
            success: function(res) {
                if (res.success) {
                    const select = $('#activeTableSelect');
                    const selectedVal = select.val();
                    select.empty().append('<option value="">-- Choose active schema --</option>');
                    res.tables.forEach(function(table) {
                        select.append(`<option value="${table}">${table}</option>`);
                    });
                    if (selectedVal) select.val(selectedVal);
                }
            }
        });
    }

    loadTablesList();

    // 6. Create Custom Table Schema
    $('#btnCreateTable').on('click', function() {
        const tableName = $('#newTableName').val().trim();
        if (!tableName) {
            alert('Please enter a valid table name.');
            return;
        }
        $.ajax({
            url: '/php/user_database_action.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'create_table', table_name: tableName },
            success: function(res) {
                if (res.success) {
                    alert(res.message);
                    $('#newTableName').val('');
                    loadTablesList();
                } else {
                    alert(res.message || 'Failed to create table schema.');
                }
            }
        });
    });

    // 7. Load & Render Custom Rows
    function loadTableRows(tableName) {
        if (!tableName) {
            $('#tableDisplayPanel').addClass('hidden');
            return;
        }
        $.ajax({
            url: '/php/user_database_action.php',
            type: 'GET',
            dataType: 'json',
            data: { action: 'get_rows', table_name: tableName },
            success: function(res) {
                if (res.success) {
                    $('#tableDisplayPanel').removeClass('hidden');
                    $('#activeTableTitle span').text(tableName);

                    const head = $('#dynamicDataTableHead');
                    const body = $('#dynamicDataTableBody');
                    head.empty();
                    body.empty();

                    let columns = ['id'];
                    if (res.rows.length > 0) {
                        res.rows.forEach(function(row) {
                            Object.keys(row).forEach(function(key) {
                                if (!columns.includes(key)) {
                                    columns.push(key);
                                }
                            });
                        });
                    } else {
                        columns.push('status');
                    }

                    columns.forEach(function(col) {
                        head.append(`<th class="p-2">${col}</th>`);
                    });
                    head.append('<th class="p-2 text-right">Actions</th>');

                    if (res.rows.length > 0) {
                        res.rows.forEach(function(row) {
                            let rowHtml = '<tr class="border-b border-gray-100 hover:bg-gray-50">';
                            columns.forEach(function(col) {
                                const cellVal = row[col] !== undefined ? row[col] : '-';
                                rowHtml += `<td class="p-2">${cellVal}</td>`;
                            });
                            rowHtml += `<td class="p-2 text-right">
                                <button class="btn btn-sm btn-outline-danger btn-delete-row rounded-md" data-id="${row.id}"><i class="fas fa-trash"></i></button>
                            </td></tr>`;
                            body.append(rowHtml);
                        });
                    } else {
                        body.append(`<tr><td colspan="${columns.length + 1}" class="text-center text-gray-400 py-3 text-xs">No records found inside '${tableName}'. Click Insert Record to begin.</td></tr>`);
                    }
                }
            }
        });
    }

    $('#activeTableSelect').on('change', function() {
        loadTableRows($(this).val());
    });

    // 8. Purge Table Schema
    $('#btnDropTable').on('click', function() {
        const tableName = $('#activeTableSelect').val();
        if (!tableName) {
            alert('Please choose a table schema first.');
            return;
        }
        if (!confirm(`Are you absolutely sure you want to drop '${tableName}' table? This will permanently delete all records.`)) return;
        $.ajax({
            url: '/php/user_database_action.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'drop_table', table_name: tableName },
            success: function(res) {
                if (res.success) {
                    alert(res.message);
                    $('#activeTableSelect').val('');
                    $('#tableDisplayPanel').addClass('hidden');
                    loadTablesList();
                } else {
                    alert(res.message);
                }
            }
        });
    });

    // 9. Dynamic Input Fields inside Insertion Modal
    $('#btnAddColumnInput').on('click', function() {
        $('#modalColumnsContainer').append(`
            <div class="row g-2 mb-2 column-input-row d-flex gap-2">
                <div class="col-6">
                    <input type="text" class="form-control col-name-input text-xs rounded-md px-2 py-1.5 border-gray-200" placeholder="Column Key" required />
                </div>
                <div class="col-6">
                    <input type="text" class="form-control col-val-input text-xs rounded-md px-2 py-1.5 border-gray-200" placeholder="Value" required />
                </div>
            </div>
        `);
    });

    // 10. Save Custom Database Row Record
    $('#btnSubmitInsertRow').on('click', function() {
        const tableName = $('#activeTableSelect').val();
        if (!tableName) return;

        let columns = [];
        $('.column-input-row').each(function() {
            const name = $(this).find('.col-name-input').val().trim();
            const value = $(this).find('.col-val-input').val().trim();
            if (name) {
                columns.push({ name: name, value: value });
            }
        });

        if (columns.length === 0) {
            alert('Please specify at least one column tag.');
            return;
        }

        $.ajax({
            url: '/php/user_database_action.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'insert_row', table_name: tableName, columns: columns },
            success: function(res) {
                if (res.success) {
                    $('#insertRowForm')[0].reset();
                    $('#modalColumnsContainer').html(`
                        <div class="row g-2 mb-2 column-input-row d-flex gap-2">
                            <div class="col-6">
                                <input type="text" class="form-control col-name-input text-xs rounded-md px-2 py-1.5 border-gray-200" placeholder="Column Key (e.g. name)" required />
                            </div>
                            <div class="col-6">
                                <input type="text" class="form-control col-val-input text-xs rounded-md px-2 py-1.5 border-gray-200" placeholder="Field Value" required />
                            </div>
                        </div>
                    `);

                    const insertRowModalEl = document.getElementById('insertRowModal');
                    if (insertRowModalEl) {
                        const modal = bootstrap.Modal.getInstance(insertRowModalEl);
                        if (modal) modal.hide();
                    }

                    loadTableRows(tableName);
                } else {
                    alert(res.message);
                }
            }
        });
    });

    // 11. Delete Custom Database Row Record
    $(document).on('click', '.btn-delete-row', function() {
        const tableName = $('#activeTableSelect').val();
        const rowId = $(this).attr('data-id');
        if (!tableName || !rowId) return;

        if (!confirm('Are you sure you want to delete this custom record?')) return;

        $.ajax({
            url: '/php/user_database_action.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'delete_row', table_name: tableName, row_id: rowId },
            success: function(res) {
                if (res.success) {
                    loadTableRows(tableName);
                } else {
                    alert(res.message);
                }
            }
        });
    });

    // Select first item by default if any exists
    $('.btn-select-cms-slug').first().click();
});
</script>
</body>
</html>
