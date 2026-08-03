<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Register | nodexGosolutions</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<link href="../css/register.css" rel="stylesheet">

    <style>

    </style>
</head>
<body>

    <div id="canvas-container"></div>

    <?php include "../modul/nav.html"?>
    <div class="auth-wrapper">
        <div class="auth-card" data-aos="zoom-in" data-aos-duration="900">

            <div class="iss-telemetry d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-satellite me-1"></i> ISS ORBIT: 418 KM</span>
                <span id="telemetry-speed">7.66 KM/S</span>
            </div>

            <div class="text-center mb-4">
                <h4 class="fw-bold text-white mb-1">Deploy New Tenant</h4>
                <p class="text-muted small mb-0" style="color: var(--text-muted-custom) !important;">Create an account with email verification</p>
            </div>

            <div id="alertBox" class="alert d-none" role="alert"></div>

            <form id="registerForm">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                        <input type="text" id="regFullname" class="form-control" placeholder="Cedar Anyanwu" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                        <input type="email" id="regEmail" class="form-control" placeholder="founder@nodexplatform.com.ng" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Create Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" id="regPassword" class="form-control" placeholder="Min. 8 characters" required>
                    </div>
                </div>

                <button type="submit" id="btnRegister" class="btn btn-space w-100 mb-3">
                    <i class="fa-solid fa-user-check me-2"></i>Initialize Account
                </button>
            </form>

            <div class="text-center pt-2 border-top border-secondary border-opacity-25 mt-3">
                <p class="small mb-0" style="color: var(--text-muted-custom);">
                    Already registered? <a href="login.html" class="text-info text-decoration-none fw-bold">Sign In Here</a>
                </p>
            </div>

        </div>
    </div>
<?php include "../modul/footer.html"?>
    <!-- JQUERY, BOOTSTRAP, AND LIBRARIES -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
     <script src="../js/register.js"></script>


    <script>

    </script>
</body>
</html>
