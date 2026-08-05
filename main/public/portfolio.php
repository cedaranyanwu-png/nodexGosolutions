<?php
/**
 * Dynamic CMS Page Integration Override
 * Queries the site_cms database to determine if there is a custom code override for slug 'portfolio'.
 */
require_once __DIR__ . "/../../php/db.php";
$cmsDb = new Database(__DIR__ . "/../../databases", "site_cms");
$cmsPage = $cmsDb->selectOne("pages", ["slug" => "portfolio"]);
if ($cmsPage !== null && !empty($cmsPage["body_code"])) {
    eval("?>" . $cmsPage["body_code"]);
    die();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Executive Portfolio & Biography | Cedar Anyanwu</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- AOS Scroll Animation CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="<?= assetUrl('/css/portfolio.css') ?>" rel="stylesheet">

    <style>

        }
    </style>
</head>
<body>

    <!-- Scroll Progress Bar -->
    <div id="scroll-progress"></div>

    <!-- 3D Earth & ISS WebGL Background Canvas -->
    <div id="canvas-container"></div>

    <!-- NAVBAR Header -->
    <?php include __DIR__ . "/../modul/nav.html"?>

    <!-- MAIN PORTFOLIO & BIOGRAPHY CONTENT -->
    <main class="py-4">
        <div class="container" id="portfolio-root">

            <!-- SECTION 1: HERO / BIOGRAPHY HEADER -->
            <section class="hero-section text-center" data-aos="fade-up">
                <!-- Profile Image Placeholder -->
                <img src="assets/images/cedar-profile.jpg" alt="Cedar Anyanwu" class="profile-avatar" onerror="this.src='https://via.placeholder.com/150/00d2ff/ffffff?text=Cedar+A.'">
                <br>
                <span class="badge bg-primary text-white px-3 py-2 rounded-pill text-uppercase fw-bold fs-6 mb-2">
                    Executive Biography & Founder Portfolio
                </span>
                <h1 class="display-3 fw-bold text-white mb-2">Cedar Anyanwu</h1>
                <p class="fs-4 gradient-earth fw-bold mb-3">Founder, Chief Executive Officer & Full-Stack Platform Architect</p>
                <p class="text-muted-custom col-lg-8 mx-auto fs-5 mb-0">
                    Proudly Nigerian, originating from Imo State and operating out of the federal capital city of Abuja. Passionate software engineer dedicated to building high-performance, multi-tenant digital systems, autonomous automation engines, and scalable web solutions.
                </p>
            </section>

            <hr class="border-secondary opacity-25 my-5">

            <!-- SECTION 2: PERSONAL IDENTITY & ORIGIN -->
            <section class="mb-5" data-aos="fade-up" data-aos-delay="100">
                <div class="glass-card">
                    <div class="row align-items-center">
                        <div class="col-lg-4 text-center mb-4 mb-lg-0">
                            <img src="assets/images/abuja-imo-nigeria.jpg" alt="Location & Identity" class="portfolio-img" onerror="this.src='https://via.placeholder.com/400x250/151728/00d2ff?text=Abuja+%26+Imo+State'">
                        </div>
                        <div class="col-lg-8">
                            <h2 class="text-white mb-3"><i class="fa-solid fa-flag me-2 text-warning"></i> Nigerian Roots & Vision</h2>
                            <p class="text-muted-custom fs-5 mb-2">
                                Born on <strong>November 26, 2000</strong>, Cedar Anyanwu hails natively from <strong>Imo State, Nigeria</strong>, and resides in <strong>Abuja</strong>.
                            </p>
                            <p class="text-muted-custom mb-0">
                                Combining West African tech innovation with global architectural standards, Cedar leads software ventures designed to bridge raw technical efficiency with enterprise monetization and space telemetry visualization.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- SECTION 3: EARLY INSPIRATION & PROGRAMMING PASSION -->
            <section class="mb-5" data-aos="fade-up" data-aos-delay="150">
                <div class="glass-card">
                    <div class="row align-items-center">
                        <div class="col-lg-8 mb-4 mb-lg-0">
                            <h2 class="text-white mb-3"><i class="fa-solid fa-code me-2 text-info"></i> The Passion for Software Engineering</h2>
                            <p class="text-muted-custom fs-5 mb-0">
                                Driven by a deep curiosity for how digital platforms function behind the scenes, Cedar began programming with a core mission: to build projects that solve real problems and deliver smooth user experiences. From early algorithmic challenges to designing pure PHP/Python modular frameworks, software creation remains his core life passion.
                            </p>
                        </div>
                        <div class="col-lg-4">
                            <img src="assets/images/coding-workspace.jpg" alt="Software Workspace" class="portfolio-img" onerror="this.src='https://via.placeholder.com/400x250/0f172a/7928ca?text=Engineering+Lab'">
                        </div>
                    </div>
                </div>
            </section>

            <!-- SECTION 4: FIRST MILESTONE PROJECT - GAMEITZ -->
            <section class="mb-5" data-aos="fade-up" data-aos-delay="200">
                <div class="glass-card">
                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                        <h2 class="text-white mb-0"><i class="fa-solid fa-gamepad me-2 text-success"></i> Project Genesis: Gameitz</h2>
                        <span class="badge bg-success px-3 py-2 rounded-pill fw-bold">First Milestone Project</span>
                    </div>
                    <div class="row align-items-center">
                        <div class="col-lg-7 mb-3 mb-lg-0">
                            <p class="text-muted-custom fs-5 mb-2">
                                <strong>Gameitz</strong> was Cedar's inaugural software project—a pioneering school initiative built to test interactive logic, application flows, and software usability under real conditions.
                            </p>
                            <p class="text-muted-custom mb-0">
                                Serving as the foundational proving ground, Gameitz sparked Cedar's journey into full-stack development, database architecture, and performance optimization.
                            </p>
                        </div>
                        <div class="col-lg-5">
                            <img src="assets/images/gameitz-preview.jpg" alt="Gameitz Project Screenshot" class="portfolio-img" onerror="this.src='https://via.placeholder.com/400x250/111827/00ff87?text=Gameitz+Project+UI'">
                        </div>
                    </div>
                </div>
            </section>

            <!-- SECTION 5: EVOLUTIONARY STEP - QUICKCHAT -->
            <section class="mb-5" data-aos="fade-up" data-aos-delay="250">
                <div class="glass-card">
                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                        <h2 class="text-white mb-0"><i class="fa-solid fa-comments me-2 text-warning"></i> Next Generation: QuickChat</h2>
                        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold">Social Media Platform</span>
                    </div>
                    <div class="row align-items-center">
                        <div class="col-lg-5 mb-3 mb-lg-0">
                            <img src="assets/images/quickchat-preview.jpg" alt="QuickChat Social Platform" class="portfolio-img" onerror="this.src='https://via.placeholder.com/400x250/1e293b/ffb703?text=QuickChat+Platform'">
                        </div>
                        <div class="col-lg-7">
                            <p class="text-muted-custom fs-5 mb-2">
                                Building on early fundamentals, Cedar conceptualized and engineered <strong>QuickChat</strong>—a modern social media platform designed for seamless messaging and user engagement.
                            </p>
                            <p class="text-muted-custom mb-0">
                                QuickChat marked a major leap forward into real-time networking, state management, UI design, and scalable client-server interaction patterns.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- SECTION 6: FLAGSHIP ENTERPRISE PLATFORM - NODEXPLATORM -->
            <section class="mb-5" data-aos="fade-up" data-aos-delay="300">
                <div class="glass-card">
                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                        <h2 class="text-white mb-0"><i class="fa-solid fa-network-wired me-2 text-primary"></i> Flagship Platform: nodexplatform.com.ng</h2>
                        <span class="badge bg-primary px-3 py-2 rounded-pill fw-bold">Live Enterprise Platform</span>
                    </div>
                    <div class="row align-items-center">
                        <div class="col-lg-7 mb-3 mb-lg-0">
                            <p class="text-muted-custom fs-5 mb-2">
                                Hosted and active since early 2026, <strong>nodexplatform.com.ng</strong> represents Cedar's primary multi-tenant web platform engine.
                            </p>
                            <p class="text-muted-custom mb-0">
                                Utilizing environment-aware single-entry routing and lightweight file-based configuration backends, the platform operates with minimal database dependency to maximize speed, security, and hosting efficiency.
                            </p>
                        </div>
                        <div class="col-lg-5">
                            <img src="assets/images/nodex-platform-preview.jpg" alt="nodexplatform.com.ng Architecture" class="portfolio-img" onerror="this.src='https://via.placeholder.com/400x250/0f172a/00d2ff?text=nodexplatform.com.ng'">
                        </div>
                    </div>
                </div>
            </section>

            <!-- SECTION 7: TECHNICAL ARCHITECTURE & STACK -->
            <section class="mb-5" data-aos="fade-up" data-aos-delay="350">
                <div class="glass-card">
                    <h2 class="text-white mb-4"><i class="fa-solid fa-layer-group me-2 text-info"></i> Architectural Expertise & Technical Stack</h2>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="p-3 border border-secondary rounded-3 bg-dark h-100">
                                <h5 class="text-warning"><i class="fa-brands fa-php me-2"></i> Backend Engineering</h5>
                                <p class="text-muted-custom small mb-0">Pure PHP custom routers, Python scripts, file-based JSON CRUD setups, and lightweight RESTful APIs.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border border-secondary rounded-3 bg-dark h-100">
                                <h5 class="text-info"><i class="fa-brands fa-js me-2"></i> Frontend & WebGL</h5>
                                <p class="text-muted-custom small mb-0">Bootstrap 5, Three.js 3D rendering engines, AOS animation pipelines, and responsive mobile-first layouts.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border border-secondary rounded-3 bg-dark h-100">
                                <h5 class="text-success"><i class="fa-solid fa-robot me-2"></i> Automation & Agents</h5>
                                <p class="text-muted-custom small mb-0">Containerized browser automation, humanized traffic curves, and local LLM agent execution rules.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- SECTION 8: STEALTH AUTOMATION & TRAFFIC SIMULATION -->
            <section class="mb-5" data-aos="fade-up" data-aos-delay="400">
                <div class="glass-card">
                    <div class="row align-items-center">
                        <div class="col-lg-5 mb-3 mb-lg-0">
                            <img src="assets/images/automation-agents.jpg" alt="Web Automation Engine" class="portfolio-img" onerror="this.src='https://via.placeholder.com/400x250/05060f/a855f7?text=Stealth+Browser+Agents'">
                        </div>
                        <div class="col-lg-7">
                            <h2 class="text-white mb-3"><i class="fa-solid fa-user-gear me-2 text-secondary"></i> Autonomous Web Agents & Traffic Simulation</h2>
                            <p class="text-muted-custom fs-5 mb-0">
                                Cedar has authored advanced browser automation engines featuring permissive execution parameters, natural typing variances, Gaussian delay curves, and multi-threaded agent runners aimed at ad verification, traffic testing, and system auditing.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- SECTION 9: DIGITAL ADVERTISING & MONETIZATION -->
            <section class="mb-5" data-aos="fade-up" data-aos-delay="450">
                <div class="glass-card">
                    <div class="row align-items-center">
                        <div class="col-lg-8 mb-3 mb-lg-0">
                            <h2 class="text-white mb-3"><i class="fa-solid fa-chart-line me-2 text-success"></i> Digital Revenue & eCPM Optimization</h2>
                            <p class="text-muted-custom fs-5 mb-0">
                                Expert in digital publisher networks, smart link embeddings, impression delivery validation, and programmatic ad optimization. Cedar’s platform setups focus on scaling publisher traffic quality and maximizing long-term yields.
                            </p>
                        </div>
                        <div class="col-lg-4">
                            <img src="assets/images/monetization-analytics.jpg" alt="eCPM Optimization" class="portfolio-img" onerror="this.src='https://via.placeholder.com/400x250/111827/00ff87?text=Ad+Monetization+Analytics'">
                        </div>
                    </div>
                </div>
            </section>

            <!-- SECTION 10: FRONTIER SCI-FI & SPACE TECH VISION -->
            <section class="mb-5" data-aos="fade-up" data-aos-delay="500">
                <div class="glass-card">
                    <div class="row align-items-center">
                        <div class="col-lg-4 mb-3 mb-lg-0">
                            <img src="assets/images/space-telemetry.jpg" alt="Space Telemetry & Frontier Tech" class="portfolio-img" onerror="this.src='https://via.placeholder.com/400x250/0f172a/1d70b8?text=Earth+%26+ISS+Telemetry'">
                        </div>
                        <div class="col-lg-8">
                            <h2 class="text-white mb-3"><i class="fa-solid fa-user-astronaut me-2 text-warning"></i> Frontier Tech & Space Infrastructure</h2>
                            <p class="text-muted-custom fs-5 mb-0">
                                Beyond software, Cedar explores frontier technological concepts including orbital space telemetry, satellite data visualization, nanotech engineering, and planetary systems—symbolized by the interactive Earth and NASA ISS orbital model running in this environment.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- SECTION 11: EXECUTIVE GOALS & MULTI-REPO STRATEGY -->
            <section class="mb-5" data-aos="fade-up" data-aos-delay="550">
                <div class="glass-card">
                    <h2 class="text-white mb-3"><i class="fa-solid fa-sitemap me-2 text-primary"></i> Multi-Repo Infrastructure & Operational Strategy</h2>
                    <p class="text-muted-custom fs-5 mb-0">
                        Managing an overarching enterprise blueprint, Cedar utilizes structured repository separation for operating systems, mobile apps, and core web assets—guided by automated agent scripts for continuous deployment and codebase maintenance.
                    </p>
                </div>
            </section>

            <!-- SECTION 12: CONTACT & GALLERY CONTAINER -->
            <section class="mb-5" data-aos="fade-up" data-aos-delay="600">
                <div class="glass-card p-4 text-center">
                    <h2 class="text-white mb-3">Project Gallery & Dynamic Media</h2>
                    <p class="text-muted-custom col-lg-8 mx-auto fs-5 mb-4">
                        Additional project screenshots, platform screenshots, and media assets fetched dynamically from <code>portfolio.php</code>.
                    </p>

                    <!-- Dynamic Image Grid -->
                    <div class="row g-3 mb-4" id="dynamic-gallery-grid">
                        <div class="col-md-4">
                            <img src="assets/images/gallery-1.jpg" alt="Project Media 1" class="portfolio-img" onerror="this.src='https://via.placeholder.com/400x250/1e293b/00d2ff?text=Platform+Architecture'">
                        </div>
                        <div class="col-md-4">
                            <img src="assets/images/gallery-2.jpg" alt="Project Media 2" class="portfolio-img" onerror="this.src='https://via.placeholder.com/400x250/1e293b/7928ca?text=Dashboard+Analytics'">
                        </div>
                        <div class="col-md-4">
                            <img src="assets/images/gallery-3.jpg" alt="Project Media 3" class="portfolio-img" onerror="this.src='https://via.placeholder.com/400x250/1e293b/ffb703?text=Automation+Console'">
                        </div>
                    </div>

                    <a href="mailto:info@nodexgosolutions.com?subject=Executive%20Portfolio%20Inquiry" class="btn btn-lg btn-warning rounded-pill px-5 fw-bold text-dark">
                        Contact Cedar Anyanwu
                    </a>
                </div>
            </section>

        </div>
    </main>

    <!-- FOOTER -->
    <?php include __DIR__ . "/../modul/footer.html"?>

    <!-- JS Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="<?= assetUrl('/js/portfolio.js') ?>"></script>


    <!-- Portfolio Dynamic Fetch & 3D Script -->
    <script>

    </script>
</body>
</html>
