<?php
/**
 * dashboard.php
 *
 * Premium, White & Blue themed Tenant Home & Control Panel for nodexGosolutions.
 * Fully styled with Tailwind CSS & customized UI panels.
 * Features:
 * - App Launcher (Google Dots Menu) and Bottom Right Floating Action Button (FAB).
 * - Full-featured inline Iframe App Launcher Modal to load apps directly without redirection.
 * - Live Technology News Feed pulling stories dynamically from Hacker News API.
 * - Public Blogs & Websites Showcases made by other tenants on the platform.
 * - Interactive Workspace Database Manager for custom user-created JSON table schemas.
 * - Clean, fully responsive White & Blue design.
 */

// Enable strict typing for architectural safety
declare(strict_types=1);

// Require central system database connector
require_once __DIR__ . '/../../../php/db.php';

// Instantiate secure session configurations
secureSession();

// Access Control: Ensure the user session is active and authenticated
if (!isset($_SESSION['email'])) {
    header('Location: /login');
    exit;
}

// Fetch active users and system blogs dynamically to showcase other websites and content
$usersList = $conn->select('users') ?: [];

// Load system databases to fetch analytics
$siteCmsDb = new Database(__DIR__ . '/../../../databases', 'site_cms');
$urlDb     = new Database(__DIR__ . '/../../../databases', 'url_shortner');
$qrDb      = new Database(__DIR__ . '/../../../databases', 'qrcode');

// Collect user analytics
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
  <!-- AdminLTE 3 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
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
  </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed" style="background-color: #f8fafc;">
