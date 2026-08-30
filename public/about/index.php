<?php
/**
 * public/about/index.php
 *
 * MAIN-CONNECTED Application.
 * Physically located inside /public/about/, but explicitly connected to the Main Website & OS System.
 * Accesses central OS services without needing duplicate code.
 */

declare(strict_types=1);

if (!isset($tenant)) {
    $tenant = [
        'name' => 'About NodeX Platform',
        'theme_color' => '#2980b9',
        'logo_url' => 'https://placehold.co/200x50/2980b9/ffffff?text=About+NodeX',
        'content' => 'Main-connected application accessing central platform services.'
    ];
}

// MAIN-CONNECTED application leverages central OS services directly
$osStatus = class_exists('SysServices') ? SysServices::getSystemStatus() : ['status' => 'disconnected'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tenant['name']); ?> | Main-Connected App</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background-color: #f8fafc; color: #1e293b; }
        header { background-color: <?php echo htmlspecialchars($tenant['theme_color']); ?>; color: #fff; padding: 2rem 1rem; text-align: center; }
        .badge { background-color: #0284c7; color: white; padding: 0.3rem 0.8rem; border-radius: 9999px; font-size: 0.85rem; font-weight: bold; display: inline-block; margin-bottom: 1rem; }
        .container { max-width: 800px; margin: 2rem auto; padding: 2rem; background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        footer { text-align: center; margin-top: 3rem; padding: 1rem; font-size: 0.9rem; color: #64748b; }
    </style>
</head>
<body>

    <header>
        <span class="badge">MAIN-CONNECTED APPLICATION</span>
        <h1><?php echo htmlspecialchars($tenant['name']); ?></h1>
    </header>

    <main class="container">
        <h2>Connected Portal Overview</h2>
        <p><?php echo htmlspecialchars($tenant['content']); ?></p>

        <hr>

        <h3>Central OS Connection</h3>
        <ul>
            <li><strong>Connection Status:</strong> Active (Main-Connected)</li>
            <li><strong>Central OS Platform:</strong> <?php echo htmlspecialchars($osStatus['app_name'] ?? ''); ?></li>
            <li><strong>Main Domain:</strong> <?php echo htmlspecialchars($osStatus['main_domain'] ?? ''); ?></li>
            <li><strong>System Version:</strong> <?php echo htmlspecialchars($osStatus['version'] ?? ''); ?></li>
        </ul>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(sys_config('app.name', 'NodeX Platform')); ?>. Main-Connected Public Portal.</p>
    </footer>

</body>
</html>
