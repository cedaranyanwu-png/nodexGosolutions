<?php
/**
 * template.php
 *
 * Presentation layer rendering HTML layout and mounting React application.
 */

if (!isset($tenant)) {
    exit('Direct access not allowed.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tenant['name']); ?> | NodeX Multi-Tenant</title>
    <!-- External modular CSS stylesheet -->
    <link rel="stylesheet" href="css/style.css">
    <!-- React and ReactDOM CDN libraries -->
    <script src="https://unpkg.com/react@18/umd/react.development.js" crossorigin></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.development.js" crossorigin></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
</head>
<body style="--theme-color: <?php echo htmlspecialchars($tenant['theme_color']); ?>;">

    <!-- Initial PHP Tenant Data payload passed safely to JavaScript -->
    <script>
        window.INITIAL_TENANT_DATA = <?php echo json_encode($tenant, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        window.ALL_TENANTS_DATA = <?php
            $allTenants = (new TenantManager())->getAllTenants();
            echo json_encode($allTenants, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        ?>;
    </script>

    <!-- Header Section -->
    <header id="app-header">
        <img id="tenant-logo" src="<?php echo htmlspecialchars($tenant['logo_url']); ?>" alt="<?php echo htmlspecialchars($tenant['name']); ?> Logo">
        <h1 id="tenant-title"><?php echo htmlspecialchars($tenant['name']); ?></h1>
    </header>

    <!-- Main React Mount Point Container -->
    <main class="container">
        <div id="react-app-root">
            <h2>About This Tenant</h2>
            <p><?php echo htmlspecialchars($tenant['content']); ?></p>
            <hr>
            <h3>System Details</h3>
            <ul>
                <li><strong>Tenant ID:</strong> <?php echo (int)$tenant['id']; ?></li>
                <li><strong>Identifier:</strong> <?php echo htmlspecialchars($tenant['identifier']); ?></li>
                <li><strong>Current Host:</strong> <?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'Unknown'); ?></li>
            </ul>
        </div>
    </main>

    <!-- Footer Section -->
    <footer>
        <p>&copy; <?php echo date('Y'); ?> NodeX Go Solutions. All rights reserved.</p>
        <p>Tenant-specific instance of the <strong>nodexGosolutions</strong> architecture.</p>
    </footer>

    <!-- Modular React Application JS -->
    <script type="text/babel" src="js/app.js"></script>
</body>
</html>
