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
    <?= renderSeoHead([
        'title' => 'About Corporate | nodexGosolutions',
        'description' => 'Providing pure, lightweight architectures designed for maximum speed and simplicity, bridging cloud with orbital research.',
        'og_title' => 'About Corporate | nodexGosolutions',
        'og_description' => 'Providing pure, lightweight architectures designed for maximum speed and simplicity, bridging cloud with orbital research.',
        'images' => [
            '/main/assets/images/about-banner-1.jpg',
            '/main/assets/images/about-banner-2.jpg',
            '/main/assets/images/about-team-1.jpg',
            '/main/assets/images/about-team-2.jpg',
            '/main/assets/images/about-team-3.jpg',
            '/main/assets/images/cedar-anyanwu.jpg'
        ],
        'og_type' => 'website',
        'schema_type' => 'Organization',
        'breadcrumbs' => [
            ['name' => 'About', 'url' => '/about']
        ]
    ]) ?>

    <!-- Google Font & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@300;400;600;700&family=Orbitron:wght@600;700;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-700 min-h-screen flex flex-col justify-between">

    <!-- Modular Navigation component -->
    <?php require_once __DIR__ . '/../modul/nav.html'; ?>

    <!-- HERO PANEL WITH DYNAMIC SEO BACKGROUND BANNERS -->
    <section class="relative bg-gradient-to-r from-blue-900/90 to-blue-800/95 py-24 text-center text-white overflow-hidden">
        <!-- Visual Background Banners overlaying for elegant SEO layout depth -->
        <div class="absolute inset-0 z-0 opacity-10 flex justify-between gap-4 pointer-events-none">
            <img
                src="<?= htmlspecialchars((string)assetUrl('/main/assets/images/about-banner-1.jpg')) ?>"
                alt="nodexGosolutions corporate high-tech environment banner illustration"
                class="w-1/2 h-full object-cover"
            />
            <img
                src="<?= htmlspecialchars((string)assetUrl('/main/assets/images/about-banner-2.jpg')) ?>"
                alt="nodexGosolutions orbital data telemetry research center representation"
                class="w-1/2 h-full object-cover"
            />
        </div>

        <div class="container mx-auto px-4 relative z-10">
            <span class="bg-blue-400/30 text-white text-xs font-semibold px-3 py-1.5 rounded-full mb-3 inline-block uppercase tracking-wider">Our Vision</span>
            <h1 class="text-4xl md:text-5xl font-extrabold mb-4" style="font-family: 'Orbitron', sans-serif;">Engineering Africa's Tech Future</h1>
            <p class="text-blue-100 max-w-2xl mx-auto text-base leading-relaxed">Providing pure, lightweight architectures designed for maximum speed and simplicity, bridging cloud with orbital research.</p>
        </div>
    </section>

    <!-- MISSION & VISION SECTIONS -->
    <main class="container mx-auto max-w-5xl my-16 px-4">
        <!-- Header Showcase featuring corporate banners side-by-side -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-16">
            <div class="rounded-2xl overflow-hidden shadow-sm border border-slate-100 bg-white">
                <!-- Using object-contain and object-top to ensure the main faces/essential areas of banners are completely visible -->
                <img
                    src="<?= htmlspecialchars((string)assetUrl('/main/assets/images/about-banner-1.jpg')) ?>"
                    alt="nodexGosolutions team collaborating on next-generation tech solutions"
                    class="w-full h-64 object-contain object-top bg-slate-100 hover:scale-105 transition-transform duration-300 cursor-pointer"
                    onclick="openImageModal(this.src, this.alt)"
                />
                <div class="p-4">
                    <span class="text-xs text-blue-600 font-bold uppercase tracking-wide">Dynamic Engineering</span>
                    <p class="text-slate-600 text-xs mt-1">Our dedicated teams streamline development using customized modular environments.</p>
                </div>
            </div>
            <div class="rounded-2xl overflow-hidden shadow-sm border border-slate-100 bg-white">
                <!-- Using object-contain and object-top to ensure the main faces/essential areas of banners are completely visible -->
                <img
                    src="<?= htmlspecialchars((string)assetUrl('/main/assets/images/about-banner-2.jpg')) ?>"
                    alt="Space telemetry data visualization and satellite cloud linkage center"
                    class="w-full h-64 object-contain object-top bg-slate-100 hover:scale-105 transition-transform duration-300 cursor-pointer"
                    onclick="openImageModal(this.src, this.alt)"
                />
                <div class="p-4">
                    <span class="text-xs text-blue-600 font-bold uppercase tracking-wide">Orbital Telemetry</span>
                    <p class="text-slate-600 text-xs mt-1">We downlink real-time satellite maps and orbital records for spatial research.</p>
                </div>
            </div>
        </div>

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
                    <!-- Dynamic CEO profile photo replaces initials for premium SEO layout structure -->
                    <!-- object-contain and object-top applied to display Cedar's face perfectly -->
                    <?= renderEntityImage(
                        'Cedar Anyanwu',
                        '/main/assets/images/cedar-anyanwu.jpg',
                        'CEO of Nodexplatform',
                        'rounded-full w-10 h-10 object-contain object-top bg-slate-100 border border-blue-200 cursor-pointer',
                        'onclick="openImageModal(this.src, this.alt)"'
                    ) ?>
                    <div class="ml-3">
                        <p class="font-bold text-slate-900 text-sm mb-0">Cedar Anyanwu</p>
                        <p class="text-xs text-slate-400 mb-0">CEO & Systems Architect</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- STRATEGIC CORE TEAM/OFFICE SEGMENTS FOR SEO -->
        <div class="mb-16">
            <h2 class="text-2xl font-bold text-slate-900 mb-2 text-center" style="font-family: 'Orbitron', sans-serif;">Meet Our Driving Forces</h2>
            <p class="text-slate-500 text-sm text-center mb-8 max-w-md mx-auto">The engineering experts behind our modular system architectures, cloud backends, and geographic mapping APIs.</p>

            <div class="grid md:grid-cols-3 gap-6">
                <!-- Team Member 1 -->
                <div class="bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-sm">
                    <!-- Using object-contain and object-top to ensure the faces are fully visible -->
                    <img
                        src="<?= htmlspecialchars((string)assetUrl('/main/assets/images/about-team-1.jpg')) ?>"
                        alt="nodexGosolutions head of system operations engineering"
                        class="w-full h-48 object-contain object-top bg-slate-100 cursor-pointer hover:opacity-90 transition-opacity"
                        onclick="openImageModal(this.src, this.alt)"
                    />
                    <div class="p-5">
                        <h4 class="font-bold text-slate-900 mb-1">Software Operations</h4>
                        <p class="text-xs text-blue-600 font-semibold mb-3">Core Engine Deployment</p>
                        <p class="text-xs text-slate-500 leading-relaxed">Directs multi-tenant runtime load balancers, safeguarding platform uptime across regional instances.</p>
                    </div>
                </div>
                <!-- Team Member 2 -->
                <div class="bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-sm">
                    <!-- Using object-contain and object-top to ensure the faces are fully visible -->
                    <img
                        src="<?= htmlspecialchars((string)assetUrl('/main/assets/images/about-team-2.jpg')) ?>"
                        alt="nodexGosolutions satellite telemetry data analyst"
                        class="w-full h-48 object-contain object-top bg-slate-100 cursor-pointer hover:opacity-90 transition-opacity"
                        onclick="openImageModal(this.src, this.alt)"
                    />
                    <div class="p-5">
                        <h4 class="font-bold text-slate-900 mb-1">Space Data Analyst</h4>
                        <p class="text-xs text-blue-600 font-semibold mb-3">Orbital Processing</p>
                        <p class="text-xs text-slate-500 leading-relaxed">Integrates real-time satellite streams, mapping soil dynamics and environmental indexes.</p>
                    </div>
                </div>
                <!-- Team Member 3 -->
                <div class="bg-white border border-slate-100 rounded-2xl overflow-hidden shadow-sm">
                    <!-- Using object-contain and object-top to ensure the faces are fully visible -->
                    <img
                        src="<?= htmlspecialchars((string)assetUrl('/main/assets/images/about-team-3.jpg')) ?>"
                        alt="nodexGosolutions lead frontend experience architect"
                        class="w-full h-48 object-contain object-top bg-slate-100 cursor-pointer hover:opacity-90 transition-opacity"
                        onclick="openImageModal(this.src, this.alt)"
                    />
                    <div class="p-5">
                        <h4 class="font-bold text-slate-900 mb-1">UI Experience Architect</h4>
                        <p class="text-xs text-blue-600 font-semibold mb-3">Creative Engineering</p>
                        <p class="text-xs text-slate-500 leading-relaxed">Pioneers fluid visual layouts using clean Tailwind & Bootstrap interfaces, optimizing client workspace speed.</p>
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

    <!-- Interactive Reusable Image Modal System for SEO Images -->
    <div
        id="seoImageModal"
        class="fixed inset-0 z-[9999] hidden bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 transition-opacity duration-300 opacity-0"
        onclick="closeImageModalOnBackdrop(event)"
    >
        <div class="relative max-w-3xl w-full bg-white rounded-2xl overflow-hidden shadow-2xl border border-slate-100 transform scale-95 transition-transform duration-300" onclick="event.stopPropagation()">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <h3 id="seoModalTitle" class="text-base font-bold text-slate-900 truncate">Image View</h3>
                <button
                    type="button"
                    class="text-slate-400 hover:text-slate-600 focus:outline-none p-1"
                    onclick="closeImageModal()"
                >
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
            <!-- Modal Body (Image Container) -->
            <div class="p-6 flex justify-center bg-slate-50">
                <img
                    id="seoModalImage"
                    src=""
                    alt=""
                    class="max-h-[70vh] w-auto object-contain rounded-lg shadow-sm border border-slate-200"
                />
            </div>
        </div>
    </div>

    <!-- JavaScript to control the image modal behavior dynamically -->
    <script>
        /**
         * Opens the SEO image modal, populates the source/alt attributes, and plays smooth transition.
         * @param {string} src - The image source URL.
         * @param {string} alt - The descriptive SEO alt tag text.
         */
        function openImageModal(src, alt) {
            const modal = document.getElementById('seoImageModal');
            const modalImg = document.getElementById('seoModalImage');
            const modalTitle = document.getElementById('seoModalTitle');

            if (!modal || !modalImg || !modalTitle) return;

            // Set dynamic attributes
            modalImg.src = src;
            modalImg.alt = alt;
            modalTitle.textContent = alt || "Uncropped Full View";

            // Unhide modal element
            modal.classList.remove('hidden');

            // Allow layout render, then trigger CSS transitions
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modal.querySelector('.transform').classList.remove('scale-95');
                modal.querySelector('.transform').classList.add('scale-100');
            }, 20);

            // Close modal on Escape key down
            document.addEventListener('keydown', handleEscapeKey);
        }

        /**
         * Closes the interactive image modal with smooth fade-out CSS transitions.
         */
        function closeImageModal() {
            const modal = document.getElementById('seoImageModal');
            if (!modal) return;

            modal.classList.add('opacity-0');
            modal.querySelector('.transform').classList.remove('scale-100');
            modal.querySelector('.transform').classList.add('scale-95');

            // Hide from DOM after transition completes
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);

            // Clean up global keydown listener
            document.removeEventListener('keydown', handleEscapeKey);
        }

        /**
         * Helper to safely close modal when clicking on the transparent backdrop.
         */
        function closeImageModalOnBackdrop(event) {
            closeImageModal();
        }

        /**
         * Handles Escape key press to close modal.
         */
        function handleEscapeKey(event) {
            if (event.key === 'Escape') {
                closeImageModal();
            }
        }
    </script>

</body>
</html>