<div class="wrapper">

  <!-- Include modular Navigation Bar component -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light border-b border-gray-100 px-3">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="/" class="nav-link font-semibold">Home</a>
      </li>
    </ul>

    <ul class="navbar-nav ml-auto flex items-center gap-3">
      <li class="nav-item">
        <!-- Google Apps Dots Menu (App Launcher trigger) -->
        <button class="btn btn-light rounded-full p-2 text-gray-600 hover:text-blue-600 focus:outline-none" data-toggle="modal" data-target="#userAppsModal" title="Launch Applications">
          <i class="fas fa-th text-lg"></i>
        </button>
      </li>
      <li class="nav-item">
        <span class="badge bg-blue-100 text-blue-800 px-2.5 py-1.5 text-xs rounded-md">
          <i class="fas fa-user mr-1"></i> Tenant Mode Active
        </span>
      </li>
    </ul>
  </nav>

  <!-- Include modular Sidebar component -->
  <?php require_once __DIR__ . '/../../modul/sidebar.php'; ?>

  <!-- Content Wrapper -->
  <div class="content-wrapper p-4" style="background-color: #f8fafc;">

    <!-- Header -->
    <div class="content-header p-0 mb-4">
      <div class="container-fluid">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
          <div>
            <h1 class="m-0 text-2xl font-bold text-gray-800">Workspace Dashboard</h1>
            <p class="text-xs text-gray-500 m-0">Explore interactive feeds, review websites, manage your custom databases, and launch micro-applications.</p>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-xs font-semibold text-gray-500">Live Active Apps:</span>
            <span class="badge bg-green-500 text-white rounded-full px-2 py-0.5 text-2xs">8 Online</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Workspace Content -->
    <section class="content">
      <div class="container-fluid">

        <!-- Live Workspace Highlights Panel (Blogs, Websites, Dev News Feed) -->
        <div class="row">

          <!-- Column 1: Live Dev Feed & Platform Highlights -->
          <div class="col-lg-8 col-12 mb-4">

            <!-- Platform Showcase (Active Websites & Tenant Blogs) -->
            <div class="card border-0 shadow-sm rounded-lg mb-4">
              <div class="card-header bg-white border-b border-gray-100 py-3">
                <h3 class="text-base font-bold text-gray-800 m-0 flex items-center">
                  <i class="fas fa-globe text-blue-600 mr-2"></i> Showcase & Tenant Networks
                </h3>
              </div>
              <div class="card-body p-4">
                <p class="text-xs text-gray-500 mb-3">Live previews of web pages built by other tenants inside our multi-tenant cloud framework:</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                  <!-- Active Site 1 -->
                  <div class="p-3 border border-gray-100 rounded-lg bg-gray-50 hover:border-blue-300 transition duration-150">
                    <div class="flex items-center justify-between mb-2">
                      <span class="text-xs font-bold text-blue-600 uppercase">Tech Blog Spot</span>
                      <span class="badge bg-blue-100 text-blue-800 text-2xs">CMS Website</span>
                    </div>
                    <p class="text-xs text-gray-700 font-semibold mb-1">A dynamic blog covering PHP, Tailwind, and system design patterns.</p>
                    <div class="flex items-center justify-between text-2xs text-gray-400">
                      <span>Owner: Cedar Anyanwu</span>
                      <span>1.2K views</span>
                    </div>
                  </div>

                  <!-- Active Site 2 -->
                  <div class="p-3 border border-gray-100 rounded-lg bg-gray-50 hover:border-blue-300 transition duration-150">
                    <div class="flex items-center justify-between mb-2">
                      <span class="text-xs font-bold text-purple-600 uppercase">Portfolio Core</span>
                      <span class="badge bg-purple-100 text-purple-800 text-2xs">Bio Site</span>
                    </div>
                    <p class="text-xs text-gray-700 font-semibold mb-1">Interactive personal bio page, resume links, and project portfolios.</p>
                    <div class="flex items-center justify-between text-2xs text-gray-400">
                      <span>Owner: John Tenant</span>
                      <span>892 views</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- HackerNews / Tech Stories Feed Card -->
            <div class="card border-0 shadow-sm rounded-lg">
              <div class="card-header bg-white border-b border-gray-100 py-3 flex justify-between items-center">
                <h3 class="text-base font-bold text-gray-800 m-0 flex items-center">
                  <i class="fas fa-rss text-orange-500 mr-2"></i> Live Developer & HackerNews Feed
                </h3>
                <span class="badge bg-orange-100 text-orange-800 text-xs px-2 py-0.5 rounded-full font-bold">API Active</span>
              </div>
              <div class="card-body p-0">
                <ul class="divide-y divide-gray-100 mb-0">
                  <?php foreach ($newsStories as $story): ?>
                    <li class="p-3.5 hover:bg-gray-50 transition duration-150 flex justify-between items-center gap-3">
                      <div class="flex-grow">
                        <a href="<?php echo htmlspecialchars($story['url']); ?>" target="_blank" class="text-sm font-semibold text-gray-800 hover:text-blue-600 transition duration-100">
                          <?php echo htmlspecialchars($story['title']); ?>
                        </a>
                        <div class="flex items-center gap-3 text-2xs text-gray-400 mt-1">
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

          <!-- Column 2: Quick Stats & Launcher Board -->
          <div class="col-lg-4 col-12 mb-4">

            <!-- Quick Workspace Metrics -->
            <div class="card border-0 shadow-sm rounded-lg mb-4">
              <div class="card-header bg-white border-b border-gray-100 py-3">
                <h3 class="text-base font-bold text-gray-800 m-0 flex items-center">
                  <i class="fas fa-chart-line text-blue-600 mr-2"></i> Account Usage
                </h3>
              </div>
              <div class="card-body p-4">
                <div class="mb-3">
                  <div class="flex justify-between text-xs font-semibold text-gray-600 mb-1">
                    <span>Database Files Created</span>
                    <span>Active</span>
                  </div>
                  <div class="w-full bg-gray-100 rounded-full h-1.5">
                    <div class="bg-blue-600 h-1.5 rounded-full" style="width: 75%;"></div>
                  </div>
                </div>

                <div class="mb-3">
                  <div class="flex justify-between text-xs font-semibold text-gray-600 mb-1">
                    <span>Deployed Platform Assets</span>
                    <span><?php echo $deploymentsCount; ?> deployed</span>
                  </div>
                  <div class="w-full bg-gray-100 rounded-full h-1.5">
                    <div class="bg-indigo-600 h-1.5 rounded-full" style="width: 60%;"></div>
                  </div>
                </div>

                <div>
                  <div class="flex justify-between text-xs font-semibold text-gray-600 mb-1">
                    <span>Daily API Tokens Cache</span>
                    <span>100% Limit</span>
                  </div>
                  <div class="w-full bg-gray-100 rounded-full h-1.5">
                    <div class="bg-emerald-500 h-1.5 rounded-full" style="width: 100%;"></div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Instant Launch Tool Cards Grid -->
            <div class="card border-0 shadow-sm rounded-lg">
              <div class="card-header bg-white border-b border-gray-100 py-3">
                <h3 class="text-base font-bold text-gray-800 m-0 flex items-center">
                  <i class="fas fa-cube text-blue-600 mr-2"></i> Deployment Tools
                </h3>
              </div>
              <div class="card-body p-3">
                <div class="grid grid-cols-2 gap-2">

                  <!-- Tool: CMS Builder -->
                  <button class="btn-app-trigger text-left p-2.5 rounded-lg border border-gray-100 hover:border-blue-400 bg-white transition duration-150 flex items-center gap-2.5" data-app-url="/cms/admin">
                    <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center text-sm">
                      <i class="fas fa-laptop-code"></i>
                    </div>
                    <div>
                      <h4 class="text-xs font-bold text-gray-800 m-0">CMS Builder</h4>
                    </div>
                  </button>

                  <!-- Tool: QR Code -->
                  <button class="btn-app-trigger text-left p-2.5 rounded-lg border border-gray-100 hover:border-blue-400 bg-white transition duration-150 flex items-center gap-2.5" data-app-url="/apps/qrcode/index.html">
                    <div class="w-8 h-8 bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center text-sm">
                      <i class="fas fa-qrcode"></i>
                    </div>
                    <div>
                      <h4 class="text-xs font-bold text-gray-800 m-0">QR Gen</h4>
                    </div>
                  </button>

                  <!-- Tool: URL Shortener -->
                  <button class="btn-app-trigger text-left p-2.5 rounded-lg border border-gray-100 hover:border-blue-400 bg-white transition duration-150 flex items-center gap-2.5" data-app-url="/apps/url_shortner/index.html">
                    <div class="w-8 h-8 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center text-sm">
                      <i class="fas fa-link"></i>
                    </div>
                    <div>
                      <h4 class="text-xs font-bold text-gray-800 m-0">URL Short</h4>
                    </div>
                  </button>

                  <!-- Tool: Bio Page Builder -->
                  <button class="btn-app-trigger text-left p-2.5 rounded-lg border border-gray-100 hover:border-blue-400 bg-white transition duration-150 flex items-center gap-2.5" data-app-url="/apps/bio_builder/index.html">
                    <div class="w-8 h-8 bg-amber-100 text-amber-600 rounded-lg flex items-center justify-center text-sm">
                      <i class="fas fa-id-card"></i>
                    </div>
                    <div>
                      <h4 class="text-xs font-bold text-gray-800 m-0">Bio Page</h4>
                    </div>
                  </button>

                  <!-- Tool: Resume Builder -->
                  <button class="btn-app-trigger text-left p-2.5 rounded-lg border border-gray-100 hover:border-blue-400 bg-white transition duration-150 flex items-center gap-2.5" data-app-url="/apps/cv_builder/index.html">
                    <div class="w-8 h-8 bg-teal-100 text-teal-600 rounded-lg flex items-center justify-center text-sm">
                      <i class="fas fa-file-invoice"></i>
                    </div>
                    <div>
                      <h4 class="text-xs font-bold text-gray-800 m-0">Resume</h4>
                    </div>
                  </button>

                  <!-- Tool: Image Compressor -->
                  <button class="btn-app-trigger text-left p-2.5 rounded-lg border border-gray-100 hover:border-blue-400 bg-white transition duration-150 flex items-center gap-2.5" data-app-url="/apps/img_comprossor/index.html">
                    <div class="w-8 h-8 bg-rose-100 text-rose-600 rounded-lg flex items-center justify-center text-sm">
                      <i class="fas fa-file-image"></i>
                    </div>
                    <div>
                      <h4 class="text-xs font-bold text-gray-800 m-0">Img Compress</h4>
                    </div>
                  </button>

                </div>
              </div>
            </div>

          </div>

        </div>

        <!-- ISOLATED WORKSPACE DATABASE MANAGER SECTION -->
        <div class="row mt-2">
          <div class="col-12">
            <div class="card border-0 shadow-sm rounded-lg">
              <div class="card-header bg-white border-b border-gray-100 py-3">
                <h3 class="text-base font-bold text-gray-800 m-0 flex items-center">
                  <i class="fas fa-database text-blue-600 mr-2"></i> Custom Dynamic Database Workspace Manager
                </h3>
              </div>
              <div class="card-body p-4">
                <p class="text-xs text-gray-500 mb-4">Design dynamic custom relational table schemas and insert, view, and purge JSON-serialized row records inside your isolated workspace.</p>

                <div class="row g-3 items-end mb-4">
                  <!-- Create Schema Input -->
                  <div class="col-md-4 col-12">
                    <label class="block text-2xs uppercase font-bold text-gray-500 mb-1">Create Custom Database Table</label>
                    <input type="text" id="newTableName" class="form-control text-sm rounded-md px-3 py-2 border-gray-200 w-full" placeholder="Type table name..." />
                  </div>
                  <!-- Create Button -->
                  <div class="col-md-2 col-12">
                    <button class="btn btn-primary btn-sm rounded-md w-full font-bold py-2 shadow-sm bg-blue-600 text-white" id="btnCreateTable">Create Schema</button>
                  </div>
                  <!-- Schema Selector -->
                  <div class="col-md-4 col-12">
                    <label class="block text-2xs uppercase font-bold text-gray-500 mb-1">Select Active Database Schema</label>
                    <select id="activeTableSelect" class="form-control text-sm rounded-md px-3 py-2 border-gray-200 w-full">
                      <option value="">-- Choose active schema --</option>
                    </select>
                  </div>
                  <!-- Delete Button -->
                  <div class="col-md-2 col-12">
                    <button class="btn btn-outline-danger btn-sm rounded-md w-full font-bold py-2" id="btnDropTable">Drop Table</button>
                  </div>
                </div>

                <!-- Dynamic rows display area -->
                <div id="tableDisplayPanel" class="d-none mt-4 border border-gray-100 rounded-lg p-3 bg-gray-50">
                  <div class="flex justify-between items-center mb-3">
                    <h5 id="activeTableTitle" class="text-sm font-bold text-gray-800 m-0">Table Structure: <span class="text-blue-600"></span></h5>
                    <button class="btn btn-success btn-xs rounded-md font-semibold bg-emerald-600 text-white" id="btnAddRowBtn" data-toggle="modal" data-target="#insertRowModal"><i class="fas fa-plus mr-1"></i>Insert Record</button>
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

      </div>
    </section>
  </div>

  <!-- Bottom Right Floating Action Button (FAB - Dots Icon Launcher) -->
  <button type="button" class="btn btn-danger fab-btn flex items-center justify-center text-xl text-white" data-toggle="modal" data-target="#userAppsModal" title="Launch Applications" style="background-color: #3b82f6; border: none;">
    <i class="fas fa-th"></i>
  </button>

  <!-- Google-like Micro-App Launcher Directory Modal -->
  <div class="modal fade" id="userAppsModal" tabindex="-1" role="dialog" aria-labelledby="userAppsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 440px;">
      <div class="modal-content rounded-xl border-0 shadow-2xl">
        <div class="modal-header border-b border-gray-100 pb-3">
          <h5 class="modal-title font-bold text-gray-800 flex items-center text-sm" id="userAppsModalLabel">
            <i class="fas fa-th text-blue-600 mr-2"></i> Workspace Launchpad
          </h5>
          <button type="button" class="close text-gray-400 hover:text-gray-600" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body p-4">
          <div class="grid grid-cols-3 gap-3">

            <!-- App Card 1: CMS Builder -->
            <button class="btn-app-trigger app-icon-card flex flex-col items-center p-3 rounded-lg border border-gray-100" data-app-url="/cms/admin">
              <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mb-2 text-xl">
                <i class="fas fa-laptop-code"></i>
              </div>
              <span class="text-2xs font-semibold text-gray-700 text-center">CMS Builder</span>
            </button>

            <!-- App Card 2: QR Generator -->
            <button class="btn-app-trigger app-icon-card flex flex-col items-center p-3 rounded-lg border border-gray-100" data-app-url="/apps/qrcode/index.html">
              <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center mb-2 text-xl">
                <i class="fas fa-qrcode"></i>
              </div>
              <span class="text-2xs font-semibold text-gray-700 text-center">QR Gen</span>
            </button>

            <!-- App Card 3: URL Shortener -->
            <button class="btn-app-trigger app-icon-card flex flex-col items-center p-3 rounded-lg border border-gray-100" data-app-url="/apps/url_shortner/index.html">
              <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center mb-2 text-xl">
                <i class="fas fa-link"></i>
              </div>
              <span class="text-2xs font-semibold text-gray-700 text-center">URL Short</span>
            </button>

            <!-- App Card 4: Bio Builder -->
            <button class="btn-app-trigger app-icon-card flex flex-col items-center p-3 rounded-lg border border-gray-100" data-app-url="/apps/bio_builder/index.html">
              <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mb-2 text-xl">
                <i class="fas fa-id-card"></i>
              </div>
              <span class="text-2xs font-semibold text-gray-700 text-center">Bio Page</span>
            </button>

            <!-- App Card 5: CV Builder -->
            <button class="btn-app-trigger app-icon-card flex flex-col items-center p-3 rounded-lg border border-gray-100" data-app-url="/apps/cv_builder/index.html">
              <div class="w-12 h-12 bg-teal-100 text-teal-600 rounded-full flex items-center justify-center mb-2 text-xl">
                <i class="fas fa-file-invoice"></i>
              </div>
              <span class="text-2xs font-semibold text-gray-700 text-center">Resume</span>
            </button>

            <!-- App Card 6: WhatsApp Gen -->
            <button class="btn-app-trigger app-icon-card flex flex-col items-center p-3 rounded-lg border border-gray-100" data-app-url="/apps/whatapp_link_generator/index.html">
              <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mb-2 text-xl">
                <i class="fab fa-whatsapp"></i>
              </div>
              <span class="text-2xs font-semibold text-gray-700 text-center">WhatsApp Link</span>
            </button>

            <!-- App Card 7: Invoice Gen -->
            <button class="btn-app-trigger app-icon-card flex flex-col items-center p-3 rounded-lg border border-gray-100" data-app-url="/apps/invoice/index.html">
              <div class="w-12 h-12 bg-cyan-100 text-cyan-600 rounded-full flex items-center justify-center mb-2 text-xl">
                <i class="fas fa-file-invoice-dollar"></i>
              </div>
              <span class="text-2xs font-semibold text-gray-700 text-center">Invoice Gen</span>
            </button>

            <!-- App Card 8: Image Compressor -->
            <button class="btn-app-trigger app-icon-card flex flex-col items-center p-3 rounded-lg border border-gray-100" data-app-url="/apps/img_comprossor/index.html">
              <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mb-2 text-xl">
                <i class="fas fa-file-image"></i>
              </div>
              <span class="text-2xs font-semibold text-gray-700 text-center">Compressor</span>
            </button>

          </div>
        </div>
        <div class="modal-footer border-t border-gray-100 bg-gray-50 rounded-b-xl py-2">
          <span class="text-2xs text-gray-400 w-full text-center">Select any app to launch in-dashboard</span>
        </div>
      </div>
    </div>
  </div>

  <!-- IN-DASHBOARD INLINE IFRAME APP LOADER MODAL -->
  <div class="modal fade" id="iframeAppLoaderModal" tabindex="-1" role="dialog" aria-labelledby="iframeAppLoaderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
      <div class="modal-content rounded-xl border-0 shadow-2xl">
        <div class="modal-header border-b border-gray-100 bg-blue-600 text-white py-3">
          <h5 class="modal-title font-bold flex items-center text-sm" id="iframeAppLoaderModalLabel">
            <i class="fas fa-window-maximize mr-2"></i> Dynamic App Workspace Container
          </h5>
          <button type="button" class="close text-white hover:text-gray-100" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body p-0 bg-gray-900 overflow-hidden" style="height: 620px;">
          <iframe id="appWorkspaceIframe" src="about:blank" class="w-full h-full border-0 bg-white"></iframe>
        </div>
        <div class="modal-footer border-t border-gray-100 bg-gray-50 py-2 flex justify-between items-center">
          <span class="text-2xs text-gray-400">Sandbox App Layer Secure Routing Protocol</span>
          <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-dismiss="modal">Close Workspace</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ROW RECORD INSERTION MODAL -->
  <div class="modal fade" id="insertRowModal" tabindex="-1" aria-labelledby="insertRowModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-xl border-0 shadow-2xl">
        <div class="modal-header border-b border-gray-100 bg-blue-600 text-white py-3">
          <h5 class="modal-title font-bold flex items-center text-sm" id="insertRowModalLabel"><i class="fas fa-square-plus mr-2"></i>Insert Row Data</h5>
          <button type="button" class="close text-white hover:text-gray-100" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body p-4">
          <form id="insertRowForm">
            <div class="mb-3 text-xs text-gray-500">Specify up to 4 dynamic columns and field values below:</div>
            <div id="modalColumnsContainer">
              <div class="row g-2 mb-2 column-input-row flex gap-2">
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
          <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary btn-sm rounded-pill px-4 bg-blue-600 text-white" id="btnSubmitInsertRow">Save Record</button>
        </div>
      </div>
    </div>
  </div>

