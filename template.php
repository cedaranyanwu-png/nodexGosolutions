<?php
/**
 * template.php
 *
 * This is the main presentation layer file. It receives a $tenant array
 * and uses its values to dynamically render the HTML layout, colors, and content.
 */

// Exit if the $tenant variable is not set, preventing direct file access
if (!isset($tenant)) {
    exit('Direct access not allowed.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Use the tenant's name as the page title -->
    <title><?php echo htmlspecialchars($tenant['name']); ?> | NodeX Multi-Tenant</title>
    <?php
    // If this is the main website (Tenant ID 1: nodexgosolutions.com), add the company favicon reference.
    if (isset($tenant['id']) && $tenant['id'] === 1) {
        // Output standard favicon with cache-busting parameter
        echo '    <link rel="icon" type="image/png" href="/assets/images/favicon.png?v=1">' . PHP_EOL;
    }
    ?>
    <style>
        /* Base styles for the layout */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
            color: #333;
            line-height: 1.6;
        }

        /* Dynamic header styling using the tenant's specific theme color */
        header {
            background-color: <?php echo htmlspecialchars($tenant['theme_color']); ?>;
            color: #fff;
            padding: 2rem 1rem;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        header img {
            max-width: 200px;
            height: auto;
            margin-bottom: 1rem;
            border: 2px solid #fff;
            border-radius: 8px;
        }

        /* Container for the main content */
        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        /* Footer styling */
        footer {
            text-align: center;
            margin-top: 3rem;
            padding: 1rem;
            font-size: 0.9rem;
            color: #777;
        }

        /* Dynamic text color for links based on theme */
        a {
            color: <?php echo htmlspecialchars($tenant['theme_color']); ?>;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <header>
        <!-- Display the tenant's unique logo -->
        <img src="<?php echo htmlspecialchars($tenant['logo_url']); ?>" alt="<?php echo htmlspecialchars($tenant['name']); ?> Logo">
        <h1><?php echo htmlspecialchars($tenant['name']); ?></h1>
    </header>

    <!-- Main Content Section -->
    <main class="container">
        <h2>About This Tenant</h2>
        <!-- Display the tenant's unique description/content -->
        <p><?php echo htmlspecialchars($tenant['content']); ?></p>

        <hr>

        <h3>System Details</h3>
        <ul>
            <li><strong>Tenant ID:</strong> <?php echo (int)$tenant['id']; ?></li>
            <li><strong>Identifier:</strong> <?php echo htmlspecialchars($tenant['identifier']); ?></li>
            <li><strong>Current Host:</strong> <?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'Unknown'); ?></li>
        </ul>
    </main>

    <!-- Footer Section -->
    <footer>
        <p>&copy; <?php echo date('Y'); ?> NodeX Go Solutions. All rights reserved.</p>
        <p>Tenant-specific instance of the <strong>nodexGosolutions</strong> architecture.</p>
    </footer>

</body>
</html>
