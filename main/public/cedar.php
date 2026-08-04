<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cedar | CEO, nodexGosolutions | Building the Future of Africa</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- FontAwesome for Social Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- AOS (Animate on Scroll) CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="../css/cedar.css" rel="stylesheet">

    <!-- Custom CSS -->
    <style>
           </style>
</head>
<body>

    <!-- Scroll Progress Bar -->
    <div id="scroll-progress"></div>

    <!-- Background Canvas for 3D Animation (Three.js) -->
    <canvas id="background-canvas"></canvas>

    <!-- Navigation -->
   <?php include __DIR__ . "/../modul/nav.html"?>

    <!-- SECTION 1: Hero - Introducing Cedar -->
    <section id="hero" class="d-flex align-items-center">
        <div class="container text-center" data-aos="fade-up">
            <img src="https://i.ibb.co/Xxd9yXv/cedar-profile-1.png" alt="Cedar" class="cedar-profile-img mb-4 img-3d-effect">
            <h1 class="display-1 fw-bolder">I AM <span class="text-gradient-tech">CEDAR</span></h1>
            <p class="lead text-uppercase tracking-wider">CEO, nodexGosolutions | Shaper of the Future</p>
        </div>
    </section>

    <!-- SECTION 2: The Visionary - My Mission -->
    <section id="vision">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6" data-aos="fade-right">
                    <h2 class="section-title text-gradient-space">The Visionary</h2>
                    <p class="lead">From the ground up, I’m driven to build sustainable, high-impact technology. My mission at nodexGosolutions is to bridge the gap between imagination and execution.</p>
                </div>
                <div class="col-md-6" data-aos="fade-left" data-aos-delay="200">
                    <img src="https://i.ibb.co/VvzM47G/cedar-construction.png" alt="Building Vision" class="img-fluid img-3d-effect">
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 3: The CEO - Leadership Defined -->
    <section id="ceo">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 order-md-2" data-aos="fade-left">
                    <h2 class="section-title text-gradient-tech">The CEO</h2>
                    <p class="lead">At nodexGosolutions, I lead teams to solve complex digital challenges. We don't just provide solutions; we engineer competitive advantages, guiding companies through digital transformation.</p>
                </div>
                <div class="col-md-6 order-md-1" data-aos="fade-right" data-aos-delay="200">
                    <img src="https://i.ibb.co/108K6fF/cedar-leadership-2.png" alt="Leadership" class="img-fluid img-3d-effect">
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 4: What I Offer: Tech Innovation -->
    <section id="offer-tech">
        <div class="container text-center">
            <h2 class="section-title text-gradient-tech" data-aos="fade-up">Tech Space Innovation</h2>
            <div class="row g-4 mt-5">
                <div class="col-md-4" data-aos="zoom-in" data-aos-delay="100">
                    <div class="offer-icon">💻</div>
                    <h4>Software Engineering</h4>
                    <p>Scalable architectural design and deployment of robust software ecosystems.</p>
                </div>
                <div class="col-md-4" data-aos="zoom-in" data-aos-delay="300">
                    <div class="offer-icon">🌐</div>
                    <h4>Digital Transformation</h4>
                    <p>Guiding businesses into the digital age with strategic IT integration.</p>
                </div>
                <div class="col-md-4" data-aos="zoom-in" data-aos-delay="500">
                    <div class="offer-icon">🤖</div>
                    <h4>AI & Automation</h4>
                    <p>Implementing intelligent automation to optimize workflows and decision making.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 5: What I Offer: Space Science -->
    <section id="offer-space">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-7" data-aos="fade-right">
                    <h2 class="section-title text-gradient-space">Space Science Exploration</h2>
                    <p class="lead">My passion extends beyond the digital realm. I advocate for and invest in the democratization of space science and satellite technology.</p>
                    <ul class="list-unstyled mt-4">
                        <li>🚀 Satellite Data Analysis</li>
                        <li>🌌 Space Policy Advocacy for Emerging Nations</li>
                        <li>📡 Remote Sensing Solutions</li>
                    </ul>
                </div>
                <div class="col-md-5" data-aos="fade-left" data-aos-delay="300">
                    <img src="https://i.ibb.co/YyYhN7R/space-africa-symbolic.png" alt="Space Science" class="img-fluid img-3d-effect">
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 6: The African Vision -->
    <section id="african-vision">
        <div class="container text-center">
            <h2 class="section-title text-gradient-tech" data-aos="fade-up">Shaping Africa's Future</h2>
            <p class="lead col-md-8 mx-auto" data-aos="fade-up" data-aos-delay="200">Africa is the next frontier of innovation. I am committed to unlocking this potential by fostering ecosystems where low-tech startups can thrive.</p>
            <img src="https://i.ibb.co/3W6f5fS/africa-continent-future.png" alt="Future of Africa" class="img-fluid mt-5 img-3d-effect" style="max-width: 600px" data-aos="zoom-in" data-aos-delay="400">
        </div>
    </section>

    <!-- SECTION 7: Empowering Low-Tech Startups -->
    <section id="low-tech">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 order-md-2" data-aos="fade-left">
                    <h2 class="section-title text-gradient-space">Empowering Startups</h2>
                    <p class="lead">I identify regions with immense human potential but low technological infrastructure. Through nodexGosolutions, we provide tailored mentorship, essential tech stacks, and investment access to jumpstart local innovation.</p>
                </div>
                <div class="col-md-6 order-md-1" data-aos="fade-right" data-aos-delay="200">
                    <img src="https://i.ibb.co/R9M4qD7/low-tech-entrepreneurs.png" alt="Startup Empowerment" class="img-fluid img-3d-effect">
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 8: Building the World for Africa -->
    <section id="build-world">
        <div class="container text-center">
            <h2 class="section-title text-gradient-tech" data-aos="fade-up">Building the World FOR Africa</h2>
            <p class="lead col-md-8 mx-auto" data-aos="fade-up" data-aos-delay="200">This isn't about charity; it's about competitive parity. I intend to build global technological architecture that seamlessly integrates African talent and needs, ensuring the continent isn't just a consumer, but a creator.</p>
            <img src="https://i.ibb.co/89fF4zR/cedar-driving-2.png" alt="Global Drive" class="img-fluid mt-5 img-3d-effect" data-aos="zoom-in" data-aos-delay="400">
        </div>
    </section>

    <!-- SECTION 9: Bridging the Divide -->
    <section id="bridge">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6" data-aos="fade-right">
                    <h2 class="section-title text-gradient-space">Bridging the Divide</h2>
                    <p class="lead">I advocate for policy and infrastructure that connects rural African communities to the global digital economy. We are building digital highways that bring opportunity to where talent resides.</p>
                </div>
                <div class="col-md-6" data-aos="fade-left" data-aos-delay="200">
                    <img src="https://i.ibb.co/b3K3S1C/digital-bridge-africa.png" alt="Digital Bridge" class="img-fluid img-3d-effect">
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 10: Space Science in Africa -->
    <section id="space-science-africa">
        <div class="container text-center">
            <h2 class="section-title text-gradient-tech" data-aos="fade-up">Space Science for Africa</h2>
            <p class="lead col-md-8 mx-auto" data-aos="fade-up" data-aos-delay="200">Space is not a luxury. I advocate for African nations to utilize space technology for agriculture, climate monitoring, and resource management, fostering true technological sovereignty.</p>
            <img src="https://i.ibb.co/xXzK9nC/rocket-launch-africa.png" alt="African Space Launch" class="img-fluid mt-5 img-3d-effect" style="max-width: 700px;" data-aos="zoom-in" data-aos-delay="400">
        </div>
    </section>

    <!-- SECTION 11: The Global Journey -->
    <section id="journey">
        <div class="container text-center">
            <h2 class="section-title text-gradient-space" data-aos="fade-up">The Global Journey</h2>
            <div class="row mt-5">
                <div class="col-md-6" data-aos="fade-right" data-aos-delay="200">
                    <img src="https://i.ibb.co/WcZ4YkX/cedar-jet-arrival.png" alt="Global Reach" class="img-fluid img-3d-effect">
                </div>
                <div class="col-md-6 d-flex align-items-center" data-aos="fade-left" data-aos-delay="400">
                    <p class="lead p-4 text-start">My journey takes me globally to forge the partnerships needed to fuel African innovation. We connect Silicon Valley innovation with African ambition.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION 12: Call to Action / Contact & Social Links -->
    <?php include __DIR__ . "/../modul/footer.html"?>
    <!-- Bootstrap 5 JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- AOS (Animate on Scroll) JS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <!-- Three.js (for the 3D Background animation) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="../js/cedar.js"></script>


    <!-- Custom JS for Animations -->
    <script>

    </script>
</body>
</html>
