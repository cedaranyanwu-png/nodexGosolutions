<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Platform Traction & Valuation | nodexGosolutions</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- AOS Scroll Animation CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
 <link href="../css/traction.css" rel="stylesheet">

    <style>

    </style>
</head>
<body>

    <div id="scroll-progress"></div>

    <!-- 3D Scene Host Container -->
    <div id="canvas-container">
        <svg class="bg-fallback-orbits" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 800">
            <circle cx="650" cy="300" r="180" fill="none" stroke="rgba(0,210,255,0.15)" stroke-width="1.5"/>
            <circle cx="650" cy="300" r="260" fill="none" stroke="rgba(255,255,255,0.08)" stroke-width="1" stroke-dasharray="6 6"/>
        </svg>
    </div>

    <!-- NAVBAR HEADER -->
    <?php include "../modul/nav.html"?>
    <!-- MAIN CONTENT -->
    <main>
        <div class="container">

            <!-- HERO SECTION -->
            <section class="hero-section text-center" data-aos="fade-down" data-aos-duration="800">
                <span class="badge bg-primary text-white px-2 py-1 rounded-pill text-uppercase fw-bold small mb-1">
                    Verified Operational Velocity
                </span>
                <h1 class="h2 fw-bold text-white mb-1">Platform Traction</h1>
                <p class="text-muted-custom col-lg-7 mx-auto mb-0">
                    Real-time operational benchmarks across multi-tenant deployments, traffic monetization, and infrastructure asset performance.
                </p>
            </section>

            <!-- LIVE STATS GRID -->
            <section class="mb-3" data-aos="fade-up" data-aos-duration="800">
                <div class="row g-2">
                    <div class="col-6 col-lg-3">
                        <div class="glass-card stat-card">
                            <div class="stat-number">$18.5K+</div>
                            <div class="text-muted-custom small mt-1 fw-semibold">Monthly MRR</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="glass-card stat-card">
                            <div class="stat-number">125K+</div>
                            <div class="text-muted-custom small mt-1 fw-semibold">Active Users (MAU)</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="glass-card stat-card">
                            <div class="stat-number">$140K+</div>
                            <div class="text-muted-custom small mt-1 fw-semibold">Total Payouts</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="glass-card stat-card">
                            <div class="stat-number">99.98%</div>
                            <div class="text-muted-custom small mt-1 fw-semibold">Core Uptime</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- VALUATION & TRUST CALCULATOR -->
            <section id="platform-valuation" class="mb-3" data-aos="fade-up" data-aos-duration="800" data-aos-delay="100">
                <div class="glass-card">
                    <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom border-secondary border-opacity-25">
                        <h5 class="text-white mb-0">
                            <i class="fa-solid fa-calculator text-info me-2"></i> Valuation & Trust Calculator
                        </h5>
                        <span class="badge bg-info text-dark fw-bold small">Real-Time Model</span>
                    </div>

                    <div class="row g-3">
                        <!-- LEFT COLUMN: CONTROLS -->
                        <div class="col-lg-6 border-end-lg border-secondary border-opacity-25 pe-lg-3">
                            <div class="mb-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="input-mrr" class="form-label text-white small fw-semibold mb-0">Monthly Revenue (MRR)</label>
                                    <span class="text-info fw-bold small" id="val-mrr-display">$18,500</span>
                                </div>
                                <input type="range" class="form-range" id="input-mrr" min="2000" max="100000" step="500" value="18500">
                            </div>

                            <div class="mb-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="input-mau" class="form-label text-white small fw-semibold mb-0">Monthly Active Users (MAU)</label>
                                    <span class="text-info fw-bold small" id="val-mau-display">125,000</span>
                                </div>
                                <input type="range" class="form-range" id="input-mau" min="10000" max="1000000" step="5000" value="125000">
                            </div>

                            <div class="mb-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="input-payouts" class="form-label text-white small fw-semibold mb-0">Historical Total Payouts</label>
                                    <span class="text-info fw-bold small" id="val-payouts-display">$140,000</span>
                                </div>
                                <input type="range" class="form-range" id="input-payouts" min="10000" max="2000000" step="10000" value="140000">
                            </div>

                            <div class="bg-dark bg-opacity-50 p-2 rounded border border-secondary border-opacity-25 mt-3">
                                <div class="d-flex justify-content-between text-muted-custom small mb-1">
                                    <span>ARR Multiple Baseline:</span>
                                    <strong class="text-white">5.0x</strong>
                                </div>
                                <div class="d-flex justify-content-between text-muted-custom small mb-1">
                                    <span>Active User Equity Value:</span>
                                    <strong class="text-white">$3.20 / MAU</strong>
                                </div>
                                <div class="d-flex justify-content-between text-muted-custom small">
                                    <span>System Asset Floor:</span>
                                    <strong class="text-white">$250,000</strong>
                                </div>
                            </div>
                        </div>

                        <!-- RIGHT COLUMN: DYNAMIC CALCULATED OUTPUTS -->
                        <div class="col-lg-6 ps-lg-3 d-flex flex-column justify-content-between">
                            <div>
                                <div class="calc-output-box mb-2">
                                    <span class="text-uppercase text-muted-custom small fw-bold d-block mb-1">Estimated Market Value</span>
                                    <div class="h3 fw-bold text-white gradient-earth mb-0" id="calc-valuation">$1,648,000</div>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <div class="calc-output-box">
                                            <span class="text-uppercase text-muted-custom small fw-bold d-block mb-1">Trust Score</span>
                                            <div class="h4 fw-bold text-info mb-0" id="calc-trust-score">94.8%</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="calc-output-box">
                                            <span class="text-uppercase text-muted-custom small fw-bold d-block mb-1">Yield Tier</span>
                                            <div class="mt-1">
                                                <span class="roi-badge roi-tier-prime" id="calc-roi-tier">Tier 1 Prime</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-2 border-top border-secondary border-opacity-25">
                                <div class="row g-2">
                                    <div class="col-sm-6">
                                        <a href="mailto:info@nodexgosolutions.com?subject=Investor%20Inquiry" class="btn btn-primary btn-sm rounded-pill w-100 fw-bold">
                                            <i class="fa-solid fa-handshake me-1"></i>Join as Partner
                                        </a>
                                    </div>
                                    <div class="col-sm-6">
                                        <a href="https://nodexplatform.com.ng" target="_blank" class="btn btn-outline-info btn-sm rounded-pill w-100 fw-bold text-white">
                                            <i class="fa-solid fa-rocket me-1"></i>Start Earning
                                        </a>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </section>

            <!-- CALL TO ACTION -->
            <section class="mb-3" data-aos="fade-up" data-aos-duration="800" data-aos-delay="150">
                <div class="glass-card p-3 text-center">
                    <h5 class="text-white mb-1">Accelerate Infrastructure Yield</h5>
                    <p class="text-muted-custom small mb-2">
                        Explore co-development opportunities, venture investment terms, or custom deployments.
                    </p>
                    <a href="mailto:info@nodexgosolutions.com?subject=Traction%20Inquiry" class="btn btn-sm btn-warning rounded-pill px-4 fw-bold text-dark">
                        Contact Cedar Anyanwu
                    </a>
                </div>
            </section>

        </div>
    </main>

    <!-- FOOTER -->
    <?php include "../modul/footer.html"?>
    <!-- JS Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
 <script src="../js/traction.js"></script>
    <!-- Safe Script Initialization Engine -->
    <script>
        // Scroll Progress Indicator
        window.onscroll = function() {
            let winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            let height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            if (height > 0) {
                let scrolled = (winScroll / height) * 100;
                document.getElementById("scroll-progress").style.width = scrolled + "%";
            }
        };

        // Scroll Animations (AOS)
        if (typeof AOS !== 'undefined') {
            AOS.init({ duration: 600, once: true });
        }

        // CALCULATOR ENGINE
        document.addEventListener("DOMContentLoaded", function() {
            const inputMrr = document.getElementById('input-mrr');
            const inputMau = document.getElementById('input-mau');
            const inputPayouts = document.getElementById('input-payouts');

            const displayMrr = document.getElementById('val-mrr-display');
            const displayMau = document.getElementById('val-mau-display');
            const displayPayouts = document.getElementById('val-payouts-display');

            const outputValuation = document.getElementById('calc-valuation');
            const outputTrustScore = document.getElementById('calc-trust-score');
            const outputRoiTier = document.getElementById('calc-roi-tier');

            function formatCurrency(num) {
                return '$' + Number(num).toLocaleString();
            }

            function updateCalculator() {
                if (!inputMrr || !inputMau || !inputPayouts) return;

                const mrr = parseFloat(inputMrr.value) || 0;
                const mau = parseFloat(inputMau.value) || 0;
                const payouts = parseFloat(inputPayouts.value) || 0;

                if (displayMrr) displayMrr.textContent = formatCurrency(mrr);
                if (displayMau) displayMau.textContent = Number(mau).toLocaleString();
                if (displayPayouts) displayPayouts.textContent = formatCurrency(payouts);

                const arr = mrr * 12;
                const valuation = (arr * 5.0) + (mau * 3.20) + 250000;
                if (outputValuation) outputValuation.textContent = formatCurrency(Math.round(valuation));

                let payoutRatioFactor = Math.min((payouts / (arr || 1)) * 40, 50);
                let scaleFactor = Math.min((mau / 1000000) * 30, 30);
                let trustScore = Math.min(20 + payoutRatioFactor + scaleFactor, 99.4).toFixed(1);

                if (outputTrustScore) outputTrustScore.textContent = trustScore + '%';

                if (outputRoiTier) {
                    if (valuation >= 2500000 || trustScore >= 90) {
                        outputRoiTier.textContent = 'Tier 1 Prime';
                        outputRoiTier.className = 'roi-badge roi-tier-prime';
                    } else {
                        outputRoiTier.textContent = 'Tier 2 Growth';
                        outputRoiTier.className = 'roi-badge roi-tier-growth';
                    }
                }
            }

            if (inputMrr && inputMau && inputPayouts) {
                inputMrr.addEventListener('input', updateCalculator);
                inputMau.addEventListener('input', updateCalculator);
                inputPayouts.addEventListener('input', updateCalculator);
                updateCalculator();
            }
        });

        // FULL 3D ANIMATION ENGINE (Globe + Satellites + Particle Field)
        window.addEventListener('load', function() {
            try {
                if (typeof THREE === 'undefined') return;

                const container = document.getElementById('canvas-container');
                if (!container) return;

                const scene = new THREE.Scene();
                const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
                camera.position.set(0, 0, 24);

                const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
                renderer.setSize(window.innerWidth, window.innerHeight);
                renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
                container.appendChild(renderer.domElement);

                // Lighting
                scene.add(new THREE.AmbientLight(0xffffff, 0.7));
                const sunLight = new THREE.DirectionalLight(0x00d2ff, 2.0);
                sunLight.position.set(30, 20, 20);
                scene.add(sunLight);

                // 3D Planet Globe
                const earthGeo = new THREE.SphereGeometry(6.0, 32, 32);
                const earthMat = new THREE.MeshStandardMaterial({
                    color: 0x1d70b8,
                    wireframe: false,
                    roughness: 0.5,
                    metalness: 0.2
                });
                const earth = new THREE.Mesh(earthGeo, earthMat);
                earth.position.set(8, -1, -2);
                scene.add(earth);

                // Planet Wireframe Outer Layer
                const wireGeo = new THREE.SphereGeometry(6.25, 20, 20);
                const wireMat = new THREE.MeshBasicMaterial({
                    color: 0x00d2ff,
                    wireframe: true,
                    transparent: true,
                    opacity: 0.15
                });
                const wireframe = new THREE.Mesh(wireGeo, wireMat);
                earth.add(wireframe);

                // Orbiting Satellites Group
                const satellites = [];
                const satelliteGroup = new THREE.Group();
                for (let i = 0; i < 7; i++) {
                    const sat = new THREE.Group();

                    // Body
                    const body = new THREE.Mesh(
                        new THREE.BoxGeometry(0.3, 0.3, 0.4),
                        new THREE.MeshStandardMaterial({ color: 0xffffff })
                    );
                    sat.add(body);

                    // Solar Panels
                    const panel = new THREE.Mesh(
                        new THREE.BoxGeometry(1.5, 0.04, 0.35),
                        new THREE.MeshStandardMaterial({ color: 0x00d2ff, roughness: 0.3 })
                    );
                    sat.add(panel);

                    satellites.push({
                        mesh: sat,
                        radius: 8.0 + (Math.random() * 2.5),
                        speed: 0.003 + (Math.random() * 0.005),
                        angle: (Math.PI * 2 / 7) * i,
                        inclination: (Math.random() - 0.5) * 1.2
                    });
                    satelliteGroup.add(sat);
                }
                earth.add(satelliteGroup);

                // Background Particle Stars
                const particlesCount = 120;
                const posArray = new Float32Array(particlesCount * 3);
                for(let i=0; i<particlesCount*3; i++) {
                    posArray[i] = (Math.random() - 0.5) * 80;
                }
                const particlesGeo = new THREE.BufferGeometry();
                particlesGeo.setAttribute('position', new THREE.BufferAttribute(posArray, 3));
                const particlesMat = new THREE.PointsMaterial({
                    size: 0.15,
                    color: 0x00d2ff,
                    transparent: true,
                    opacity: 0.5
                });
                const starField = new THREE.Points(particlesGeo, particlesMat);
                scene.add(starField);

                // Responsive Window Handling
                window.addEventListener('resize', () => {
                    camera.aspect = window.innerWidth / window.innerHeight;
                    camera.updateProjectionMatrix();
                    renderer.setSize(window.innerWidth, window.innerHeight);
                });

                // Animation Loop
                function animate() {
                    requestAnimationFrame(animate);

                    earth.rotation.y += 0.0015;
                    wireframe.rotation.y -= 0.0008;
                    starField.rotation.y += 0.0002;

                    satellites.forEach(sat => {
                        sat.angle += sat.speed;
                        sat.mesh.position.x = Math.sin(sat.angle) * sat.radius;
                        sat.mesh.position.z = Math.cos(sat.angle) * sat.radius;
                        sat.mesh.position.y = Math.sin(sat.angle + sat.inclination) * (sat.radius * 0.3);
                        sat.mesh.rotation.y += 0.01;
                    });

                    renderer.render(scene, camera);
                }

                animate();
            } catch (e) {
                console.warn("WebGL 3D background initialization skipped:", e);
            }
        });
    </script>
</body>
</html>