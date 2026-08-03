  AOS.init({ duration: 800, once: true });

        // Scroll Progress Bar
        window.onscroll = function() {
            let winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            let height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            let scrolled = (winScroll / height) * 100;
            document.getElementById("scroll-progress").style.width = scrolled + "%";
        };

        // Close mobile nav on selection
        const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
        const menuCollapse = document.getElementById('navContent');
        navLinks.forEach((link) => {
            link.addEventListener('click', () => {
                if (menuCollapse.classList.contains('show')) {
                    new bootstrap.Collapse(menuCollapse).toggle();
                }
            });
        });

        // --- THREE.JS: JUPITER + FLOATING COMPUTER TERMINAL ---
        let scene, camera, renderer;
        let jupiter, computerGroup;

        function createJupiterTexture() {
            const canvas = document.createElement('canvas');
            canvas.width = 1024;
            canvas.height = 512;
            const ctx = canvas.getContext('2d');

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

            return new THREE.CanvasTexture(canvas);
        }

        function init3D() {
            const container = document.getElementById('canvas-container');

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

            // Jupiter Sphere
            const jupiterTexture = createJupiterTexture();
            const jupiterGeo = new THREE.SphereGeometry(7, 48, 48);
            const jupiterMat = new THREE.MeshStandardMaterial({
                map: jupiterTexture,
                roughness: 0.8,
                metalness: 0.1
            });
            jupiter = new THREE.Mesh(jupiterGeo, jupiterMat);
            jupiter.position.set(-12, 2, -5);
            scene.add(jupiter);

            // Moons
            const moonGeo = new THREE.SphereGeometry(0.35, 16, 16);
            const moonMat = new THREE.MeshStandardMaterial({ color: 0xdddddd });

            this.moon1 = new THREE.Mesh(moonGeo, moonMat);
            this.moon2 = new THREE.Mesh(moonGeo, moonMat);
            scene.add(this.moon1);
            scene.add(this.moon2);

            // Floating 3D Computer Model
            computerGroup = new THREE.Group();

            const screenGeo = new THREE.BoxGeometry(4.5, 2.8, 0.2);
            const screenMat = new THREE.MeshStandardMaterial({ color: 0x111827, metalness: 0.8, roughness: 0.2 });
            const screen = new THREE.Mesh(screenGeo, screenMat);
            computerGroup.add(screen);

            const displayGeo = new THREE.PlaneGeometry(4.2, 2.5);
            const displayMat = new THREE.MeshBasicMaterial({ color: 0x00d2ff });
            const display = new THREE.Mesh(displayGeo, displayMat);
            display.position.z = 0.11;
            computerGroup.add(display);

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

            // Space Dust
            const starsGeo = new THREE.BufferGeometry();
            const starsCount = window.innerWidth < 768 ? 900 : 2000;
            const starPositions = new Float32Array(starsCount * 3);

            for (let i = 0; i < starsCount * 3; i++) {
                starPositions[i] = (Math.random() - 0.5) * 250;
            }

            starsGeo.setAttribute('position', new THREE.BufferAttribute(starPositions, 3));
            const starsMat = new THREE.PointsMaterial({ color: 0xffffff, size: 0.6 });
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

            if (jupiter) jupiter.rotation.y += 0.003;

            if (this.moon1 && jupiter) {
                this.moon1.position.x = jupiter.position.x + Math.cos(elapsedTime * 0.8) * 11;
                this.moon1.position.z = jupiter.position.z + Math.sin(elapsedTime * 0.8) * 11;
            }
            if (this.moon2 && jupiter) {
                this.moon2.position.x = jupiter.position.x + Math.cos(elapsedTime * 0.4 + 2) * 14;
                this.moon2.position.z = jupiter.position.z + Math.sin(elapsedTime * 0.4 + 2) * 14;
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

        init3D();