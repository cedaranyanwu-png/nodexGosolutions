/**
 * app.js
 *
 * This is the central modularized JavaScript script orchestrator for nodexGosolutions.
 * It encapsulates all page-specific features (authentication, scroll progress lines,
 * dynamic timeline calculations, and Three.js 3D space visual effects) into dedicated
 * modular functions, and dispatches them on demand based on current page selectors.
 */

// Enable strict evaluation mode
'use strict';

/**
 * 1. INDEX / LANDING PAGE MODULE
 * Implements Solar system orbital simulations and flying rocket animations.
 */
function initIndexModule() {
    // Check if Animate On Scroll is present and initialize it
    if (typeof AOS !== 'undefined') {
        AOS.init({ duration: 800, once: true });
    }

    // Scroll Progress bar percentage updater
    window.addEventListener('scroll', function() {
        let winScroll = document.body.scrollTop || document.documentElement.scrollTop;
        let height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        let scrolled = height > 0 ? (winScroll / height) * 100 : 0;
        const progress = document.getElementById("scroll-progress");
        if (progress) {
            progress.style.width = scrolled + "%";
        }
    });

    // Mobile Navigation auto-collapser on item click
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    const menuCollapse = document.getElementById('navContent');
    navLinks.forEach((link) => {
        link.addEventListener('click', () => {
            if (menuCollapse && menuCollapse.classList.contains('show') && typeof bootstrap !== 'undefined') {
                new bootstrap.Collapse(menuCollapse).toggle();
            }
        });
    });

    // Abort 3D initialization if Three.js library context is missing
    if (typeof THREE === 'undefined') return;

    let scene, camera, renderer;
    let sun, earth, moon, rocketGroup;

    const container = document.getElementById('canvas-container');
    if (!container) return;

    scene = new THREE.Scene();
    camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
    camera.position.set(0, 15, 35);

    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setSize(container.clientWidth || window.innerWidth, container.clientHeight || window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    container.appendChild(renderer.domElement);

    // Dynamic Lights setup
    const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
    scene.add(ambientLight);

    const sunLight = new THREE.PointLight(0xffb703, 2, 100);
    scene.add(sunLight);

    // Create Sun mesh mapping shape and color
    const sunGeo = new THREE.SphereGeometry(3.5, 24, 24);
    const sunMat = new THREE.MeshBasicMaterial({ color: 0xffb703 });
    sun = new THREE.Mesh(sunGeo, sunMat);
    scene.add(sun);

    // Create Earth sphere geometry and materials
    const earthGeo = new THREE.SphereGeometry(1.5, 24, 24);
    const earthMat = new THREE.MeshStandardMaterial({ color: 0x0077b6, roughness: 0.5 });
    earth = new THREE.Mesh(earthGeo, earthMat);
    scene.add(earth);

    // Create Moon orbiter mesh
    const moonGeo = new THREE.SphereGeometry(0.4, 16, 16);
    const moonMat = new THREE.MeshStandardMaterial({ color: 0xcccccc });
    moon = new THREE.Mesh(moonGeo, moonMat);
    scene.add(moon);

    // Construct flying 3D Rocket Group
    rocketGroup = new THREE.Group();

    const bodyGeo = new THREE.CylinderGeometry(0.3, 0.4, 2.5, 12);
    const bodyMat = new THREE.MeshStandardMaterial({ color: 0xeeeeee });
    const rocketBody = new THREE.Mesh(bodyGeo, bodyMat);
    rocketGroup.add(rocketBody);

    const coneGeo = new THREE.ConeGeometry(0.31, 0.8, 12);
    const coneMat = new THREE.MeshStandardMaterial({ color: 0xff2a6d });
    const rocketNose = new THREE.Mesh(coneGeo, coneMat);
    rocketNose.position.y = 1.6;
    rocketGroup.add(rocketNose);

    const finGeo = new THREE.BoxGeometry(1.0, 0.4, 0.1);
    const finMat = new THREE.MeshStandardMaterial({ color: 0x00d2ff });
    const fin = new THREE.Mesh(finGeo, finMat);
    fin.position.y = -0.8;
    rocketGroup.add(fin);

    rocketGroup.rotation.z = Math.PI / 4;
    scene.add(rocketGroup);

    // Create starfield particles backdrop
    const starsGeo = new THREE.BufferGeometry();
    const starsCount = window.innerWidth < 768 ? 800 : 1800;
    const starPositions = new Float32Array(starsCount * 3);

    for (let i = 0; i < starsCount * 3; i++) {
        starPositions[i] = (Math.random() - 0.5) * 250;
    }

    starsGeo.setAttribute('position', new THREE.BufferAttribute(starPositions, 3));
    const starsMat = new THREE.PointsMaterial({ color: 0xffffff, size: 0.5 });
    const starField = new THREE.Points(starsGeo, starsMat);
    scene.add(starField);

    // Resize listener
    window.addEventListener('resize', function() {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    });

    let clock = new THREE.Clock();

    // Recursive 3D animation loop
    function animate3D() {
        requestAnimationFrame(animate3D);

        const elapsedTime = clock.getElapsedTime();
        if (sun) sun.rotation.y += 0.005;

        // Earth orbits the Sun
        const earthOrbitRadius = window.innerWidth < 768 ? 10 : 15;
        if (earth) {
            earth.position.x = Math.cos(elapsedTime * 0.5) * earthOrbitRadius;
            earth.position.z = Math.sin(elapsedTime * 0.5) * earthOrbitRadius;
            earth.rotation.y += 0.01;
        }

        // Moon orbits the Earth
        const moonOrbitRadius = 2.8;
        if (moon && earth) {
            moon.position.x = earth.position.x + Math.cos(elapsedTime * 2) * moonOrbitRadius;
            moon.position.z = earth.position.z + Math.sin(elapsedTime * 2) * moonOrbitRadius;
        }

        // Rocket flight calculations
        const rocketRadius = window.innerWidth < 768 ? 14 : 20;
        if (rocketGroup) {
            rocketGroup.position.x = Math.sin(elapsedTime * 0.7) * rocketRadius;
            rocketGroup.position.y = Math.cos(elapsedTime * 0.4) * 5;
            rocketGroup.position.z = Math.cos(elapsedTime * 0.7) * rocketRadius;
            rocketGroup.rotation.y = -elapsedTime * 0.7;
        }

        // Camera shifts dynamically during user scrolling
        const scrollY = window.scrollY;
        camera.position.y = 15 - scrollY * 0.008;
        camera.position.z = 35 + scrollY * 0.004;

        renderer.render(scene, camera);
    }

    animate3D();
}

/**
 * 2. ABOUT US MODULE
 * Implements dynamic Jupiter textures, orbiting moons, and floating terminal models.
 */
function initAboutModule() {
    if (typeof AOS !== 'undefined') AOS.init({ duration: 800, once: true });

    window.addEventListener('scroll', function() {
        let winScroll = document.body.scrollTop || document.documentElement.scrollTop;
        let height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        let scrolled = height > 0 ? (winScroll / height) * 100 : 0;
        const progress = document.getElementById("scroll-progress");
        if (progress) progress.style.width = scrolled + "%";
    });

    if (typeof THREE === 'undefined') return;

    let scene, camera, renderer;
    let jupiter, computerGroup;
    let moon1, moon2;

    const container = document.getElementById('canvas-container');
    if (!container) return;

    scene = new THREE.Scene();
    camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
    camera.position.set(0, 10, 38);

    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    container.appendChild(renderer.domElement);

    const ambientLight = new THREE.AmbientLight(0xffffff, 0.7);
    scene.add(ambientLight);

    const sunLight = new THREE.DirectionalLight(0xffcc88, 2.0);
    sunLight.position.set(40, 20, 30);
    scene.add(sunLight);

    // Canvas texture generator simulating Jupiter surface storm bands
    const canvas = document.createElement('canvas');
    canvas.width = 1024;
    canvas.height = 512;
    const ctx = canvas.getContext('2d');
    if (ctx) {
        const gradient = ctx.createLinearGradient(0, 0, 0, 512);
        gradient.addColorStop(0.0, '#3d1e10');
        gradient.addColorStop(0.1, '#a6633c');
        gradient.addColorStop(0.25, '#d9a07b');
        gradient.addColorStop(0.35, '#8c4827');
        gradient.addColorStop(0.5, '#e3a880');
        gradient.addColorStop(0.65, '#a6522c');
        gradient.addColorStop(0.8, '#c48962');
        gradient.addColorStop(1.0, '#2e150a');

        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, 1024, 512);

        for (let i = 0; i < 800; i++) {
            ctx.fillStyle = Math.random() > 0.5 ? 'rgba(255, 230, 200, 0.15)' : 'rgba(80, 30, 10, 0.15)';
            let y = Math.random() * 512;
            let h = Math.random() * 8 + 2;
            ctx.fillRect(0, y, 1024, h);
        }

        ctx.beginPath();
        ctx.ellipse(650, 320, 70, 45, 0, 0, Math.PI * 2);
        let spotGrad = ctx.createRadialGradient(650, 320, 5, 650, 320, 70);
        spotGrad.addColorStop(0, '#b82e1f');
        spotGrad.addColorStop(0.7, '#801e14');
        spotGrad.addColorStop(1, 'transparent');
        ctx.fillStyle = spotGrad;
        ctx.fill();
    }

    const jupiterTexture = new THREE.CanvasTexture(canvas);
    const jupiterGeo = new THREE.SphereGeometry(7, 48, 48);
    const jupiterMat = new THREE.MeshStandardMaterial({ map: jupiterTexture, roughness: 0.8 });
    jupiter = new THREE.Mesh(jupiterGeo, jupiterMat);
    jupiter.position.set(-12, 2, -5);
    scene.add(jupiter);

    // Orbiting Moons
    const moonGeo = new THREE.SphereGeometry(0.35, 16, 16);
    const moonMat = new THREE.MeshStandardMaterial({ color: 0xdddddd });
    moon1 = new THREE.Mesh(moonGeo, moonMat);
    moon2 = new THREE.Mesh(moonGeo, moonMat);
    scene.add(moon1);
    scene.add(moon2);

    // Floating Terminal Computer mesh model
    computerGroup = new THREE.Group();
    const screenGeo = new THREE.BoxGeometry(4.5, 2.8, 0.2);
    const screenMat = new THREE.MeshStandardMaterial({ color: 0x111827, metalness: 0.8 });
    const screen = new THREE.Mesh(screenGeo, screenMat);
    computerGroup.add(screen);

    const baseGeo = new THREE.CylinderGeometry(0.8, 1.2, 0.3, 16);
    const baseMat = new THREE.MeshStandardMaterial({ color: 0x374151, metalness: 0.9 });
    const base = new THREE.Mesh(baseGeo, baseMat);
    base.position.set(0, -1.8, 0);
    computerGroup.add(base);

    const neckGeo = new THREE.CylinderGeometry(0.15, 0.15, 0.8, 16);
    const neck = new THREE.Mesh(neckGeo, baseMat);
    neck.position.set(0, -1.3, 0);
    computerGroup.add(neck);

    const kbGeo = new THREE.BoxGeometry(3.8, 0.15, 1.4);
    const kbMat = new THREE.MeshStandardMaterial({ color: 0x1f2937, metalness: 0.5 });
    const keyboard = new THREE.Mesh(kbGeo, kbMat);
    keyboard.position.set(0, -1.8, 1.2);
    keyboard.rotation.x = 0.1;
    computerGroup.add(keyboard);

    computerGroup.position.set(12, 1, 5);
    computerGroup.rotation.y = -0.4;
    scene.add(computerGroup);

    // Resize listener
    window.addEventListener('resize', function() {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    });

    let clock = new THREE.Clock();

    function animate3D() {
        requestAnimationFrame(animate3D);
        const elapsedTime = clock.getElapsedTime();

        if (jupiter) jupiter.rotation.y += 0.003;

        if (moon1 && jupiter) {
            moon1.position.x = jupiter.position.x + Math.cos(elapsedTime * 0.8) * 11;
            moon1.position.z = jupiter.position.z + Math.sin(elapsedTime * 0.8) * 11;
        }
        if (moon2 && jupiter) {
            moon2.position.x = jupiter.position.x + Math.cos(elapsedTime * 0.4 + 2) * 14;
            moon2.position.z = jupiter.position.z + Math.sin(elapsedTime * 0.4 + 2) * 14;
        }

        if (computerGroup) {
            computerGroup.position.y = 1 + Math.sin(elapsedTime * 1.2) * 0.6;
            computerGroup.rotation.y = -0.4 + Math.cos(elapsedTime * 0.8) * 0.08;
        }

        const scrollY = window.scrollY;
        camera.position.y = 10 - scrollY * 0.006;
        camera.position.z = 38 + scrollY * 0.003;

        renderer.render(scene, camera);
    }
    animate3D();
}

