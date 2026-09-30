/**
 * NodexPlatform - Pages jQuery Script
 * Handles Terms, Privacy, and other page interactions
 * Connects to Python backend at /python/ endpoints
 */

$(document).ready(function() {

    const API_BASE_URL = '/python/api';

    // ==========================================================================
    // UTILITY FUNCTIONS
    // ==========================================================================

    function getCSRFToken() {
        return $('input[name="csrfmiddlewaretoken"]').val() || getCookie('csrftoken');
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

    function showAlert(container, type, message, duration = 5000) {
        const alertId = 'alert-' + Date.now();
        const alertHtml = `
            <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        $(container).prepend(alertHtml);

        if (duration > 0) {
            setTimeout(function() {
                $(`#${alertId}`).alert('close');
            }, duration);
        }
    }

    // ==========================================================================
    // LEGAL PAGES (Terms & Privacy)
    // ==========================================================================

    const $legalContent = $('.legal-content');

    if ($legalContent.length) {

        // Generate Table of Contents
        generateTableOfContents();

        // Smooth scrolling
        $('.toc-link').on('click', function(e) {
            e.preventDefault();
            const target = $(this).attr('href');

            $('html, body').animate({
                scrollTop: $(target).offset().top - 100
            }, 600);
        });

        // Highlight active section
        $(window).on('scroll', function() {
            const scrollPos = $(this).scrollTop() + 120;

            $('.legal-content h2').each(function() {
                const $section = $(this);
                const sectionTop = $section.offset().top;
                const sectionBottom = sectionTop + $section.outerHeight();
                const id = $section.attr('id');

                if (scrollPos >= sectionTop && scrollPos < sectionBottom) {
                    $('.toc-link').removeClass('active');
                    $(`.toc-link[href="#${id}"]`).addClass('active');
                }
            });
        });

        // Print functionality
        $('.print-btn').on('click', function(e) {
            e.preventDefault();
            window.print();
        });

        // Copy section link
        $('.legal-content h2').each(function() {
            const $heading = $(this);
            const id = $heading.attr('id');

            if (id) {
                const $copyLink = $('<a href="#" class="copy-link ms-2 text-muted small" title="Copy link to section"><i class="bi bi-link-45deg"></i></a>');

                $copyLink.on('click', function(e) {
                    e.preventDefault();
                    const url = window.location.origin + window.location.pathname + '#' + id;

                    navigator.clipboard.writeText(url).then(function() {
                        $copyLink.attr('title', 'Copied!');
                        setTimeout(function() {
                            $copyLink.attr('title', 'Copy link to section');
                        }, 2000);
                    });
                });

                $heading.append($copyLink);
            }
        });

        // Mobile collapsible sections
        if ($(window).width() < 768) {
            $('.legal-content h2').each(function() {
                const $heading = $(this);
                const $content = $heading.nextUntil('h2');

                $heading.css('cursor', 'pointer').append(' <i class="bi bi-chevron-down float-end"></i>');

                $heading.on('click', function() {
                    $content.slideToggle(300);
                    $(this).find('.bi-chevron-down, .bi-chevron-up').toggleClass('bi-chevron-down bi-chevron-up');
                });
            });
        }
    }

    // ==========================================================================
    // NEWSLETTER SUBSCRIPTION
    // ==========================================================================

    const $newsletterForm = $('#newsletterForm');

    if ($newsletterForm.length) {
        $newsletterForm.on('submit', function(e) {
            e.preventDefault();

            const email = $(this).find('input[type="email"]').val().trim();
            const $submitBtn = $(this).find('button[type="submit"]');

            if (!email) {
                showAlert('#newsletter', 'danger', 'Please enter your email address');
                return;
            }

            $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

            $.ajax({
                url: `${API_BASE_URL}/newsletter/subscribe/`,
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ email: email }),
                headers: {
                    'X-CSRFToken': getCSRFToken()
                },
                success: function(response) {
                    showAlert('#newsletter', 'success', response.message || 'Successfully subscribed!');
                    $newsletterForm[0].reset();
                },
                error: function(xhr) {
                    showAlert('#newsletter', 'danger', xhr.responseJSON?.message || 'Subscription failed. Please try again.');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).html('Subscribe');
                }
            });
        });
    }

    // ==========================================================================
    // CONTACT FORM
    // ==========================================================================

    const $contactForm = $('#contactForm');

    if ($contactForm.length) {
        $contactForm.on('submit', function(e) {
            e.preventDefault();

            const formData = {
                name: $('#contactName').val().trim(),
                email: $('#contactEmail').val().trim(),
                subject: $('#contactSubject').val().trim(),
                message: $('#contactMessage').val().trim()
            };

            const $submitBtn = $(this).find('button[type="submit"]');

            if (!formData.name || !formData.email || !formData.message) {
                showAlert('#contact', 'danger', 'Please fill in all required fields');
                return;
            }

            $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Sending...');

            $.ajax({
                url: `${API_BASE_URL}/contact/submit/`,
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                headers: {
                    'X-CSRFToken': getCSRFToken()
                },
                success: function(response) {
                    showAlert('#contact', 'success', response.message || 'Message sent successfully! We\'ll get back to you soon.');
                    $contactForm[0].reset();
                },
                error: function(xhr) {
                    showAlert('#contact', 'danger', xhr.responseJSON?.message || 'Failed to send message. Please try again.');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).html('Send Message');
                }
            });
        });
    }

    // ==========================================================================
    // TABLE OF CONTENTS GENERATOR
    // ==========================================================================

    function generateTableOfContents() {
        const $toc = $('<div class="toc-container bg-light p-4 rounded-3 mb-4"><h5 class="mb-3"><i class="bi bi-list-ul me-2"></i>Table of Contents</h5><ul class="list-unstyled mb-0"></ul></div>');
        const $tocList = $toc.find('ul');

        $('.legal-content h2').each(function(index) {
            const $heading = $(this);
            const text = $heading.text().replace(/Copy link to section/g, '').trim();
            const id = 'section-' + (index + 1);

            if (!$heading.attr('id')) {
                $heading.attr('id', id);
            }

            const headingId = $heading.attr('id');
            const $item = $(`<li class="mb-2"><a href="#${headingId}" class="toc-link text-decoration-none text-muted">${text}</a></li>`);
            $tocList.append($item);
        });

        $('.last-updated').after($toc);
    }

    // ==========================================================================
    // BACK TO TOP BUTTON
    // ==========================================================================

    const $backToTop = $('<button class="btn btn-primary rounded-circle position-fixed" style="bottom: 30px; right: 30px; width: 50px; height: 50px; display: none; align-items: center; justify-content: center; font-size: 1.5rem; z-index: 1000; box-shadow: 0 5px 15px rgba(0,0,0,0.3);"><i class="bi bi-arrow-up"></i></button>');

    $('body').append($backToTop);

    $(window).on('scroll', function() {
        if ($(this).scrollTop() > 300) {
            $backToTop.fadeIn();
        } else {
            $backToTop.fadeOut();
        }
    });

    $backToTop.on('click', function() {
        $('html, body').animate({ scrollTop: 0 }, 600);
    });

    // ==========================================================================
    // KEYBOARD SHORTCUTS
    // ==========================================================================

    $(document).on('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'p' && $legalContent.length) {
            e.preventDefault();
            window.print();
        }

        if (e.key === 'Escape') {
            $('.alert').alert('close');
        }
    });

    console.log('✅ NodexPlatform pages script loaded successfully!');
});