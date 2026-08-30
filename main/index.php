<?php
/**
 * main/index.php
 *
 * Main Website & OS Control Center Interface.
 * Controls, manages, and monitors the entire platform including connected sites and isolated tenants.
 */

declare(strict_types=1);

if (!isset($tenant)) {
    $tenant = [
        'name' => sys_config('app.name', 'NodeX Go Solutions HQ'),
        'theme_color' => '#2c3e50',
        'logo_url' => 'https://placehold.co/200x50/2c3e50/ffffff?text=NodeX+HQ',
        'content' => 'Main Website & Central OS Control Center'
    ];
}

$status = SysServices::getSystemStatus();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tenant['name']); ?> | OS Control Center</title>
    <style>
        body { font-family: 'Segoe UI', system-ui, sans-serif; margin: 0; background-color: #0f172a; color: #f8fafc; }
        header { background-color: #1e293b; padding: 1.5rem 2rem; border-bottom: 1px solid #334155; display: flex; justify-content: space-between; align-items: center; }
        .logo-area { display: flex; align-items: center; gap: 1rem; }
        .logo-area img { max-height: 40px; border-radius: 4px; }
        .badge { background: #3b82f6; color: #fff; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.85rem; font-weight: 600; }
        .container { max-width: 1100px; margin: 2rem auto; padding: 0 1rem; }
        .card-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-top: 1.5rem; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 8px; padding: 1.5rem; }
        .card h3 { margin-top: 0; color: #38bdf8; }
        footer { text-align: center; padding: 2rem; border-top: 1px solid #334155; color: #64748b; font-size: 0.9rem; margin-top: 3rem; }
    </style>
</head>
<body>

    <header>
        <div class="logo-area">
            <img src="<?php echo htmlspecialchars($tenant['logo_url']); ?>" alt="NodeX OS">
            <h2><?php echo htmlspecialchars($tenant['name']); ?></h2>
        </div>
        <div>
            <span class="badge">MAIN OS CONTROL CENTER</span>
        </div>
    </header>

    <main class="container">
        <div class="card">
            <h3>Central OS Dashboard</h3>
            <p><?php echo htmlspecialchars($tenant['content']); ?></p>
            <p><strong>Main Domain:</strong> <?php echo htmlspecialchars($status['main_domain']); ?></p>
            <p><strong>Platform Version:</strong> <?php echo htmlspecialchars($status['version']); ?></p>
        </div>

        <div class="card-grid">
            <div class="card">
                <h3>Registered Configurations</h3>
                <ul>
                    <li><strong>App:</strong> <?php echo htmlspecialchars((string)sys_config('app.name')); ?></li>
                    <li><strong>Environment:</strong> <?php echo htmlspecialchars((string)sys_config('app.environment')); ?></li>
                    <li><strong>Multi-Tenancy:</strong> <?php echo sys_config('features.multi_tenancy') ? 'Enabled' : 'Disabled'; ?></li>
                </ul>
            </div>

            <div class="card">
                <h3>Managed Websites & Tenants</h3>
                <ul>
                    <?php
                    $tenantsList = sys_config('tenants', []);
                    foreach ($tenantsList as $t):
                    ?>
                        <li>
                            <strong><?php echo htmlspecialchars($t['name']); ?></strong>
                            (<?php echo htmlspecialchars($t['identifier']); ?>) -
                            <span class="badge" style="background:#475569;"><?php echo htmlspecialchars($t['type'] ?? 'TENANT'); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($status['app_name']); ?>. Central OS Control Center.</p>
    </footer>

</body>
</html>
