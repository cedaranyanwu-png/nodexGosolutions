<?php
/**
 * index.php
 *
 * This is the primary marketing landing page for nodexGosolutions.
 * It is fully styled with our gorgeous White & Blue enterprise theme.
 */

// Enable strict typing for safety
declare(strict_types=1);

// Require database config and helpers to load version parameters
require_once __DIR__ . '/../../php/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>nodexGosolutions | Bridging Earth & Space Tech</title>

    <!-- Load standard Fonts and Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@300;400;600;700&family=Orbitron:wght@600;700;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Load premium central White & Blue stylesheet -->
    <link href="<?= assetUrl('/css/style.css') ?>" rel="stylesheet">

    <!-- Official platform favicon references with cache versioning -->
    <link rel="icon" type="image/png" href="/main/assets/images/favicon.png?v=2">
</head>
<body>

    <!-- Scroll Progress line -->
    <div id="scroll-progress"></div>

    <!-- Modular Navigation Bar component -->
    <?php require_once __DIR__ . '/../modul/nav.html'; ?>

    <!-- HERO SECTION: Strategic Masterplan -->
    <section class="hero-gradient py-5 text-center d-flex align-items-center" style="min-height: 75vh;">
        <div class="container my-auto">
            <span class="badge custom-badge rounded-pill mb-3 px-3 py-2 text-primary" style="background-color: #ffffff;"><i class="fa-solid fa-satellite me-1"></i>Next-Gen Space & Software Ecosystem</span>
            <h1 class="display-3 fw-bold mb-3" style="font-family: 'Orbitron', sans-serif;">Empowering Businesses From <br><span style="color: #00d2ff;">Earth to the Stars</span></h1>
            <p class="lead col-lg-8 mx-auto text-light opacity-90 mb-4 fw-semibold">nodexGosolutions deploys high-speed modular web frameworks, maps satellite telemetry data, and powers modern creative entertainment platforms.</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="#services" class="btn btn-lg btn-light rounded-pill px-5 fw-bold text-primary"><i class="fa-solid fa-cubes me-1"></i>Explore Solutions</a>
                <a href="/login" class="btn btn-lg btn-outline-light rounded-pill px-5 fw-bold"><i class="fa-solid fa-user-lock me-1"></i>Sign In Hub</a>
            </div>
        </div>
    </section>

    <!-- METRICS SECTION: Enterprise Traction -->
    <section class="py-5" style="background-color: #ffffff;">
        <div class="container text-center">
            <div class="row g-4">
                <div class="col-md-3">
                    <h2 class="display-5 fw-bold text-primary" style="font-family: 'Orbitron', sans-serif;">350+</h2>
                    <p class="text-muted fw-bold small text-uppercase">Startups Empowered</p>
                </div>
                <div class="col-md-3">
                    <h2 class="display-5 fw-bold text-primary" style="font-family: 'Orbitron', sans-serif;">₦120M+</h2>
                    <p class="text-muted fw-bold small text-uppercase">Revenue Generated</p>
                </div>
                <div class="col-md-3">
                    <h2 class="display-5 fw-bold text-primary" style="font-family: 'Orbitron', sans-serif;">99.99%</h2>
                    <p class="text-muted fw-bold small text-uppercase">System Uptime</p>
                </div>
                <div class="col-md-3">
                    <h2 class="display-5 fw-bold text-primary" style="font-family: 'Orbitron', sans-serif;">18+</h2>
                    <p class="text-muted fw-bold small text-uppercase">Global Space Alliances</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CORE SERVICES SECTION -->
    <section id="services" class="py-5" style="background-color: #f8fafc;">
        <div class="container text-center">
            <div class="mb-5">
                <span class="badge custom-badge px-3 py-2 rounded-pill text-primary mb-2">Our Pillars</span>
                <h2 class="display-5 fw-bold text-dark" style="font-family: 'Orbitron', sans-serif;">The Three Expansion Verticals</h2>
                <p class="text-muted col-lg-6 mx-auto">How we bridge cloud technology, space research, and digital creative networks.</p>
            </div>
            <div class="row g-4 text-start">
                <!-- Vertical 1 -->
                <div class="col-md-4">
                    <div class="premium-card h-100">
                        <i class="fa-solid fa-code fs-1 text-primary mb-3"></i>
                        <h4 class="fw-bold" style="font-family: 'Orbitron', sans-serif;">1. Modular Softwares</h4>
                        <p class="text-muted mb-0">Engineered custom file-based JSON administrative setups so SMEs and developers launch without cloud overhead.</p>
                    </div>
                </div>
                <!-- Vertical 2 -->
                <div class="col-md-4">
                    <div class="premium-card h-100">
                        <i class="fa-solid fa-globe-africa fs-1 text-primary mb-3"></i>
                        <h4 class="fw-bold" style="font-family: 'Orbitron', sans-serif;">2. Space Data GIS</h4>
                        <p class="text-muted mb-0">Democratizing real-time earth observation, land mapping, and orbital telemetry for African agriculture and research.</p>
                    </div>
                </div>
                <!-- Vertical 3 -->
                <div class="col-md-4">
                    <div class="premium-card h-100">
                        <i class="fa-solid fa-clapperboard fs-1 text-primary mb-3"></i>
                        <h4 class="fw-bold" style="font-family: 'Orbitron', sans-serif;">3. Entertainment Tech</h4>
                        <p class="text-muted mb-0">Connecting creative studios and agencies with high-capacity digital platforms, ad networks, and fan management suites.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- DYNAMIC PRICING SECTION -->
    <section class="py-5" style="background-color: #ffffff;">
        <div class="container text-center">
            <div class="mb-5">
                <span class="badge custom-badge px-3 py-2 rounded-pill text-primary mb-2">SaaS Tiers</span>
                <h2 class="display-5 fw-bold text-dark" style="font-family: 'Orbitron', sans-serif;">Flexible Business Models</h2>
                <p class="text-muted">Low-cost scalable utility subscriptions designed for micro-enterprises and global giants.</p>
            </div>
            <div class="row g-4 align-items-stretch text-start">
                <div class="col-md-4">
                    <div class="premium-card h-100 d-flex flex-column">
                        <h4 class="fw-bold text-dark">Micro Starter</h4>
                        <h3 class="my-3 text-gradient">₦3,000 <small class="fs-6 text-muted">/mo</small></h3>
                        <p class="text-muted small">Ideal for micro-startups and basic domain utility tools.</p>
                        <ul class="list-unstyled d-flex flex-column gap-2 small text-muted flex-grow-1">
                            <li><i class="fa-solid fa-circle-check text-primary me-2"></i> Basic website hosting</li>
                            <li><i class="fa-solid fa-circle-check text-primary me-2"></i> 1 SSL certificate</li>
                            <li><i class="fa-solid fa-circle-check text-primary me-2"></i> Standard API access</li>
                        </ul>
                        <a href="/register" class="btn btn-premium w-100 mt-4 rounded-pill">Choose Starter</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="premium-card h-100 d-flex flex-column border-primary" style="box-shadow: 0 4px 30px rgba(0, 114, 255, 0.15);">
                        <span class="badge bg-primary text-white mb-2 fs-6 rounded-pill align-self-start px-3">Most Popular</span>
                        <h4 class="fw-bold text-dark">Business Pro</h4>
                        <h3 class="my-3 text-gradient">₦75,000 <small class="fs-6 text-muted">/mo</small></h3>
                        <p class="text-muted small">Full digital stack for established firms needing scale.</p>
                        <ul class="list-unstyled d-flex flex-column gap-2 small text-muted flex-grow-1">
                            <li><i class="fa-solid fa-circle-check text-primary me-2"></i> Full Web & Mobile App</li>
                            <li><i class="fa-solid fa-circle-check text-primary me-2"></i> Cloud Infrastructure</li>
                            <li><i class="fa-solid fa-circle-check text-primary me-2"></i> GIS Data Dashboard</li>
                        </ul>
                        <a href="/register" class="btn btn-premium w-100 mt-4 rounded-pill">Start Pro</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="premium-card h-100 d-flex flex-column">
                        <h4 class="fw-bold text-dark">Enterprise Space</h4>
                        <h3 class="my-3 text-gradient">₦100,000+ <small class="fs-6 text-muted">/mo</small></h3>
                        <p class="text-muted small">Custom satellite data & enterprise infrastructure.</p>
                        <ul class="list-unstyled d-flex flex-column gap-2 small text-muted flex-grow-1">
                            <li><i class="fa-solid fa-circle-check text-primary me-2"></i> Satellite Sensing API</li>
                            <li><i class="fa-solid fa-circle-check text-primary me-2"></i> Dedicated Engineers</li>
                            <li><i class="fa-solid fa-circle-check text-primary me-2"></i> Custom SLA & Support</li>
                        </ul>
                        <a href="/register" class="btn btn-premium w-100 mt-4 rounded-pill">Contact Sales</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modular Footer component -->
    <?php require_once __DIR__ . '/../modul/footer.html'; ?>

    <!-- JS Libraries -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