/**
 * 3. AUTHENTICATION MODULE (LOGIN & REGISTRATION)
 */
function initAuthModule() {
    if (typeof AOS !== 'undefined') AOS.init({ duration: 800, once: true });

    // Handle Login Forms submissions
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        const alertBox = $('#alertBox');
        const btn = $('#btnLogin');

        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i>Authenticating...');
        alertBox.addClass('d-none').removeClass('alert-success alert-warning alert-danger');

        $.ajax({
            url: '/php/login.php',
            type: 'POST',
            dataType: 'json',
            data: {
                email: $('#loginEmail').val(),
                password: $('#loginPassword').val()
            },
            success: function(response) {
                btn.prop('disabled', false).html('<i class="fa-solid fa-rocket me-2"></i>Authenticate Session');
                alertBox.removeClass('d-none');

                if (response.success === true) {
                    alertBox.addClass('alert-success').html('<i class="fa-solid fa-circle-check me-1"></i> ' + response.message);
                    setTimeout(function() {
                        window.location.href = response.redirect;
                    }, 1200);
                } else {
                    if (response.status === 'unverified') {
                        alertBox.addClass('alert-warning').html('<i class="fa-solid fa-triangle-exclamation me-1"></i> ' + response.message);
                    } else {
                        alertBox.addClass('alert-danger').html('<i class="fa-solid fa-circle-xmark me-1"></i> ' + response.message);
                    }
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fa-solid fa-rocket me-2"></i>Authenticate Session');
                alertBox.removeClass('d-none').addClass('alert-danger');

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    if (xhr.responseJSON.status === 'unverified') {
                        alertBox.removeClass('alert-danger').addClass('alert-warning')
                            .html('<i class="fa-solid fa-triangle-exclamation me-1"></i> ' + xhr.responseJSON.message);
                    } else {
                        alertBox.html('<i class="fa-solid fa-circle-xmark me-1"></i> ' + xhr.responseJSON.message);
                    }
                } else {
                    alertBox.html('<i class="fa-solid fa-wifi me-1"></i> Network connection error. Please try again.');
                }
            }
        });
    });

    // Handle Register Forms submissions
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

    // Ambient Earth standard animation effect for login / registration pages
    if (typeof THREE === 'undefined') return;
    const container = document.getElementById('canvas-container');
    if (!container) return;

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(55, window.innerWidth / window.innerHeight, 0.1, 1000);
    camera.position.set(0, 15, 60);

    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    container.appendChild(renderer.domElement);

    scene.add(new THREE.AmbientLight(0xffffff, 0.5));
    const earthGeo = new THREE.SphereGeometry(25, 48, 48);
    const earthMat = new THREE.MeshStandardMaterial({ color: 0x114477, roughness: 0.7 });
    const earth = new THREE.Mesh(earthGeo, earthMat);
    earth.position.set(0, -32, -10);
    scene.add(earth);

    function animate() {
        requestAnimationFrame(animate);
        camera.position.z += (15 - camera.position.z) * 0.04;
        if (earth) earth.rotation.y += 0x0.0001;
        renderer.render(scene, camera);
    }
    animate();
}

