<?php
/**
 * dashboard.php
 *
 * This is the enhanced Client-Side Tenant Control Panel.
 * It serves as a centralized hub offering:
 * - "Tools on Top" quick launch bar for all sub-apps (QR Codes, short URLs, invoice creator, image compressor, etc.)
 * - Direct Web Deployment & CMS links
 * - Live dynamic lists of their active deployments (Bios, short links, QR codes) queried directly from our custom JSON Database
 * - Back-to-Main landing page navigation links
 */

// Enable strict typing for safety
declare(strict_types=1);

// Require central database configuration and security helpers
require_once __DIR__ . '/../../../php/db.php';

// Instantiate secure session configurations
secureSession();

// Access Control: Verify that the user is logged in
if (!isset($_SESSION['email'])) {
    // If not logged in, redirect to login screen
    header('Location: /login');
    // Terminate script execution
    exit;
}

// Fetch list of links, qr codes, and biography records to display dynamically in the control panel
$urlDb     = new Database(__DIR__ . '/../../../databases', 'url_shortner');
$qrDb      = new Database(__DIR__ . '/../../../databases', 'qrcode');
$bioDb     = new Database(__DIR__ . '/../../../databases', 'bio_builder');

// Load records list
$shortLinks = $urlDb->select('links');
$qrCodes    = $qrDb->select('qrcodes');
$biosList   = $bioDb->select('bios');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenant Control Panel | nodexGosolutions</title>

    <!-- Load standard Fonts and Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@300;400;600;700&family=Orbitron:wght@600;700;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --bg-dark: #0b0f1e;
            --card-bg: #131a35;
            --accent: #00d2ff;
            --accent-hover: #00a2cc;
            --text: #f8fafc;
            --text-muted: #8a99ad;
            --border-color: rgba(0, 210, 255, 0.15);
        }
        body {
            background-color: var(--bg-dark);
            color: var(--text);
            font-family: 'Source Sans 3', sans-serif;
            margin: 0;
            padding: 0;
        }
        .navbar-custom {
            background: linear-gradient(135deg, #0072ff, #00d2ff);
            box-shadow: 0 4px 15px rgba(0, 210, 255, 0.25);
            padding: 15px 30px;
        }
        .navbar-brand-custom {
            font-family: 'Orbitron', sans-serif;
            font-weight: 900;
            color: white !important;
            font-size: 22px;
            letter-spacing: 0.5px;
        }
        .nav-link-custom {
            color: white !important;
            font-weight: 600;
            margin-left: 20px;
            transition: opacity 0.3s;
        }
        .nav-link-custom:hover {
            opacity: 0.8;
        }
        .hero-banner {
            background: linear-gradient(135deg, rgba(0, 114, 255, 0.12), rgba(0, 210, 255, 0.12));
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 45px;
            margin-top: 40px;
        }
        .hero-banner h2 {
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            color: white;
            margin-bottom: 8px;
        }
        /* Tools On Top Row Grid */
        .tool-box-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 18px;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
            color: var(--text);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 120px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.15);
        }
        .tool-box-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 210, 255, 0.3);
            color: var(--accent);
        }
        .tool-icon {
            font-size: 26px;
            color: var(--accent);
            margin-bottom: 10px;
        }
        .tool-title {
            font-weight: bold;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        /* Section Cards */
        .panel-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }
        .panel-card h3 {
            font-family: 'Orbitron', sans-serif;
            font-size: 16px;
            margin-bottom: 20px;
            color: white;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            padding-bottom: 10px;
        }
        /* Table Styles */
        .table-responsive-custom {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            vertical-align: middle;
        }
        th {
            font-family: 'Orbitron', sans-serif;
            color: var(--accent);
            font-size: 11px;
            text-transform: uppercase;
        }
        tr:hover {
            background: rgba(255,255,255,0.01);
        }
        .btn-preview-link {
            font-size: 12px;
            font-weight: bold;
            text-decoration: none;
            color: var(--accent);
            border: 1px solid var(--accent);
            padding: 4px 10px;
            border-radius: 4px;
            transition: all 0.2s;
        }
        .btn-preview-link:hover {
            background-color: var(--accent);
            color: var(--bg-dark);
        }
    </style>
