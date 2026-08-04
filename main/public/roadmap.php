<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Strategic Roadmap | nodexGosolutions</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- AOS Scroll Animation CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="../css/roadmap.css" rel="stylesheet">

    <style>

    </style>
</head>
<body>

    <!-- Scroll Progress Bar -->
    <div id="scroll-progress"></div>

    <!-- 3D Saturn WebGL Background Canvas -->
    <div id="canvas-container"></div>

    <!-- NAVBAR Header -->
    <?php include __DIR__ . "/../modul/nav.html"?>
    <!-- MAIN ROADMAP CONTENT -->
    <main class="py-4">
        <div class="container">

            <!-- Header Section -->
            <div class="text-center mb-5" data-aos="fade-up">
                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill text-uppercase fw-bold fs-6 mb-2">
                    Strategic Growth Trajectory
                </span>
                <h1 class="display-4 fw-bold text-white mb-2">Corporate Multi-Year Roadmap</h1>
                <p class="text-muted-custom col-lg-8 mx-auto fs-5 mb-0">
                    A structured technical and operational roadmap outlining the scaling of our zero-database SaaS architecture, media monetization pipelines, space telemetry integration, and enterprise partnerships.
                </p>
            </div>

            <!-- Dynamic / Fallback Timeline Container -->
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="timeline-container" id="roadmap-timeline">

                        <!-- Phase 1 -->
                        <div class="timeline-node" data-aos="fade-up" data-aos-delay="100">
                            <div class="glass-card">
                                <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
                                    <span class="badge bg-info text-dark fw-bold px-3 py-2 rounded-pill mb-1">PHASE 1: FOUNDATION & ECOSYSTEM CORE</span>
                                    <span class="text-warning fw-bold">Q1 - Q2 2026</span>
                                </div>
                                <h3 class="text-white mb-3">Modular Engine Architecture & Initial Traction</h3>
                                <ul class="text-muted-custom mb-0 d-flex flex-column gap-2">
                                    <li><i class="fa-solid fa-angle-right text-warning me-2"></i> Deployment of live modular multi-tenant PHP single-entry router architecture (nodexplatform.com.ng).</li>
                                    <li><i class="fa-solid fa-angle-right text-warning me-2"></i> Implementation of file-based JSON configuration backends for high-speed CRUD operations and ultra-low server cost.</li>
                                    <li><i class="fa-solid fa-angle-right text-warning me-2"></i> Integration of high-eCPM publisher ad networks and smart-link monetization tools.</li>
                                    <li><i class="fa-solid fa-angle-right text-warning me-2"></i> Corporate structuring and financial compliance for small business funding channels.</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Phase 2 -->
                        <div class="timeline-node" data-aos="fade-up" data-aos-delay="200">
                            <div class="glass-card">
                                <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
                                    <span class="badge bg-warning text-dark fw-bold px-3 py-2 rounded-pill mb-1">PHASE 2: AUTOMATION & ENTERPRISE EXPANSION</span>
                                    <span class="text-warning fw-bold">Q3 - Q4 2026</span>
                                </div>
                                <h3 class="text-white mb-3">Stealth Web Automation & Media Outreach</h3>
                                <ul class="text-muted-custom mb-0 d-flex flex-column gap-2">
                                    <li><i class="fa-solid fa-angle-right text-warning me-2"></i> Rollout of containerized stealth browser agents and humanized traffic distribution curves for ad verification.</li>
                                    <li><i class="fa-solid fa-angle-right text-warning me-2"></i> Strategic industry pitch campaign targeting entertainment management and media distribution networks (e.g., Atlas Artists).</li>
                                    <li><i class="fa-solid fa-angle-right text-warning me-2"></i> Multi-repository operational framework linking local LLM agents directly to repository workflows.</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Phase 3 -->
                        <div class="timeline-node" data-aos="fade-up" data-aos-delay="300">
                            <div class="glass-card">
                                <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
                                    <span class="badge bg-success text-dark fw-bold px-3 py-2 rounded-pill mb-1">PHASE 3: ADVANCED SCALING & TELEMETRY</span>
                                    <span class="text-warning fw-bold">2027 & BEYOND</span>
                                </div>
                                <h3 class="text-white mb-3">Cross-Industry Alliances & Frontier Tech</h3>
                                <ul class="text-muted-custom mb-0 d-flex flex-column gap-2">
                                    <li><i class="fa-solid fa-angle-right text-warning me-2"></i> Integration of satellite ground station telemetry feeds into public-private research dashboards.</li>
                                    <li><i class="fa-solid fa-angle-right text-warning me-2"></i> Expansion of white-label SaaS multi-tenant licensing across international enterprise markets.</li>
                                    <li><i class="fa-solid fa-angle-right text-warning me-2"></i> Establishing institutional venture partnerships and expansion into next-generation cloud automation.</li>
                                </ul>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Strategic Call to Action -->
            <div class="row justify-content-center mt-4 mb-5" data-aos="fade-up" data-aos-delay="400">
                <div class="col-lg-10">
                    <div class="glass-card p-4 text-center">
                        <h3 class="text-white mb-2">Co-Develop the Future With Us</h3>
                        <p class="text-muted-custom col-md-9 mx-auto mb-3">
                            We are actively aligning with forward-thinking investors, enterprise developers, and strategic partners to accelerate execution on our roadmap milestones.
                        </p>
                        <a href="mailto:info@nodexgosolutions.com?subject=Strategic%20Roadmap%20Inquiry" class="btn btn-warning rounded-pill px-4 py-2 fw-bold me-2 text-dark">
                            Request Strategic Briefing
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- FOOTER -->
    <?php include __DIR__ . "/../modul/footer.html"?>

    <!-- JS Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="../js/roadmap.js"></script>


    <!-- Roadmap Dynamic Fetch Script -->
    <script>
          </script>
</body>
</html>
