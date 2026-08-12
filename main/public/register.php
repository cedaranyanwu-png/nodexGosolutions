<?php
/**
 * register.php
 *
 * Renders the tenant registration layout allowing businesses to sign up and deploy their private space.
 * Styled with clean premium White & Blue theme.
 */

// Enable strict typing for safety
declare(strict_types=1);

// Require dynamic database configurations
require_once __DIR__ . '/../../php/db.php';

// Instantiate secure session configurations
secureSession();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deploy Tenant | nodexGosolutions</title>

    <!-- Load standard Fonts and Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@300;400;600;700&family=Orbitron:wght@600;700;900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Load premium central White & Blue stylesheet -->
    <link href="<?= assetUrl('/css/style.css') ?>" rel="stylesheet">
</head>
<body>

    <!-- Modular Navigation Bar component -->
    <?php require_once __DIR__ . '/../modul/nav.html'; ?>

    <!-- AUTH SECTION LAYOUT -->
    <div class="auth-layout-wrapper">
        <div class="auth-form-card">

            <div class="text-center mb-4">
                <h3 class="fw-bold text-primary mb-1" style="font-family: 'Orbitron', sans-serif;"><i class="fa-solid fa-server me-2"></i>Deploy Tenant</h3>
                <p class="text-muted small mb-0 text-uppercase fw-bold" style="letter-spacing: 0.5px;">Initialize Your Isolated Portal</p>
            </div>

            <!-- AJAX Action alerts feedback panel -->
            <div id="alertBox" class="alert d-none" role="alert"></div>

            <form id="registerForm">
                <!-- Full name field -->
                <div class="mb-3 text-start">
                    <label class="form-label text-muted small fw-bold">FULL NAME</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0" style="border-color: rgba(0, 114, 255, 0.15);"><i class="fa-solid fa-user text-primary"></i></span>
                        <input type="text" id="regFullname" class="form-control form-input-custom border-start-0 w-100" style="border-color: rgba(0, 114, 255, 0.15); border-radius: 0 8px 8px 0;" placeholder="Amina Anyanwu" required>
                    </div>
                </div>

                <!-- Email field -->
                <div class="mb-3 text-start">
                    <label class="form-label text-muted small fw-bold">EMAIL ADDRESS</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0" style="border-color: rgba(0, 114, 255, 0.15);"><i class="fa-solid fa-envelope text-primary"></i></span>
                        <input type="email" id="regEmail" class="form-control form-input-custom border-start-0 w-100" style="border-color: rgba(0, 114, 255, 0.15); border-radius: 0 8px 8px 0;" placeholder="user@domain.com" required>
                    </div>
                </div>

                <!-- Password field -->
                <div class="mb-4 text-start">
                    <label class="form-label text-muted small fw-bold">PASSWORD</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0" style="border-color: rgba(0, 114, 255, 0.15);"><i class="fa-solid fa-key text-primary"></i></span>
                        <input type="password" id="regPassword" class="form-control form-input-custom border-start-0 w-100" style="border-color: rgba(0, 114, 255, 0.15); border-radius: 0 8px 8px 0;" placeholder="••••••••" required>
                    </div>
                </div>

                <!-- Submit button -->
                <button type="submit" id="btnRegister" class="btn btn-premium w-100 py-3 mb-3">
                    <i class="fa-solid fa-circle-plus me-2"></i>Initialize Account
                </button>
            </form>

            <div class="text-center pt-3 border-top mt-3" style="border-color: rgba(0, 114, 255, 0.1) !important;">
                <p class="small text-muted mb-0">
                    Already have an account? <a href="/login" class="text-primary text-decoration-none fw-bold">Sign In Hub</a>
                </p>
            </div>

        </div>
    </div>

    <!-- Modular Footer component -->
    <?php require_once __DIR__ . '/../modul/footer.html'; ?>

    <!-- JS Libraries -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Dynamic AJAX Form Submission Handler -->
    <script>
    $(document).ready(function() {
        $('#registerForm').on('submit', function(e) {
            e.preventDefault();
            const alertBox = $('#alertBox');
            const btn = $('#btnRegister');

            btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i>Initializing...');
            alertBox.addClass('d-none').removeClass('alert-success alert-danger');

            $.ajax({
                url: '/php/register.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    fullname: $('#regFullname').val(),
                    email: $('#regEmail').val(),
                    password: $('#regPassword').val()
                },
                success: function(response) {
                    btn.prop('disabled', false).html('<i class="fa-solid fa-user-check me-2"></i>Initialize Account');
                    alertBox.removeClass('d-none');

                    if (response.success === true) {
                        alertBox.addClass('alert-success').html('<i class="fa-solid fa-envelope-circle-check me-1"></i> ' + response.message);
                        $('#registerForm')[0].reset();
                    } else {
                        alertBox.addClass('alert-danger').html('<i class="fa-solid fa-triangle-exclamation me-1"></i> ' + response.message);
                    }
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html('<i class="fa-solid fa-user-check me-2"></i>Initialize Account');
                    alertBox.removeClass('d-none').addClass('alert-danger');

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        alertBox.html('<i class="fa-solid fa-triangle-exclamation me-1"></i> ' + xhr.responseJSON.message);
                    } else {
                        alertBox.html('<i class="fa-solid fa-wifi me-1"></i> Server communication error. Please try again.');
                    }
                }
            });
        });
    });
    </script>
</body>
</html>
