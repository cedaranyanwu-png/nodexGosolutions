<?php
/**
 * register.php
 *
 * Renders the tenant registration layout allowing businesses to sign up.
 */

// Enable strict typing for safety
declare(strict_types=1);

// Require dynamic database configuration
require_once __DIR__ . '/../../php/db.php';

// Instantiate secure session configurations
secureSession();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Deploy Tenant | nodexGosolutions</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Load unified CSS from root directory -->
    <link href="<?= assetUrl('/css/register.css') ?>" rel="stylesheet">
</head>
<body>

    <div id="canvas-container"></div>

    <!-- Navigation Header component -->
    <?php require_once __DIR__ . '/../modul/nav.html'; ?>

    <div class="auth-wrapper">
        <div class="auth-card" data-aos="zoom-in" data-aos-duration="900">

            <div class="iss-telemetry d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-satellite me-1"></i> TELEMETRY ACTIVE</span>
                <span id="telemetry-speed">LAUNCH READY</span>
            </div>

            <div class="text-center mb-4">
                <h4 class="fw-bold text-white mb-1">Deploy New Tenant Instance</h4>
                <p class="text-muted small mb-0">Initialize your cloud database & workspace</p>
            </div>

            <div id="alertBox" class="alert d-none" role="alert"></div>

            <form id="registerForm">
                <div class="mb-3">
                    <label class="form-label">Company Name / Full Name</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                        <input type="text" id="regFullname" class="form-control" placeholder="Acme Corporation" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                        <input type="email" id="regEmail" class="form-control" placeholder="billing@acme.com" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Session Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-key"></i></span>
                        <input type="password" id="regPassword" class="form-control" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" id="btnRegister" class="btn btn-space w-100 mb-3">
                    <i class="fa-solid fa-user-check me-2"></i>Initialize Account
                </button>
            </form>

            <div class="text-center pt-2 border-top border-secondary border-opacity-25 mt-3">
                <p class="small mb-0" style="color: var(--text-muted-custom);">
                    Already have an account? <a href="/login" class="text-info text-decoration-none fw-bold">Sign In</a>
                </p>
            </div>

        </div>
    </div>

    <!-- Footer component -->
    <?php require_once __DIR__ . '/../modul/footer.html'; ?>

    <!-- JQUERY, BOOTSTRAP, AND LIBRARIES -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <!-- Load unified JS from root directory -->
    <script src="<?= assetUrl('/js/register.js') ?>"></script>
</body>
</html>