/**
 * 4. ROADMAP TIMELINE MODULE
 */
function initRoadmapModule() {
    if (typeof AOS !== 'undefined') AOS.init({ duration: 800, once: true });

    window.addEventListener('scroll', function() {
        let winScroll = document.body.scrollTop || document.documentElement.scrollTop;
        let height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        let scrolled = height > 0 ? (winScroll / height) * 100 : 0;
        const progress = document.getElementById("scroll-progress");
        if (progress) progress.style.width = scrolled + "%";
    });

    // 3D Saturn representation using Three.js rings and spheres
    if (typeof THREE === 'undefined') return;
    const container = document.getElementById('canvas-container');
    if (!container) return;

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
    camera.position.set(0, 8, 45);

    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    container.appendChild(renderer.domElement);

    scene.add(new THREE.AmbientLight(0xffffff, 0.4));
    const dirLight = new THREE.DirectionalLight(0xffeacc, 1.8);
    dirLight.position.set(30, 20, 25);
    scene.add(dirLight);

    const saturnGeo = new THREE.SphereGeometry(6, 32, 32);
    const saturnMat = new THREE.MeshStandardMaterial({ color: 0xe2bf7d, roughness: 0.8 });
    const saturn = new THREE.Mesh(saturnGeo, saturnMat);
    saturn.position.set(-10, 0, 0);
    scene.add(saturn);

    // Outer Saturn Ring geometry
    const innerRadius = 8;
    const outerRadius = 14;
    const thetaSegments = 64;
    const ringGeo = new THREE.RingGeometry(innerRadius, outerRadius, thetaSegments);
    const ringMat = new THREE.MeshBasicMaterial({ color: 0xa68059, side: THREE.DoubleSide, transparent: true, opacity: 0.8 });
    const ring = new THREE.Mesh(ringGeo, ringMat);
    ring.position.set(-10, 0, 0);
    ring.rotation.x = Math.PI / 2.4;
    scene.add(ring);

    window.addEventListener('resize', function() {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    });

    let clock = new THREE.Clock();

    function animate() {
        requestAnimationFrame(animate);
        const elapsed = clock.getElapsedTime();

        if (saturn) saturn.rotation.y += 0.003;

        const scrollY = window.scrollY;
        camera.position.y = 8 - scrollY * 0.004;
        camera.position.z = 45 + scrollY * 0.002;

        renderer.render(scene, camera);
    }
    animate();
}

/**
 * MAIN CENTRAL SCRIPT DISPATCHER
 * Executes matching page initialization modules based on DOM element hooks.
 */
$(document).ready(function() {
    // Dispatch modules dynamically
    if ($('#loginForm').length > 0 || $('#registerForm').length > 0) {
        initAuthModule();
    } else if ($('#about-hero').length > 0) {
        initAboutModule();
    } else if ($('#roadmap-timeline').length > 0) {
        initRoadmapModule();
    } else if ($('#hero').length > 0) {
        initIndexModule();
    } else {
        // Fallback or general layout animations triggering
        if (typeof AOS !== 'undefined') AOS.init({ duration: 800, once: true });
    }
});
