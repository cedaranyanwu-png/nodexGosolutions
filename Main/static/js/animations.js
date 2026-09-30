/**
 * NodexPlatform Animation Script
 * Professional scroll-triggered animations using Intersection Observer
 */

document.addEventListener('DOMContentLoaded', function() {

    // ==========================================================================
    // 1. SCROLL-TRIGGERED ANIMATIONS (Intersection Observer)
    // ==========================================================================

    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const animationObserver = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                // Optional: Stop observing after animation triggers
                // animationObserver.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Observe all animated elements
    const animatedElements = document.querySelectorAll(
        '.fade-in-up, .fade-in-down, .fade-in-left, .fade-in-right, .fade-in-scale'
    );

    animatedElements.forEach(element => {
        animationObserver.observe(element);
    });

    // ==========================================================================
    // 2. COUNTER ANIMATION (Stats Section)
    // ==========================================================================

    const counters = document.querySelectorAll('.counter');
    const counterSpeed = 200; // Lower = faster

    const counterObserver = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                const target = +counter.getAttribute('data-target');
                const increment = target / counterSpeed;

                const updateCount = () => {
                    const count = +counter.innerText;

                    if (count < target) {
                        counter.innerText = Math.ceil(count + increment);
                        setTimeout(updateCount, 10);
                    } else {
                        counter.innerText = target;
                    }
                };

                updateCount();
                counterObserver.unobserve(counter);
            }
        });
    }, { threshold: 0.5 });

    counters.forEach(counter => {
        counterObserver.observe(counter);
    });

    // ==========================================================================
    // 3. NAVBAR SCROLL EFFECT
    // ==========================================================================

    const navbar = document.querySelector('.navbar');

    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }

    // ==========================================================================
    // 4. SMOOTH SCROLL FOR ANCHOR LINKS
    // ==========================================================================

    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');

            // Skip if it's just "#"
            if (href === '#') return;

            e.preventDefault();

            const target = document.querySelector(href);
            if (target) {
                const offsetTop = target.offsetTop - 80; // Account for fixed navbar

                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });

                // Close mobile menu if open
                const navbarCollapse = document.querySelector('.navbar-collapse');
                if (navbarCollapse && navbarCollapse.classList.contains('show')) {
                    const bsCollapse = new bootstrap.Collapse(navbarCollapse);
                    bsCollapse.hide();
                }
            }
        });
    });

    // ==========================================================================
    // 5. ACTIVE NAV LINK HIGHLIGHTING
    // ==========================================================================

    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');

    window.addEventListener('scroll', function() {
        let current = '';

        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;

            if (window.scrollY >= (sectionTop - 100)) {
                current = section.getAttribute('id');
            }
        });

        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#${current}`) {
                link.classList.add('active');
            }
        });
    });

    // ==========================================================================
    // 6. PARALLAX EFFECT (Optional - for hero section)
    // ==========================================================================

    const heroSection = document.querySelector('.hero-section');

    if (heroSection) {
        window.addEventListener('scroll', function() {
            const scrolled = window.scrollY;
            const parallaxElements = heroSection.querySelectorAll('.fade-in-up, .fade-in-right');

            parallaxElements.forEach(element => {
                const speed = 0.5;
                element.style.transform = `translateY(${scrolled * speed}px)`;
            });
        });
    }

    // ==========================================================================
    // 7. LAZY LOADING IMAGES (If you add images later)
    // ==========================================================================

    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.classList.add('loaded');
                        imageObserver.unobserve(img);
                    }
                }
            });
        });

        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }

    // ==========================================================================
    // 8. FORM ANIMATIONS
    // ==========================================================================

    const forms = document.querySelectorAll('form');

    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"]');

            if (submitBtn) {
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
                submitBtn.disabled = true;

                // Re-enable after 3 seconds (for demo purposes)
                setTimeout(() => {
                    submitBtn.innerHTML = '✓ Submitted!';
                    submitBtn.classList.remove('btn-warning');
                    submitBtn.classList.add('btn-success');
                }, 3000);
            }
        });
    });

    // ==========================================================================
    // 9. SCROLL TO TOP BUTTON (Optional enhancement)
    // ==========================================================================

    // Create scroll to top button
    const scrollToTopBtn = document.createElement('button');
    scrollToTopBtn.innerHTML = '<i class="bi bi-arrow-up"></i>';
    scrollToTopBtn.className = 'btn btn-primary rounded-circle position-fixed';
    scrollToTopBtn.style.cssText = `
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        display: none;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        z-index: 1000;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        transition: all 0.3s ease;
    `;

    document.body.appendChild(scrollToTopBtn);

    // Show/hide based on scroll position
    window.addEventListener('scroll', function() {
        if (window.scrollY > 300) {
            scrollToTopBtn.style.display = 'flex';
        } else {
            scrollToTopBtn.style.display = 'none';
        }
    });

    // Scroll to top on click
    scrollToTopBtn.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    // ==========================================================================
    // 10. TYPING EFFECT (Optional - for hero section)
    // ==========================================================================

    const typingElement = document.querySelector('.typing-text');

    if (typingElement) {
        const text = typingElement.textContent;
        typingElement.textContent = '';
        let i = 0;

        const typeWriter = () => {
            if (i < text.length) {
                typingElement.textContent += text.charAt(i);
                i++;
                setTimeout(typeWriter, 50);
            }
        };

        // Start typing when element is visible
        const typingObserver = new IntersectionObserver(function(entries) {
            if (entries[0].isIntersecting) {
                typeWriter();
                typingObserver.unobserve(typingElement);
            }
        });

        typingObserver.observe(typingElement);
    }

    // ==========================================================================
    // 11. CARD TILT EFFECT (Optional enhancement)
    // ==========================================================================

    const tiltCards = document.querySelectorAll('.feature-card, .service-card, .pricing-card');

    tiltCards.forEach(card => {
        card.addEventListener('mousemove', function(e) {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            const centerX = rect.width / 2;
            const centerY = rect.height / 2;

            const rotateX = (y - centerY) / 20;
            const rotateY = (centerX - x) / 20;

            card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-10px)`;
        });

        card.addEventListener('mouseleave', function() {
            card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) translateY(0)';
        });
    });

    // ==========================================================================
    // 12. PRELOADER (Optional - uncomment if needed)
    // ==========================================================================

    /*
    window.addEventListener('load', function() {
        const preloader = document.querySelector('.preloader');
        if (preloader) {
            preloader.style.opacity = '0';
            setTimeout(() => {
                preloader.style.display = 'none';
            }, 500);
        }
    });
    */

    console.log('✅ NodexPlatform animations initialized successfully!');
});