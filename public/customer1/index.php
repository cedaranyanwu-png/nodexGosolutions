<?php
/**
 * public/customer1/index.php
 *
 * TENANT Application.
 * Physically located inside /public/customer1/.
 * Operates as an isolated tenant website.
 */

declare(strict_types=1);

if (!isset($tenant)) {
    $tenant = [
        'name' => 'Acme Corp Portal',
        'theme_color' => '#27ae60',
        'logo_url' => 'https://placehold.co/200x50/27ae60/ffffff?text=Acme+Corp',
        'content' => 'This is the private portal for Acme Corp.'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tenant['name']); ?> | Isolated Tenant</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background-color: #f4f4f9; color: #333; }
        header { background-color: <?php echo htmlspecialchars($tenant['theme_color']); ?>; color: #fff; padding: 2rem 1rem; text-align: center; }
        .badge { background-color: #16a34a; color: white; padding: 0.3rem 0.8rem; border-radius: 9999px; font-size: 0.85rem; font-weight: bold; display: inline-block; margin-bottom: 1rem; }
        .container { max-width: 800px; margin: 2rem auto; padding: 2rem; background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        footer { text-align: center; margin-top: 3rem; padding: 1rem; font-size: 0.9rem; color: #777; }
    </style>
</head>
<body>

    <header>
        <span class="badge">ISOLATED TENANT</span>
        <h1><?php echo htmlspecialchars($tenant['name']); ?></h1>
    </header>

    <main class="container">
        <h2>Tenant Dashboard</h2>
        <p><?php echo htmlspecialchars($tenant['content']); ?></p>

        <hr>

        <h3>Tenant Details</h3>
        <ul>
            <li><strong>Tenant ID:</strong> <?php echo (int)($tenant['id'] ?? 3); ?></li>
            <li><strong>Identifier:</strong> <?php echo htmlspecialchars($tenant['identifier'] ?? ''); ?></li>
            <li><strong>Isolation Mode:</strong> Strict Tenant Isolation</li>
        </ul>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($tenant['name']); ?>. All rights reserved.</p>
    </footer>

</body>
</html>