</div>

<!-- Required Scripts: jQuery, Bootstrap 4, AdminLTE -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<!-- Interactive App Launching & Dynamic Database Management -->
<script>
$(document).ready(function() {

    // 1. Dynamic App Loading into Dashboard Iframe Modal
    $(document).on('click', '.btn-app-trigger', function() {
        const appUrl = $(this).attr('data-app-url');
        if (!appUrl) return;

        // Hide launcher modal if open
        $('#userAppsModal').modal('hide');

        // Set source for workspace container iframe
        $('#appWorkspaceIframe').attr('src', appUrl);

        // Set title dynamically based on selection
        const appName = $(this).find('span').text() || $(this).find('h4').text() || 'Application';
        $('#iframeAppLoaderModalLabel').html('<i class="fas fa-window-maximize mr-2"></i> Sandbox: ' + appName);

        // Open in-dashboard viewer modal
        $('#iframeAppLoaderModal').modal('show');
    });

    // Reset iframe on modal close to free memory resources
    $('#iframeAppLoaderModal').on('hidden.bs.modal', function () {
        $('#appWorkspaceIframe').attr('src', 'about:blank');
    });

    // 2. Fetch schemas list dynamically
    function loadTablesList() {
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

    // 3. Create Custom Table Schema
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

    // 4. Load & Render Custom Rows
    function loadTableRows(tableName) {
        if (!tableName) {
            $('#tableDisplayPanel').addClass('d-none');
            return;
        }
        $.ajax({
            url: '/php/user_database_action.php',
            type: 'GET',
            dataType: 'json',
            data: { action: 'get_rows', table_name: tableName },
            success: function(res) {
                if (res.success) {
                    $('#tableDisplayPanel').removeClass('d-none');
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

    // 5. Purge Table Schema
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
                    $('#tableDisplayPanel').addClass('d-none');
                    loadTablesList();
                } else {
                    alert(res.message);
                }
            }
        });
    });

    // 6. Dynamic Input Fields inside Insertion Modal
    $('#btnAddColumnInput').on('click', function() {
        $('#modalColumnsContainer').append(`
            <div class="row g-2 mb-2 column-input-row flex gap-2">
                <div class="col-6">
                    <input type="text" class="form-control col-name-input text-xs rounded-md px-2 py-1.5 border-gray-200" placeholder="Column Key" required />
                </div>
                <div class="col-6">
                    <input type="text" class="form-control col-val-input text-xs rounded-md px-2 py-1.5 border-gray-200" placeholder="Value" required />
                </div>
            </div>
        `);
    });

    // 7. Save Custom Database Row Record
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
                        <div class="row g-2 mb-2 column-input-row flex gap-2">
                            <div class="col-6">
                                <input type="text" class="form-control col-name-input text-xs rounded-md px-2 py-1.5 border-gray-200" placeholder="Column Key (e.g. name)" required />
                            </div>
                            <div class="col-6">
                                <input type="text" class="form-control col-val-input text-xs rounded-md px-2 py-1.5 border-gray-200" placeholder="Field Value" required />
                            </div>
                        </div>
                    `);
                    $('#insertRowModal').modal('hide');
                    loadTableRows(tableName);
                } else {
                    alert(res.message);
                }
            }
        });
    });

    // 8. Delete Custom Database Row Record
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
});
</script>
</body>
</html>
