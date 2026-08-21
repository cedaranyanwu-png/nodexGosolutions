<?php
/**
 * traction.php
 *
 * Renders corporate traction metrics and agriculture partners details.
 * Styled with premium Tailwind CSS and modular elements.
 */

declare(strict_types=1);
require_once __DIR__ . '/../../php/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?= renderSeoHead([
        'title' => 'Traction & Metrics | nodexGosolutions',
        'description' => 'Explore our solid growth statistics, verified regional agriculture, and digital transformation milestones.',
        'og_title' => 'Traction & Metrics | nodexGosolutions',
        'og_description' => 'Explore our solid growth statistics, verified regional agriculture, and digital transformation milestones.',
        'images' => ['/main/assets/images/about-banner-1.jpg', '/main/assets/images/logo.png'],
        'og_type' => 'website',
        'schema_type' => 'WebPage',
        'breadcrumbs' => [
            ['name' => 'Traction', 'url' => '/traction']
        ]
    ]) ?>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@300;400;600;700&family=Orbitron:wght@600;700;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/public-space.css?v=20260820">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-slate-50 text-slate-700 min-h-screen flex flex-col justify-between nx-public-shell">
<div class="nx-space-stars"></div>

    <?php require_once __DIR__ . '/../modul/nav.html'; ?>

    <main class="container mx-auto max-w-4xl my-16 px-4">
        <div class="text-center mb-12">
            <h1 class="text-3xl font-extrabold text-slate-900 mb-2" style="font-family: 'Orbitron', sans-serif;">Platform Traction</h1>
            <p class="text-slate-500">Explore our solid growth statistics, verified regional agriculture, and digital transformation milestones.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
            <div class="bg-white border border-slate-100 rounded-xl p-6 shadow-sm">
                <h4 class="text-lg font-bold text-slate-900 mb-3" style="font-family: 'Orbitron', sans-serif;"><i class="fa-solid fa-seedling text-blue-600 mr-2"></i> Agritech Integration</h4>
                <p class="text-slate-600 text-sm leading-relaxed">Through our spatial satellite telemetry maps, we track agricultural soil health metrics across thousands of farms, improving yields by over 35% annually.</p>
            </div>
            <div class="bg-white border border-slate-100 rounded-xl p-6 shadow-sm">
                <h4 class="text-lg font-bold text-slate-900 mb-3" style="font-family: 'Orbitron', sans-serif;"><i class="fa-solid fa-network-wired text-blue-600 mr-2"></i> Cloud Expansion</h4>
                <p class="text-slate-600 text-sm leading-relaxed">Our modular single-entry multi-tenant platform now processes millions of monthly active server requests with near-zero latency, maintaining a robust 99.99% system uptime.</p>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/../modul/footer.html'; ?>

</body>
</html>
