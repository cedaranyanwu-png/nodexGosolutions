<?php
/**
 * portfolio.php
 *
 * Renders executive platform portfolio.
 * Styled with premium Tailwind CSS and modular elements.
 */

declare(strict_types=1);
require_once __DIR__ . '/../../php/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?= renderSeoHead([
        'title' => 'Executive Portfolio | nodexGosolutions',
        'description' => 'Discover our strategic digital deployments, software engines, and dynamic GIS telemetry platforms.',
        'og_title' => 'Executive Portfolio | nodexGosolutions',
        'og_description' => 'Discover our strategic digital deployments, software engines, and dynamic GIS telemetry platforms.',
        'og_image' => '/main/assets/images/cedar-anyanwu.jpg',
        'og_type' => 'website',
        'schema_type' => 'ProfilePage',
        'person_params' => [
            'name' => 'Cedar Anyanwu',
            'url' => 'https://nodexplatform.com.ng',
            'image' => 'https://nodexplatform.com.ng',
            'jobTitle' => 'Chief Executive Officer',
            'organization' => 'Nodexplatform',
            'sameAs' => ['https://linkedin.com']
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
            <h1 class="text-3xl font-extrabold text-slate-900 mb-2" style="font-family: 'Orbitron', sans-serif;">Executive Portfolio</h1>
            <p class="text-slate-500">Discover our strategic digital deployments, software engines, and dynamic GIS telemetry platforms.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="bg-white border border-slate-100 rounded-xl p-6 shadow-sm">
                <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-1.5 rounded-full mb-3 inline-block">Boilerplate Software</span>
                <h4 class="text-xl font-bold text-slate-900 mb-2" style="font-family: 'Orbitron', sans-serif;">Modular CMS Router</h4>
                <p class="text-slate-600 text-sm leading-relaxed mb-4">A framework-free single-entry routing engine enabling users to instantly deploy subdomains, CMS widgets, and isolated local schemas with zero database overhead.</p>
            </div>
            <div class="bg-white border border-slate-100 rounded-xl p-6 shadow-sm">
                <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-1.5 rounded-full mb-3 inline-block">Sovereign Space Tech</span>
                <h4 class="text-xl font-bold text-slate-900 mb-2" style="font-family: 'Orbitron', sans-serif;">GIS Imagery Processing</h4>
                <p class="text-slate-600 text-sm leading-relaxed mb-4">Downlinking and processing raw satellite land telemetry feeds, delivering soil quality insights directly to agricultural cooperatives.</p>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/../modul/footer.html'; ?>

</body>
</html>
