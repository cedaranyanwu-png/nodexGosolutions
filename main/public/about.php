<?php
/**
 * about.php
 *
 * About corporate masterplan view.
 * Styled beautifully with Tailwind CSS and modular navigations.
 */

// Enable strict typing for safety
declare(strict_types=1);

// Require dynamic database configuration
require_once __DIR__ . '/../../php/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Corporate | nodexGosolutions</title>

    <!-- Google Font & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@300;400;600;700&family=Orbitron:wght@600;700;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-700 min-h-screen flex flex-col justify-between">

    <!-- Modular Navigation component -->
    <?php require_once __DIR__ . '/../modul/nav.html'; ?>

    <!-- HERO PANEL -->
    <section class="bg-gradient-to-r from-blue-600 to-blue-800 py-20 text-center text-white">
        <div class="container mx-auto px-4">
            <span class="bg-blue-400/30 text-white text-xs font-semibold px-3 py-1.5 rounded-full mb-3 inline-block uppercase tracking-wider">Our Vision</span>
            <h1 class="text-4xl md:text-5xl font-extrabold mb-4" style="font-family: 'Orbitron', sans-serif;">Engineering Africa's Tech Future</h1>
            <p class="text-blue-100 max-w-2xl mx-auto text-base leading-relaxed">Providing pure, lightweight architectures designed for maximum speed and simplicity, bridging cloud with orbital research.</p>
        </div>
    </section>

    <!-- MISSION & VISION SECTIONS -->
    <main class="container mx-auto max-w-5xl my-16 px-4">
        <div class="grid md:grid-cols-2 gap-8 items-center mb-16">
            <div>
                <h2 class="text-2xl font-bold text-slate-900 mb-4" style="font-family: 'Orbitron', sans-serif;"><i class="fa-solid fa-rocket text-blue-600 mr-2"></i> Our Strategic Mission</h2>
                <p class="text-slate-600 leading-relaxed mb-4 text-sm">nodexGosolutions aims to dismantle complex environment overhead, delivering lightweight, file-based SaaS platforms to emerging startups and enterprise leaders alike.</p>
                <p class="text-slate-600 leading-relaxed text-sm">By utilizing custom, file-locked JSON datastores, we eliminate expensive MySQL databases and administrative bottlenecks, providing modular applications that run with maximum uptime and speed.</p>
            </div>
            <div class="bg-white border border-slate-100 p-8 rounded-2xl shadow-sm">
                <h3 class="text-lg font-bold text-slate-900 mb-2">Corporate Mandate</h3>
                <p class="text-slate-500 italic text-sm mb-4">"We are not merely consuming tech; we are designing the modules that navigate cloud computation."</p>
                <div class="flex items-center">
                    <div class="rounded-full bg-blue-600 text-white w-10 h-10 flex items-center justify-center font-bold">C</div>
                    <div class="ml-3">
                        <p class="font-bold text-slate-900 text-sm mb-0">Cedar Anyanwu</p>
                        <p class="text-xs text-slate-400 mb-0">CEO & Systems Architect</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
            <div class="bg-white border border-slate-100 p-6 rounded-xl shadow-sm">
                <i class="fa-solid fa-shield-halved text-blue-600 text-2xl mb-3"></i>
                <h4 class="font-bold text-slate-900 mb-2">Security-First Isolation</h4>
                <p class="text-xs text-slate-500 leading-relaxed">Our platforms enforce complete tenant isolation and directory traversal guards, preventing unauthorized database cross-reads.</p>
            </div>
            <div class="bg-white border border-slate-100 p-6 rounded-xl shadow-sm">
                <i class="fa-solid fa-cloud-arrow-up text-blue-600 text-2xl mb-3"></i>
                <h4 class="font-bold text-slate-900 mb-2">Auto Cache-Busting</h4>
                <p class="text-xs text-slate-500 leading-relaxed">Dynamic script and stylesheet version parameters auto-bust browser caches instantly whenever resources are updated.</p>
            </div>
            <div class="bg-white border border-slate-100 p-6 rounded-xl shadow-sm">
                <i class="fa-solid fa-code text-blue-600 text-2xl mb-3"></i>
                <h4 class="font-bold text-slate-900 mb-2">Pure Code-First CMS</h4>
                <p class="text-xs text-slate-500 leading-relaxed">Admins hold absolute control to inject custom HTML, CSS, and dynamic PHP codes straight into endpoint routes.</p>
            </div>
        </div>
    </main>

    <!-- Modular Footer component -->
    <?php require_once __DIR__ . '/../modul/footer.html'; ?>

</body>
</html>
