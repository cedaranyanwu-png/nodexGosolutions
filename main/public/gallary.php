<?php
/**
 * gallary.php
 *
 * Renders corporate gallery media.
 * Styled with premium Tailwind CSS and modular elements.
 */

declare(strict_types=1);
require_once __DIR__ . '/../../php/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?= renderSeoHead([
        'title' => 'Gallery | nodexGosolutions',
        'description' => 'Visual highlights of our space telemetry downlinks, agritech spatial maps, and platform deployments.',
        'og_title' => 'Gallery | nodexGosolutions',
        'og_description' => 'Visual highlights of our space telemetry downlinks, agritech spatial maps, and platform deployments.',
        'images' => [
            '/main/assets/images/about-banner-1.jpg',
            '/main/assets/images/about-banner-2.jpg',
            '/main/assets/images/cedar-anyanwu.jpg'
        ],
        'og_type' => 'website',
        'schema_type' => 'ImageGallery',
        'breadcrumbs' => [
            ['name' => 'Gallery', 'url' => '/gallary']
        ]
    ]) ?>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@300;400;600;700&family=Orbitron:wght@600;700;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-700 min-h-screen flex flex-col justify-between">

    <?php require_once __DIR__ . '/../modul/nav.html'; ?>

    <main class="container mx-auto max-w-5xl my-16 px-4">
        <div class="text-center mb-12">
            <h1 class="text-3xl font-extrabold text-slate-900 mb-2" style="font-family: 'Orbitron', sans-serif;">Corporate Gallery</h1>
            <p class="text-slate-500">Visual highlights of our space telemetry downlinks, agritech spatial maps, and platform deployments.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white border border-slate-100 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                <img src="/watermarked_img_2473855018031345766.png" class="w-full h-48 object-cover" alt="Sensing telemetry" />
                <div class="p-4">
                    <h5 class="font-bold text-slate-900">Orbital Ground Station</h5>
                    <p class="text-xs text-slate-500">Processing real-time land indexing telemetry downlinks.</p>
                </div>
            </div>
            <div class="bg-white border border-slate-100 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                <img src="/watermarked_img_2473855018031345766.png" class="w-full h-48 object-cover" alt="Agritech analysis" />
                <div class="p-4">
                    <h5 class="font-bold text-slate-900">Sovereign Agritech GIS</h5>
                    <p class="text-xs text-slate-500">Soil moisture and crop yield tracking models.</p>
                </div>
            </div>
            <div class="bg-white border border-slate-100 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                <img src="/watermarked_img_2473855018031345766.png" class="w-full h-48 object-cover" alt="CMS dashboard" />
                <div class="p-4">
                    <h5 class="font-bold text-slate-900">Ecosystem Cloud Nodes</h5>
                    <p class="text-xs text-slate-500">White-label modular software dashboard interfaces.</p>
                </div>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/../modul/footer.html'; ?>

</body>
</html>
