<?php
/**
 * Dynamic CMS Page Integration Override
 * Queries the site_cms database to determine if there is a custom code override for slug 'gallary'.
 */
require_once __DIR__ . "/../../php/db.php";
$cmsDb = new Database(__DIR__ . "/../../databases", "site_cms");
$cmsPage = $cmsDb->selectOne("pages", ["slug" => "gallary"]);
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
    <title>Executive Gallery & Video Showcase | Cedar Anyanwu</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- AOS Scroll Animation CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="<?= assetUrl('/css/gallary.css') ?>" rel="stylesheet">


    <style>

    </style>
</head>
<body>

    <!-- Scroll Progress Bar -->
    <div id="scroll-progress"></div>

    <!-- 3D Earth & Satellite Constellation WebGL Canvas -->
    <div id="canvas-container"></div>

    <!-- NAVBAR Header -->
    <?php include __DIR__ . "/../modul/nav.html";?>

    <!-- MAIN MEDIA & GALLERY CONTENT -->
    <main class="py-4">
        <div class="container">

            <!-- HERO HEADER -->
            <section class="hero-section text-center" data-aos="fade-up">
                <img src="assets/images/cedar-profile.jpg" alt="Cedar Anyanwu" class="profile-avatar" onerror="this.src='https://via.placeholder.com/150/00d2ff/ffffff?text=Cedar+A.'">
                <br>
                <span class="badge bg-primary text-white px-3 py-2 rounded-pill text-uppercase fw-bold fs-6 mb-2">
                    Executive Media Showcase & Architecture Gallery
                </span>
                <h1 class="display-3 fw-bold text-white mb-2">Cedar Anyanwu</h1>
                <p class="fs-4 gradient-earth fw-bold mb-3">Project Assets, Video Demos & System Media</p>
                <p class="text-muted-custom col-lg-8 mx-auto fs-5 mb-0">
                    A visual archive featuring live multi-tenant web platforms, autonomous browser automation runs, architectural diagrams, and video walkthroughs.
                </p>
            </section>

            <hr class="border-secondary opacity-25 my-5">

            <!-- SECTION 1: FEATURED VIDEO DEMONSTRATIONS (YOUTUBE INTEGRATION) -->
            <section class="mb-5" data-aos="fade-up" data-aos-delay="100">
                <div class="glass-card">
                    <h2 class="text-white mb-4"><i class="fa-solid fa-circle-play me-2 text-danger"></i> Video Walkthroughs & Demos</h2>
                    <div class="row g-4" id="video-grid">
                        <!-- Default Featured Video 1 -->
                        <div class="col-lg-6">
                            <div class="video-container shadow-lg">
                                <!-- Replace VIDEO_ID with your YouTube Video ID or let gallery.php supply it -->
                                <iframe src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ" title="Platform Demo Video" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                            </div>
                            <h5 class="text-white mt-3 mb-1"><i class="fa-brands fa-youtube text-danger me-2"></i> Multi-Tenant Platform Architecture</h5>
                            <p class="text-muted-custom small">Demonstration of environment-aware single-entry routing and lightweight JSON configuration CRUD execution.</p>
                        </div>

                        <!-- Default Featured Video 2 -->
                        <div class="col-lg-6">
                            <div class="video-container shadow-lg">
                                <iframe src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ" title="Automation Engine Demo" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                            </div>
                            <h5 class="text-white mt-3 mb-1"><i class="fa-brands fa-youtube text-danger me-2"></i> Stealth Automation Runner</h5>
                            <p class="text-muted-custom small">Real-time execution of containerized browser agents with Gaussian delay curves and natural traffic simulation.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- SECTION 2: STATIC & CORE PROJECT IMAGES -->
            <section class="mb-5" data-aos="fade-up" data-aos-delay="150">
                <div class="glass-card">
                    <h2 class="text-white mb-4"><i class="fa-solid fa-images me-2 text-warning"></i> Enterprise Screenshots & Blueprints</h2>
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="gallery-img-wrapper">
                                <img src="assets/images/nodex-platform-preview.jpg" class="gallery-img" alt="nodexplatform.com.ng" onerror="this.src='https://via.placeholder.com/400x250/0f172a/00d2ff?text=nodexplatform.com.ng'">
                                <div class="gallery-caption"><i class="fa-solid fa-globe me-1"></i> nodexplatform.com.ng</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="gallery-img-wrapper">
                                <img src="assets/images/quickchat-preview.jpg" class="gallery-img" alt="QuickChat Social Platform" onerror="this.src='https://via.placeholder.com/400x250/1e293b/ffb703?text=QuickChat+Platform'">
                                <div class="gallery-caption"><i class="fa-solid fa-comments me-1"></i> QuickChat Engine</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="gallery-img-wrapper">
                                <img src="assets/images/gameitz-preview.jpg" class="gallery-img" alt="Gameitz Project" onerror="this.src='https://via.placeholder.com/400x250/111827/00ff87?text=Gameitz+Project+UI'">
                                <div class="gallery-caption"><i class="fa-solid fa-gamepad me-1"></i> Gameitz Genesis Project</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- SECTION 3: DYNAMIC MEDIA INGESTION FROM GALLERY.PHP -->
            <section class="mb-5" data-aos="fade-up" data-aos-delay="200">
                <div class="glass-card">
                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
                        <h2 class="text-white mb-0"><i class="fa-solid fa-cloud-arrow-down me-2 text-info"></i> Dynamic Server Stream</h2>
                        <span class="badge bg-info text-dark px-3 py-2 rounded-pill fw-bold">Live Synced from gallery.php</span>
                    </div>

                    <p class="text-muted-custom mb-4">
                        The grid below automatically fetches new screenshots, telemetry visualizers, and YouTube videos as they are registered in <code>gallery.php</code>.
                    </p>

                    <!-- Dynamic Container for extra fetched images & videos -->
                    <div class="row g-4" id="dynamic-gallery-stream">
                        <!-- Initial Loading Placeholders -->
                        <div class="col-md-4">
                            <div class="gallery-img-wrapper">
                                <img src="assets/images/stream-1.jpg" class="gallery-img" alt="System Asset" onerror="this.src='https://via.placeholder.com/400x250/0f172a/00d2ff?text=Dynamic+Asset+1'">
                                <div class="gallery-caption">Architecture Spec</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="gallery-img-wrapper">
                                <img src="assets/images/stream-2.jpg" class="gallery-img" alt="System Asset" onerror="this.src='https://via.placeholder.com/400x250/0f172a/7928ca?text=Dynamic+Asset+2'">
                                <div class="gallery-caption">Dashboard Interface</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="gallery-img-wrapper">
                                <img src="assets/images/stream-3.jpg" class="gallery-img" alt="System Asset" onerror="this.src='https://via.placeholder.com/400x250/0f172a/ffb703?text=Dynamic+Asset+3'">
                                <div class="gallery-caption">Analytics Stream</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CONTACT FOOTER CARD -->
            <section class="mb-5" data-aos="fade-up" data-aos-delay="250">
                <div class="glass-card p-4 text-center">
                    <h2 class="text-white mb-3">Media & Enterprise Inquiries</h2>
                    <p class="text-muted-custom col-lg-8 mx-auto fs-5 mb-4">
                        Interested in live system demonstrations or software architecture blueprints?
                    </p>
                    <a href="mailto:info@nodexgosolutions.com?subject=Media%20And%20Demo%20Inquiry" class="btn btn-lg btn-warning rounded-pill px-5 fw-bold text-dark">
                        Contact Cedar Anyanwu
                    </a>
                </div>
            </section>

        </div>
    </main>

    <!-- FOOTER -->
    <?php include __DIR__ . "/../modul/footer.html";?>
    <!-- JS Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
     <script src="<?= assetUrl('/js/gallary.js') ?>"></script>



    <!-- Gallery Ingestion & 3D Satellites Script -->
    <script>

    </script>
</body>
</html>
