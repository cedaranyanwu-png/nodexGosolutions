/**
 * NodexPlatform - Complete Authentication Script
 * Handles Login, Register, Forgot Password, and Verification
 */

$(document).ready(function() {

    // ==========================================================================
    // CONFIGURATION (CORRECTED URL)
    // ==========================================================================
    const API_BASE_URL = '/python/api/auth'; // <-- FIXED: Must include '/api/'
    const TOKEN_KEY = 'nodex_auth_token';
    const USER_KEY = 'nodex_user_data';

    // ==========================================================================
    // UTILITY FUNCTIONS
    // ==========================================================================

    function getCSRFToken() {
        return $('input[name="csrfmiddlewaretoken"]').val() ||
               document.querySelector('[name=csrfmiddlewaretoken]')?.value ||
               getCookie('csrftoken');
    }

    function getCookie(name) {
        let cookieValue = null;
        if (document.cookie && document.cookie !== '') {
            const cookies = document.cookie.split(';');
            for (let i = 0; i < cookies.length; i++) {
                const cookie = cookies[i].trim();
                if (cookie.substring(0, name.length + 1) === (name + '=')) {
                    cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                    break;
                }
            }
        }
        return cookieValue;
    }

    function storeAuth(token, userData) {
        localStorage.setItem(TOKEN_KEY, token);
        localStorage.setItem(USER_KEY, JSON.stringify(userData));
    }

    function getAuthToken() {
        return localStorage.getItem(TOKEN_KEY);
    }

    function clearAuth() {
        localStorage.removeItem(TOKEN_KEY);
        localStorage.removeItem(USER_KEY);
    }

    function showAlert(container, type, message, duration = 5000) {
        const alertId = 'alert-' + Date.now();
        const iconClass = type === 'success' ? 'check-circle' :
                         type === 'danger' ? 'exclamation-circle' :
                         type === 'warning' ? 'exclamation-triangle' : 'info-circle';

        const alertHtml = `
            <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="bi bi-${iconClass} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        $(container + ' .alert').remove();
        $(container).prepend(alertHtml);

        if (duration > 0) {
            setTimeout(function() {
                $(`#${alertId}`).alert('close');
            }, duration);
        }
    }

    function setLoading($button, isLoading, loadingText = 'Processing...') {
        if (isLoading) {
            $button.data('original-html', $button.html());
            $button.html(`<span class="spinner-border spinner-border-sm me-2"></span>${loadingText}`);
            $button.prop('disabled', true);
        } else {
            const originalHtml = $button.data('original-html');
            if (originalHtml) $button.html(originalHtml);
            $button.prop('disabled', false);
        }
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function checkPasswordStrength(password) {
        if (password.length === 0) return { strength: 0, feedback: '', class: '' };
        let strength = 0;
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;

        if (strength <= 1) return { strength, feedback: 'Weak', class: 'text-danger' };
        if (strength <= 2) return { strength, feedback: 'Fair', class: 'text-warning' };
        if (strength <= 3) return { strength, feedback: 'Good', class: 'text-info' };
        return { strength, feedback: 'Strong', class: 'text-success' };
    }

    function isStrongPassword(password) {
        return password.length >= 8 && /[A-Z]/.test(password) && /[0-9]/.test(password);
    }

    // ==========================================================================
    // REGISTER FORM FUNCTIONALITY
    // ==========================================================================
    const $registerForm = $('#registerForm');

    if ($registerForm.length) {
        $('#registerEmail').on('input', function() {
            const email = $(this).val().trim();
            $(this).toggleClass('is-invalid', !isValidEmail(email) && email.length > 0)
                   .toggleClass('is-valid', isValidEmail(email));
        });

        $('#registerPassword').on('input', function() {
            const password = $(this).val();
            const $strengthText = $('#passwordStrength');
            const strength = checkPasswordStrength(password);

            if (password.length === 0) {
                $(this).removeClass('is-valid is-invalid');
                $strengthText.text('').removeClass();
                return;
            }

            $(this).toggleClass('is-invalid', strength.strength <= 2)
                   .toggleClass('is-valid', strength.strength > 2);
            $strengthText.text(strength.feedback).removeClass().addClass(strength.class + ' small');
        });

        $('#registerPasswordConfirm').on('input', function() {
            const password = $('#registerPassword').val();
            const confirm = $(this).val();
            $(this).toggleClass('is-invalid', confirm !== '' && password !== confirm)
                   .toggleClass('is-valid', confirm !== '' && password === confirm);
        });

               $registerForm.on('submit', function(e) {
            e.preventDefault();

            const firstName = $('#firstName').val().trim();
            const lastName = $('#lastName').val().trim();
            const email = $('#registerEmail').val().trim();

            // Get passwords and trim any accidental spaces
            const password = $('#registerPassword').val();
            const passwordConfirm = $('#registerPasswordConfirm').val();

            const termsAccepted = $('#terms').is(':checked');
            const $submitBtn = $(this).find('button[type="submit"]');

            // DEBUG: Log the values to the console (Press F12 to see this)
            console.log("Checking passwords -> Password:", password, "| Confirm:", passwordConfirm);

            if (!firstName || !lastName || !email || !password || !passwordConfirm) {
                showAlert('.auth-form', 'danger', 'Please fill in all fields');
                return;
            }
            if (!isValidEmail(email)) {
                showAlert('.auth-form', 'danger', 'Please enter a valid email address');
                return;
            }
            if (!isStrongPassword(password)) {
                showAlert('.auth-form', 'danger', 'Password must be at least 8 characters with 1 uppercase letter and 1 number');
                return;
            }

            // Strict check, but now we know exactly what it's comparing
            if (password !== passwordConfirm) {
                console.error("PASSWORD MISMATCH DETECTED!");
                showAlert('.auth-form', 'danger', 'Passwords do not match. Please type them exactly the same.');
                return;
            }
            if (!termsAccepted) {
                showAlert('.auth-form', 'danger', 'You must accept the Terms of Service and Privacy Policy');
                return;
            }

            // ... (keep the rest of the AJAX code exactly as it was)

            $('.auth-form .alert').remove();
            setLoading($submitBtn, true, 'Creating account...');

                        const accountType = $('input[name="accountType"]:checked').val(); // <-- NEW

            // ... (keep validation checks) ...

            $.ajax({
                url: `${API_BASE_URL}/register/`,
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    first_name: firstName,
                    last_name: lastName,
                    email: email,
                    password: password,
                    account_type: accountType // <-- NEW
                }),
                headers: { 'X-CSRFToken': getCSRFToken() },
                // ... (keep success/error handlers) ...
                headers: { 'X-CSRFToken': getCSRFToken() },
                success: function(response) {
                    console.log("Registration Success:", response);
                    setLoading($submitBtn, false);
                    showAlert('.auth-form', 'success', response.message);

                    if (response.verify_link) {
                        const verifyHtml = `
                            <div class="alert alert-warning mt-3">
                                <strong>Testing Mode:</strong> Click this link to verify your email:<br>
                                <a href="${response.verify_link}" target="_blank" class="text-break">${response.verify_link}</a>
                            </div>
                        `;
                        $('.auth-form').append(verifyHtml);
                    }

                    $submitBtn.prop('disabled', true).text('Account Created');
                    clearAuth(); // Ensure no accidental login state
                    setTimeout(() => window.location.href = '/login/', 4000);
                },
                error: function(xhr) {
                    console.error("Registration Failed:", xhr.responseText);
                    setLoading($submitBtn, false);

                    let errorMsg = 'Registration failed. Please try again.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    showAlert('.auth-form', 'danger', errorMsg);
                }
            });
        });
    }

    // ==========================================================================
    // LOGIN FORM FUNCTIONALITY
    // ==========================================================================
    const $loginForm = $('#loginForm');
    if ($loginForm.length) {
        $('#loginEmail').on('input', function() {
            const email = $(this).val().trim();
            $(this).toggleClass('is-invalid', !isValidEmail(email) && email.length > 0)
                   .toggleClass('is-valid', isValidEmail(email));
        });

        $loginForm.on('submit', function(e) {
            e.preventDefault();

            const email = $('#loginEmail').val().trim();
            const password = $('#loginPassword').val();
            const $submitBtn = $(this).find('button[type="submit"]');

            if (!email || !password) {
                showAlert('.auth-form', 'danger', 'Please fill in all fields');
                return;
            }

            $('.auth-form .alert').remove();
            setLoading($submitBtn, true, 'Signing in...');

            $.ajax({
                url: `${API_BASE_URL}/login/`,
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ email: email, password: password }),
                headers: { 'X-CSRFToken': getCSRFToken() },
                success: function(response) {
                    console.log("Login Success:", response);
                    storeAuth(response.token, response.user);
                    showAlert('.auth-form', 'success', response.message || 'Login successful!');

                    // FIXED: Fallback to /user/dashboard/ instead of /dashboard/
                    setTimeout(() => window.location.href = response.redirect_url || '/users/dashboard/', 1000);
                },
                error: function(xhr) {
                    console.error("Login Failed:", xhr.responseText);
                    setLoading($submitBtn, false);
                    showAlert('.auth-form', 'danger', xhr.responseJSON?.message || 'Invalid email or password.');
                }
            });
        });
    }

    // ==========================================================================
    // FORGOT PASSWORD FORM FUNCTIONALITY
    // ==========================================================================
    const $forgotForm = $('#forgotPasswordForm');
    if ($forgotForm.length) {
        $forgotForm.on('submit', function(e) {
            e.preventDefault();
            const email = $('#forgotEmail').val().trim();
            const $submitBtn = $(this).find('button[type="submit"]');

            if (!isValidEmail(email)) {
                showAlert('.auth-form', 'danger', 'Please enter a valid email address');
                return;
            }

            $('.auth-form .alert').remove();
            setLoading($submitBtn, true, 'Sending reset link...');

            $.ajax({
                url: `${API_BASE_URL}/forgot-password/`,
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ email: email }),
                headers: { 'X-CSRFToken': getCSRFToken() },
                success: function(response) {
                    $('.form-content').fadeOut(300, () => $('.success-message').fadeIn(300));
                    setLoading($submitBtn, false);
                },
                error: function(xhr) {
                    console.error("Forgot Password Failed:", xhr.responseText);
                    setLoading($submitBtn, false);
                    showAlert('.auth-form', 'danger', xhr.responseJSON?.message || 'Failed to send reset email.');
                }
            });
        });
    }

    // ==========================================================================
    // AUTHENTICATION STATE MANAGEMENT
    // ==========================================================================
    const currentPage = window.location.pathname;
    const isLoggedIn = getAuthToken() !== null;
    const protectedPages = ['/user/dashboard/', '/admin/dashboard/', '/profile/', '/settings/'];
    const authPages = ['/login/', '/signup/', '/forgot-password/'];

    // Redirect to login if on protected page but not logged in
    if (protectedPages.some(page => currentPage.startsWith(page)) && !isLoggedIn) {
        window.location.href = '/login/?redirect=' + encodeURIComponent(currentPage);
    }

    // Redirect to user dashboard if logged in and on auth page
    if (authPages.includes(currentPage) && isLoggedIn) {
        window.location.href = '/user/dashboard/'; // FIXED
    }

    console.log('✅ NodexPlatform authentication script loaded successfully!');
});