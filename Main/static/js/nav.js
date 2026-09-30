// static/js/nav_portals.js

$(document).ready(function() {

    // 1. Helper: Get CSRF token (Django's official, bulletproof method)
    function getCSRFToken() {
        let cookieValue = null;
        if (document.cookie && document.cookie !== '') {
            const cookies = document.cookie.split(';');
            for (let i = 0; i < cookies.length; i++) {
                const cookie = cookies[i].trim();
                if (cookie.substring(0, 'csrftoken'.length + 1) === ('csrftoken' + '=')) {
                    cookieValue = decodeURIComponent(cookie.substring('csrftoken'.length + 1));
                    break;
                }
            }
        }
        return cookieValue;
    }

    // 2. Load user data from database
    $.ajax({
        url: '/python/api/auth/me/',
        method: 'GET',
        success: function(response) {
            if (response.success && response.user) {
                const user = response.user;
                const firstName = user.first_name || 'User';
                const lastName = user.last_name || '';
                const email = user.email || 'user@example.com';
                const fullName = `${firstName} ${lastName}`.trim();
                const accountType = user.account_type || 'unspecified';

                const initials = (firstName.charAt(0) + (lastName ? lastName.charAt(0) : '')).toUpperCase();

                $('#userName').text(fullName);
                $('#userNameLarge').text(fullName);
                $('#dashboardUserName').text(firstName);
                $('#userEmail').text(email);
                $('#userEmailLarge').text(email);

                $('#userAvatar').html(initials);
                $('#userAvatarLarge').html(initials);
                $('#dashboardAvatar').html(initials);

                if (user.profile_image) {
                    const imgTag = `<img src="${user.profile_image}" class="rounded-circle" style="width:100%;height:100%;object-fit:cover;">`;
                    $('#userAvatar').html(imgTag);
                    $('#userAvatarLarge').html(imgTag);
                    $('#dashboardAvatar').html(imgTag);
                }

                if ($('#dashboardUserName').length) {
                    let badgeClass = 'bg-info text-white';
                    let badgeText = 'Personal Account';

                    if (accountType === 'business') {
                        badgeClass = 'bg-warning text-dark';
                        badgeText = 'Business Account';
                    } else if (accountType === 'unspecified' || !accountType) {
                        badgeClass = 'bg-secondary text-white';
                        badgeText = 'Setup Required';
                    }

                    $('#dashboardUserName').after(` <span class="badge ${badgeClass} ms-2">${badgeText}</span>`);
                }

                if (accountType === 'unspecified' || !accountType) {
                    const modalElement = document.getElementById('accountTypeModal');
                    if (modalElement) {
                        const accountTypeModal = new bootstrap.Modal(modalElement);
                        accountTypeModal.show();
                    }
                }
            }
        },
        error: function(xhr) {
            console.error('Failed to load user info:', xhr.responseText);
            if (xhr.status === 401) {
                window.location.href = '/login/';
            }
        }
    });

    // 3. Handle Account Type Modal Save
    $('#saveAccountTypeBtn').on('click', function() {
        const $btn = $(this);
        const originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');

        const selectedType = $('input[name="modalAccountType"]:checked').val();
        const csrftoken = getCSRFToken();

        $.ajax({
            url: '/python/api/profile/account-type/',
            method: 'POST',
            // FIXED: Send as standard Form Data, NOT JSON
            data: { account_type: selectedType },
            headers: { 'X-CSRFToken': csrftoken },
            success: function(res) {
                if (res.success) {
                    location.reload();
                } else {
                    alert(res.message || 'Failed to update');
                    $btn.prop('disabled', false).html(originalHtml);
                }
            },
            error: function(xhr) {
                console.error('--- ❌ ACCOUNT TYPE UPDATE FAILED ---');
                console.error('Status:', xhr.status);
                console.error('Response Text:', xhr.responseText);

                let errorMsg = 'An error occurred. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr.status === 403) {
                    errorMsg = 'Security verification failed. Please refresh the page.';
                }

                alert(errorMsg);
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });
});