</head>
<body>

    <!-- Unified Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid d-flex justify-content-between">
            <a class="navbar-brand-custom" href="/"><i class="fa-solid fa-server me-2"></i>nodexGo Portal</a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3 small"><i class="fa-solid fa-user me-1"></i> <?php echo htmlspecialchars($_SESSION['fullname'] ?? $_SESSION['email']); ?></span>
                <!-- Clean back-to-main page link -->
                <a class="btn btn-outline-light btn-sm rounded-pill px-3 nav-link-custom" href="/"><i class="fa-solid fa-arrow-left me-1"></i> Return to Main Page</a>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="container py-4">

        <!-- Welcome Banner Section -->
        <div class="hero-banner text-center">
            <h2>Welcome to Your Tenant Workspace Control Panel!</h2>
            <p class="text-muted mb-0">Unify deployment tools on demand. Launch apps, create shortened URLs, generate QR codes, and monitor active deployments below.</p>
        </div>

        <!-- TOOLS ON TOP: Quick Launch Bar -->
        <h4 class="mb-3 text-uppercase small fw-bold text-muted tracking-wide" style="font-family: 'Orbitron', sans-serif; letter-spacing: 1px;">
            <i class="fa-solid fa-cubes me-2 text-primary"></i>Quick Deployment Tools (Tools on Top)
        </h4>
        <div class="row g-3 mb-5">
            <!-- Web Deployment & CMS Builder -->
            <div class="col-md-3 col-sm-6">
                <a href="/cms/admin.php" class="tool-box-card">
                    <span class="tool-icon"><i class="fa-solid fa-laptop-code"></i></span>
                    <span class="tool-title">Web Builder & CMS</span>
                </a>
            </div>
            <!-- Dynamic QR Code Generator -->
            <div class="col-md-3 col-sm-6">
                <a href="/apps/qrcode/index.html" class="tool-box-card">
                    <span class="tool-icon"><i class="fa-solid fa-qrcode"></i></span>
                    <span class="tool-title">QR Code Gen</span>
                </a>
            </div>
            <!-- Mini URL Shortener -->
            <div class="col-md-3 col-sm-6">
                <a href="/apps/url_shortner/index.html" class="tool-box-card">
                    <span class="tool-icon"><i class="fa-solid fa-link"></i></span>
                    <span class="tool-title">URL Shortener</span>
                </a>
            </div>
            <!-- Digital Bio Page Builder -->
            <div class="col-md-3 col-sm-6">
                <a href="/apps/bio_builder/index.html" class="tool-box-card">
                    <span class="tool-icon"><i class="fa-solid fa-id-card"></i></span>
                    <span class="tool-title">Bio Page Builder</span>
                </a>
            </div>
            <!-- Interactive CV/Resume Builder -->
            <div class="col-md-3 col-sm-6">
                <a href="/apps/cv_builder/index.html" class="tool-box-card">
                    <span class="tool-icon"><i class="fa-solid fa-file-invoice"></i></span>
                    <span class="tool-title">Resume Builder</span>
                </a>
            </div>
            <!-- Dynamic Invoice Estimator -->
            <div class="col-md-3 col-sm-6">
                <a href="/apps/invoice/index.html" class="tool-box-card">
                    <span class="tool-icon"><i class="fa-solid fa-file-invoice-dollar"></i></span>
                    <span class="tool-title">Invoice Gen</span>
                </a>
            </div>
            <!-- WhatsApp Link Generator -->
            <div class="col-md-3 col-sm-6">
                <a href="/apps/whatapp_link_generator/index.html" class="tool-box-card">
                    <span class="tool-icon"><i class="fa-brands fa-whatsapp"></i></span>
                    <span class="tool-title">WhatsApp Gen</span>
                </a>
            </div>
            <!-- Batch Image Compressor -->
            <div class="col-md-3 col-sm-6">
                <a href="/apps/img_comprossor/index.html" class="tool-box-card">
                    <span class="tool-icon"><i class="fa-solid fa-file-image"></i></span>
                    <span class="tool-title">Img Compressor</span>
                </a>
            </div>
        </div>

        <!-- DYNAMIC CONTROL PANEL TABLES -->
        <div class="row">
            <!-- Active Short Links & QR Code Deployments -->
            <div class="col-md-6">
                <div class="panel-card h-100">
                    <h3><i class="fa-solid fa-rocket me-2 text-info"></i>Active Short Links</h3>
                    <div class="table-responsive-custom">
                        <table>
                            <thead>
                                <tr>
                                    <th>Short Code</th>
                                    <th>Original Destination URL</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php // Iterate through active shortened link deployments
                                if (!empty($shortLinks)):
                                    foreach (array_slice($shortLinks, -5) as $link): ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($link['code'] ?? ''); ?></code></td>
                                            <td class="text-truncate" style="max-width: 150px;"><?php echo htmlspecialchars($link['long_url'] ?? ''); ?></td>
                                            <td class="text-end">
                                                <a href="/php/url_shortner.php?c=<?php echo htmlspecialchars($link['code'] ?? ''); ?>" target="_blank" class="btn-preview-link">Visit</a>
                                            </td>
                                        </tr>
                                    <?php endforeach;
                                else: ?>
                                    <tr><td colspan="3" class="text-center text-muted small">No short URLs deployed yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Active Biography Pages -->
            <div class="col-md-6">
                <div class="panel-card h-100">
                    <h3><i class="fa-solid fa-address-book me-2 text-success"></i>Active Bio Profiles</h3>
                    <div class="table-responsive-custom">
                        <table>
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Display Name</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php // Iterate through active biography deployments
                                if (!empty($biosList)):
                                    foreach (array_slice($biosList, -5) as $bio): ?>
                                        <tr>
                                            <td><code>@<?php echo htmlspecialchars($bio['username'] ?? ''); ?></code></td>
                                            <td><?php echo htmlspecialchars($bio['display_name'] ?? ''); ?></td>
                                            <td class="text-end">
                                                <a href="/php/bio_builder.php?u=<?php echo htmlspecialchars($bio['username'] ?? ''); ?>" target="_blank" class="btn-preview-link">Visit Profile</a>
                                            </td>
                                        </tr>
                                    <?php endforeach;
                                else: ?>
                                    <tr><td colspan="3" class="text-center text-muted small">No bio profile pages deployed yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
