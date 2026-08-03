  // Init Animate On Scroll
        AOS.init({ duration: 800, once: true });

        // Scroll Progress Line
        window.onscroll = function() {
            let winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            let height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            let scrolled = (winScroll / height) * 100;
            document.getElementById("scroll-progress").style.width = scrolled + "%";
        };

        // Close mobile nav menu on item click
        const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
        const menuCollapse = document.getElementById('navContent');
        navLinks.forEach((link) => {
            link.addEventListener('click', () => {
                if (menuCollapse.classList.contains('show')) {
                    new bootstrap.Collapse(menuCollapse).toggle();
                }
            });
        });

        // --- Three.js 3D Interactive Solar System & Flying Rocket ---
        let scene, camera, renderer;
        let sun, earth, moon, rocketGroup;

        function init3D() {
            const container = document.getElementById('canvas-container');

            scene = new THREE.Scene();
            camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
            camera.position.set(0, 15, 35);

            renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            container.appendChild(renderer.domElement);

            // Lights
            const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
            scene.add(ambientLight);

            const sunLight = new THREE.PointLight(0xffb703, 2, 100);
            scene.add(sunLight);

            // 1. Sun
            const sunGeo = new THREE.SphereGeometry(3.5, 24, 24);
            const sunMat = new THREE.MeshBasicMaterial({ color: 0xffb703 });
            sun = new THREE.Mesh(sunGeo, sunMat);
            scene.add(sun);

            // 2. Earth
            const earthGeo = new THREE.SphereGeometry(1.5, 24, 24);
            const earthMat = new THREE.MeshStandardMaterial({ color: 0x0077b6, roughness: 0.5 });
            earth = new THREE.Mesh(earthGeo, earthMat);
            scene.add(earth);

            // 3. Moon
            const moonGeo = new THREE.SphereGeometry(0.4, 16, 16);
            const moonMat = new THREE.MeshStandardMaterial({ color: 0xcccccc });
            moon = new THREE.Mesh(moonGeo, moonMat);
            scene.add(moon);

            // 4. Rocket Group
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

            // Background Starfield Particles
            const starsGeo = new THREE.BufferGeometry();
            const starsCount = window.innerWidth < 768 ? 800 : 1800;
            const starPositions = new Float32Array(starsCount * 3);

            for(let i=0; i < starsCount * 3; i++) {
                starPositions[i] = (Math.random() - 0.5) * 250;
            }

            starsGeo.setAttribute('position', new THREE.BufferAttribute(starPositions, 3));
            const starsMat = new THREE.PointsMaterial({ color: 0xffffff, size: 0.5 });
            const starField = new THREE.Points(starsGeo, starsMat);
            scene.add(starField);

            window.addEventListener('resize', onWindowResize);
            animate3D();
        }

        function onWindowResize() {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        }

        let clock = new THREE.Clock();

        function animate3D() {
            requestAnimationFrame(animate3D);

            const elapsedTime = clock.getElapsedTime();

            sun.rotation.y += 0.005;

            const earthOrbitRadius = window.innerWidth < 768 ? 10 : 15;
            earth.position.x = Math.cos(elapsedTime * 0.5) * earthOrbitRadius;
            earth.position.z = Math.sin(elapsedTime * 0.5) * earthOrbitRadius;
            earth.rotation.y += 0.01;

            const moonOrbitRadius = 2.8;
            moon.position.x = earth.position.x + Math.cos(elapsedTime * 2) * moonOrbitRadius;
            moon.position.z = earth.position.z + Math.sin(elapsedTime * 2) * moonOrbitRadius;

            const rocketRadius = window.innerWidth < 768 ? 14 : 20;
            rocketGroup.position.x = Math.sin(elapsedTime * 0.7) * rocketRadius;
            rocketGroup.position.y = Math.cos(elapsedTime * 0.4) * 5;
            rocketGroup.position.z = Math.cos(elapsedTime * 0.7) * rocketRadius;
            rocketGroup.rotation.y = -elapsedTime * 0.7;

            const scrollY = window.scrollY;
            camera.position.y = 15 - scrollY * 0.008;
            camera.position.z = 35 + scrollY * 0.004;

            renderer.render(scene, camera);
        }

        init3D();