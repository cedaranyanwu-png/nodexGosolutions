<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>nodexGosolutions | Bridging Earth & Space Tech</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- AOS Scroll Animation CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
     <link href="../css/style.css" rel="stylesheet">


    <style>

    </style>
</head>
<body>

    <!-- Scroll Progress -->
    <div id="scroll-progress"></div>

    <!-- 3D Canvas Background -->
    <div id="canvas-container"></div>

    <!-- NAVBAR -->
   <?php include "../modul/nav.html";?>
    <!-- SECTION 1: HERO -->
    <section id="hero" class="text-center">
        <div class="container" data-aos="zoom-in">
            <span class="badge bg-primary px-3 py-2 rounded-pill mb-3 text-uppercase fw-bold fs-6 text-wrap">Next-Gen Enterprise Tech & Space Solutions</span>
            <h1 class="display-2 fw-bolder mb-3 text-white">Empowering Businesses From <br class="d-none d-sm-inline"><span class="gradient-text">Earth to the Stars</span></h1>
            <p class="lead col-lg-8 mx-auto text-muted-custom mb-4 fw-semibold">nodexGosolutions brings high-tier software infrastructure, digital transformation, and satellite data tech to emerging ventures and global enterprises.</p>
            <div class="d-flex justify-content-center gap-3 btn-responsive-group">
                <a href="#pricing" class="btn btn-lg btn-primary rounded-pill px-4 px-sm-5 fw-bold">Explore Pricing</a>
                <a href="#about" class="btn btn-lg btn-outline-light rounded-pill px-4 px-sm-5 fw-bold">Learn More</a>
            </div>
        </div>
    </section>

    <!-- SECTION 2: ABOUT US -->
    <section id="about">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-6" data-aos="fade-right">
                    <h2 class="display-5 fw-bold mb-3 text-white">Who We Are at <span class="gradient-text">nodexGosolutions</span></h2>
                    <p class="lead text-white fw-bold fs-5">We are an innovation hub dedicated to scaling businesses in low-tech markets and powering orbital-class computational research.</p>
                    <p class="text-muted-custom">Founded under the vision of Cedar, CEO of nodexGosolutions, our mission is to build affordable, world-class software stacks while creating pathways for African tech and space technology adoption.</p>
                </div>
                <div class="col-lg-6 text-center" data-aos="fade-left">
                    <img src="watermarked_img_2473855018031345766.png" class="img-fluid img-rounded-3d" alt="Space & Tech Node">
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 3: TRACTION & METRICS -->
    <section id="traction">
        <div class="container">
            <div class="row text-center g-3 g-md-4">
                <div class="col-6 col-md-3" data-aos="flip-up" data-aos-delay="100">
                    <div class="glass-card">
                        <h2 class="display-5 fw-bold gradient-text mb-1">250+</h2>
                        <p class="text-muted-custom fw-bold mb-0 small">Startups Empowered</p>
                    </div>
                </div>
                <div class="col-6 col-md-3" data-aos="flip-up" data-aos-delay="200">
                    <div class="glass-card">
                        <h2 class="display-5 fw-bold gradient-gold mb-1">₦100M+</h2>
                        <p class="text-muted-custom fw-bold mb-0 small">Revenue Generated</p>
                    </div>
                </div>
                <div class="col-6 col-md-3" data-aos="flip-up" data-aos-delay="300">
                    <div class="glass-card">
                        <h2 class="display-5 fw-bold gradient-text mb-1">99.9%</h2>
                        <p class="text-muted-custom fw-bold mb-0 small">System Uptime</p>
                    </div>
                </div>
                <div class="col-6 col-md-3" data-aos="flip-up" data-aos-delay="400">
                    <div class="glass-card">
                        <h2 class="display-5 fw-bold gradient-gold mb-1">14+</h2>
                        <p class="text-muted-custom fw-bold mb-0 small">Satellite Partners</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 4: CORE SERVICES -->
    <section id="services">
        <div class="container">
            <div class="text-center mb-4 mb-md-5" data-aos="fade-up">
                <h2 class="display-5 fw-bold text-white">Our Engineering Ecosystem</h2>
                <p class="text-muted-custom">Tailored technical solutions for businesses at every phase of growth.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="glass-card h-100">
                        <i class="fa-solid fa-code fs-1 gradient-text mb-3"></i>
                        <h4>Custom Web & App Dev</h4>
                        <p class="text-muted-custom mb-0">Blazing fast web applications, enterprise software, and mobile platforms scaled for millions of users.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="glass-card h-100">
                        <i class="fa-solid fa-satellite fs-1 gradient-gold mb-3"></i>
                        <h4>Space & GIS Solutions</h4>
                        <p class="text-muted-custom mb-0">Remote sensing, satellite image processing, and spatial analytics for agriculture, urban planning, and research.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="glass-card h-100">
                        <i class="fa-solid fa-cloud-bolt fs-1 gradient-text mb-3"></i>
                        <h4>Cloud & DevOps Stack</h4>
                        <p class="text-muted-custom mb-0">End-to-end cloud infrastructure deployment, server security, continuous integration, and database optimization.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 5: SPACE SCIENCE & AFRICA INITIATIVE -->
    <section id="space-and-africa">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-6 order-2 order-lg-1" data-aos="fade-right">
                    <img src="watermarked_img_2473855018031345766.png" class="img-fluid img-rounded-3d" alt="Space Science Initiative">
                </div>
                <div class="col-lg-6 order-1 order-lg-2" data-aos="fade-left">
                    <h2 class="display-5 fw-bold mb-3 text-white">Pioneering Space Tech & African Startups</h2>
                    <p class="lead text-white font-weight-bold fs-5">Space science is critical infrastructure for climate and economic security. We democratize orbital data for researchers and agritech ventures.</p>
                    <p class="text-muted-custom">Simultaneously, we run our Startup Accelerator—providing subsidized code stacks, templates, and cloud resources to digitize low-tech African businesses rapidly.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 6: PRICING PLANS -->
    <section id="pricing">
        <div class="container">
            <div class="text-center mb-4 mb-md-5" data-aos="fade-up">
                <h2 class="display-5 fw-bold text-white">Flexible Pricing Plans</h2>
                <p class="text-muted-custom">From micro-businesses starting at ₦3,000 to enterprise deployments above ₦100,000+.</p>
            </div>
            <div class="row g-4 align-items-stretch">
                <div class="col-lg-3 col-sm-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="glass-card text-center d-flex flex-column h-100">
                        <h4 class="fw-bold">Micro Tier</h4>
                        <h3 class="my-3 gradient-text">₦3,000 <small class="fs-6 text-white">/mo</small></h3>
                        <p class="text-muted-custom small">Ideal for micro-startups and basic domain utility tools.</p>
                        <ul class="list-unstyled text-start my-3 text-muted-custom small fw-semibold flex-grow-1">
                            <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i> Basic website hosting</li>
                            <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i> 1 SSL certificate</li>
                            <li><i class="fa-solid fa-check text-success me-2"></i> Basic API Access</li>
                        </ul>
                        <a href="#contact" class="btn btn-outline-light w-100 rounded-pill fw-bold mt-auto">Choose Starter</a>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="glass-card text-center d-flex flex-column h-100">
                        <h4 class="fw-bold">Growth Tier</h4>
                        <h3 class="my-3 gradient-text">₦25,000 <small class="fs-6 text-white">/mo</small></h3>
                        <p class="text-muted-custom small">For growing businesses needing web platforms and analytics.</p>
                        <ul class="list-unstyled text-start my-3 text-muted-custom small fw-semibold flex-grow-1">
                            <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i> Custom Web Application</li>
                            <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i> Payment Integration</li>
                            <li><i class="fa-solid fa-check text-success me-2"></i> Server Maintenance</li>
                        </ul>
                        <a href="#contact" class="btn btn-outline-light w-100 rounded-pill fw-bold mt-auto">Get Growth</a>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6" data-aos="fade-up" data-aos-delay="300">
                    <div class="glass-card popular text-center d-flex flex-column h-100">
                        <span class="badge bg-primary mb-2 fs-6">Most Popular</span>
                        <h4 class="fw-bold">Business Pro</h4>
                        <h3 class="my-3 gradient-text">₦75,000 <small class="fs-6 text-white">/mo</small></h3>
                        <p class="text-muted-custom small">Full digital stack for established firms needing scale.</p>
                        <ul class="list-unstyled text-start my-3 text-muted-custom small fw-semibold flex-grow-1">
                            <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i> Full Web & Mobile App</li>
                            <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i> Cloud Infrastructure</li>
                            <li><i class="fa-solid fa-check text-success me-2"></i> GIS Data Dashboard</li>
                        </ul>
                        <a href="#contact" class="btn btn-primary w-100 rounded-pill fw-bold mt-auto">Start Pro</a>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6" data-aos="fade-up" data-aos-delay="400">
                    <div class="glass-card text-center d-flex flex-column h-100">
                        <h4 class="fw-bold">Enterprise & Space</h4>
                        <h3 class="my-3 gradient-gold">₦100,000+</h3>
                        <p class="text-muted-custom small">Custom satellite data & enterprise infrastructure.</p>
                        <ul class="list-unstyled text-start my-3 text-muted-custom small fw-semibold flex-grow-1">
                            <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i> Satellite Sensing API</li>
                            <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i> Dedicated Engineers</li>
                            <li><i class="fa-solid fa-check text-success me-2"></i> Custom SLA & Support</li>
                        </ul>
                        <a href="#contact" class="btn btn-outline-warning w-100 rounded-pill fw-bold mt-auto">Contact Sales</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 7: WHY CHOOSE US -->
    <section id="why-us">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-6" data-aos="fade-right">
                    <h2 class="display-5 fw-bold mb-4 text-white">Why Partner With <span class="gradient-text">nodexGosolutions</span>?</h2>
                    <div class="d-flex mb-4">
                        <i class="fa-solid fa-bolt fs-2 text-warning me-3 mt-1 flex-shrink-0"></i>
                        <div>
                            <h5 class="text-white mb-1">Rapid Deployment</h5>
                            <p class="text-muted-custom mb-0">We take businesses from zero code to active deployments in days.</p>
                        </div>
                    </div>
                    <div class="d-flex mb-4">
                        <i class="fa-solid fa-shield-halved fs-2 text-info me-3 mt-1 flex-shrink-0"></i>
                        <div>
                            <h5 class="text-white mb-1">Enterprise Security</h5>
                            <p class="text-muted-custom mb-0">Bank-grade encryption protocols across all software and cloud platforms.</p>
                        </div>
                    </div>
                    <div class="d-flex">
                        <i class="fa-solid fa-globe fs-2 text-success me-3 mt-1 flex-shrink-0"></i>
                        <div>
                            <h5 class="text-white mb-1">Global & Space Reach</h5>
                            <p class="text-muted-custom mb-0">Integrated solutions designed to operate seamlessly across continents.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 text-center" data-aos="fade-left">
                    <div class="glass-card p-4 p-md-5">
                        <h3 class="gradient-gold mb-3">Innovation Engine</h3>
                        <p class="text-muted-custom fs-5 mb-0">We bridge low-cost business entry with complex scientific engineering.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 8: TESTIMONIALS -->
    <section id="testimonials">
        <div class="container">
            <div class="text-center mb-4 mb-md-5" data-aos="fade-up">
                <h2 class="display-5 fw-bold text-white">Client Testimonials</h2>
                <p class="text-muted-custom">What leaders say about working with nodexGosolutions.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4" data-aos="zoom-in" data-aos-delay="100">
                    <div class="glass-card h-100 d-flex flex-column">
                        <p class="fst-italic text-white fs-6 flex-grow-1">"nodexGosolutions transformed our retail logistics system. Cedar and his team delivered beyond expectations for an unbeatable price."</p>
                        <hr class="border-secondary my-3">
                        <h6 class="fw-bold mb-0 text-white">Amina K.</h6>
                        <small class="text-muted-custom">CEO, WestAfrica Logistics</small>
                    </div>
                </div>
                <div class="col-md-4" data-aos="zoom-in" data-aos-delay="200">
                    <div class="glass-card h-100 d-flex flex-column">
                        <p class="fst-italic text-white fs-6 flex-grow-1">"The satellite crop analytics built by nodexGosolutions gave our agricultural startup real-time yield monitoring. Game changer!"</p>
                        <hr class="border-secondary my-3">
                        <h6 class="fw-bold mb-0 text-white">Emeka O.</h6>
                        <small class="text-muted-custom">Founder, AgriSpace Tech</small>
                    </div>
                </div>
                <div class="col-md-4" data-aos="zoom-in" data-aos-delay="300">
                    <div class="glass-card h-100 d-flex flex-column">
                        <p class="fst-italic text-white fs-6 flex-grow-1">"From the ₦25,000 plan up to scaling our full infrastructure, nodexGosolutions has been our reliable technology partner."</p>
                        <hr class="border-secondary my-3">
                        <h6 class="fw-bold mb-0 text-white">David M.</h6>
                        <small class="text-muted-custom">CTO, Fintech Node</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 9: CASE STUDIES & TECH STACK -->
    <section id="case-studies-tech">
        <div class="container text-center">
            <h2 class="display-5 fw-bold mb-4 text-white" data-aos="fade-up">Impact & Tech Stack</h2>
            <div class="row g-4 mb-4 mb-md-5 text-start">
                <div class="col-md-6" data-aos="fade-right">
                    <div class="glass-card h-100">
                        <span class="badge bg-info text-dark mb-2 fw-bold">Space & Agritech</span>
                        <h4 class="text-white">Orbital Land Indexing</h4>
                        <p class="text-muted-custom mb-0">Utilized satellite image APIs to map agricultural soil moisture, reducing crop failure risks by 35%.</p>
                    </div>
                </div>
                <div class="col-md-6" data-aos="fade-left">
                    <div class="glass-card h-100">
                        <span class="badge bg-success mb-2 fw-bold">Low-Tech Acceleration</span>
                        <h4 class="text-white">African Digital SME Modernization</h4>
                        <p class="text-muted-custom mb-0">Provided 50 local commerce shops with affordable payment solutions, increasing regional revenue 3x.</p>
                    </div>
                </div>
            </div>
            <div class="row g-3 justify-content-center text-white" data-aos="zoom-in">
                <div class="col-4 col-sm-2"><i class="fa-brands fa-react fs-2 mb-2 text-info"></i><p class="fw-bold small">React / Next</p></div>
                <div class="col-4 col-sm-2"><i class="fa-brands fa-python fs-2 mb-2 text-warning"></i><p class="fw-bold small">Python / AI</p></div>
                <div class="col-4 col-sm-2"><i class="fa-brands fa-node-js fs-2 mb-2 text-success"></i><p class="fw-bold small">Node.js</p></div>
                <div class="col-4 col-sm-2"><i class="fa-brands fa-aws fs-2 mb-2 text-warning"></i><p class="fw-bold small">AWS Cloud</p></div>
                <div class="col-4 col-sm-2"><i class="fa-brands fa-docker fs-2 mb-2 text-primary"></i><p class="fw-bold small">Docker</p></div>
            </div>
        </div>
    </section>

    <!-- SECTION 10: OUR PROCESS -->
    <section id="process">
        <div class="container">
            <div class="text-center mb-4 mb-md-5" data-aos="fade-up">
                <h2 class="display-5 fw-bold text-white">How We Work</h2>
                <p class="text-muted-custom">A streamlined path from consultation to launch.</p>
            </div>
            <div class="row g-3 g-md-4 text-center">
                <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="100">
                    <div class="glass-card h-100">
                        <h2 class="gradient-text">01</h2>
                        <h5 class="fs-6 fw-bold">Discovery</h5>
                        <p class="text-muted-custom small mb-0">Analyzing business & tech goals.</p>
                    </div>
                </div>
                <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="200">
                    <div class="glass-card h-100">
                        <h2 class="gradient-text">02</h2>
                        <h5 class="fs-6 fw-bold">Architecture</h5>
                        <p class="text-muted-custom small mb-0">Designing cloud & app stacks.</p>
                    </div>
                </div>
                <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="300">
                    <div class="glass-card h-100">
                        <h2 class="gradient-text">03</h2>
                        <h5 class="fs-6 fw-bold">Engineering</h5>
                        <p class="text-muted-custom small mb-0">Agile dev & quality checks.</p>
                    </div>
                </div>
                <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="400">
                    <div class="glass-card h-100">
                        <h2 class="gradient-text">04</h2>
                        <h5 class="fs-6 fw-bold">Launch</h5>
                        <p class="text-muted-custom small mb-0">Deployment & active scaling.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 11: FAQ -->
    <section id="faq">
        <div class="container">
            <div class="text-center mb-4 mb-md-5" data-aos="fade-up">
                <h2 class="display-5 fw-bold text-white">Frequently Asked Questions</h2>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8" data-aos="fade-up">
                    <div class="accordion accordion-flush" id="faqAccordion">
                        <div class="accordion-item glass-card mb-3 p-2">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed bg-transparent text-white fw-bold fs-6" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    Can I start with the ₦3,000 plan and upgrade later?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-muted-custom small">
                                    Yes! Our platform is modular. You can start with basic micro-hosting services and seamlessly scale to enterprise or space data tiers as your startup grows.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item glass-card mb-3 p-2">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed bg-transparent text-white fw-bold fs-6" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    What space technology services do you offer?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-muted-custom small">
                                    We offer GIS satellite processing, satellite API integration, remote sensing analytics for agriculture, and educational space technology tools.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 12: CONTACT & FOOTER -->
   <?php include "../modul/footer.html";?>
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
     <script src="../js/index.js"></script>


    <script>

    </script>
</body>
</html>
