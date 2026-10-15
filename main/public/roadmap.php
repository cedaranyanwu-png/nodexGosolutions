<?php
/**
 * roadmap.php
 *
 * Renders corporate multi-yearroadmap timelines.
 * Styled with premium Tailwind CSS and modular elements.
 */

declare(strict_types=1);
require_once __DIR__ . '/../../php/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roadmap | nodexGosolutions</title>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@300;400;600;700&family=Orbitron:wght@600;700;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-700 min-h-screen flex flex-col justify-between">

    <?php require_once __DIR__ . '/../modul/nav.html'; ?>

    <main class="container mx-auto max-w-4xl my-16 px-4">
        <div class="text-center mb-12">
            <h1 class="text-3xl font-extrabold text-slate-900 mb-2" style="font-family: 'Orbitron', sans-serif;">Corporate Roadmap</h1>
            <p class="text-slate-500">Our structured milestones scaling modular SaaS platforms, media ad yield networks, and agritech telemetry downlinks.</p>
        </div>

        <div class="space-y-8" id="roadmap-timeline">
            <!-- Phase 1 -->
            <div class="bg-white border border-slate-100 rounded-xl p-6 shadow-sm">
                <div class="flex justify-between items-center flex-wrap gap-2 mb-3">
                    <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-1.5 rounded-full">Phase 1: Foundation Engine</span>
                    <span class="text-blue-600 font-bold text-xs uppercase tracking-wider">Q1-Q2 2026</span>
                </div>
                <h4 class="text-xl font-bold text-slate-900 mb-2" style="font-family: 'Orbitron', sans-serif;">Modular Architecture Rollout</h4>
                <p class="text-slate-600 text-sm leading-relaxed">Full deployment of the native multi-tenant PHP router engine with zero hardcoded MySQL queries, replacing standard backends with robust, local JSON-based databases.</p>
            </div>
            <!-- Phase 2 -->
            <div class="bg-white border border-slate-100 rounded-xl p-6 shadow-sm">
                <div class="flex justify-between items-center flex-wrap gap-2 mb-3">
                    <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-1.5 rounded-full">Phase 2: Platform Scale</span>
                    <span class="text-blue-600 font-bold text-xs uppercase tracking-wider">Q3-Q4 2026</span>
                </div>
                <h4 class="text-xl font-bold text-slate-900 mb-2" style="font-family: 'Orbitron', sans-serif;">Media & Ad Yield Automation</h4>
                <p class="text-slate-600 text-sm leading-relaxed">Deployment of browser automation agents and high-eCPM yield tracking tools, providing creators and media studios with maximum ad revenue optimization.</p>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/../modul/footer.html'; ?>

</body>
</html>
