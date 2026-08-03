  AOS.init({ duration: 800, once: true });

        // Scroll Progress Line
        window.onscroll = function() {
            let winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            let height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            let scrolled = (winScroll / height) * 100;
            document.getElementById("scroll-progress").style.width = scrolled + "%";
        };

        // --- DYNAMIC FETCH PATTERN FOR PORTFOLIO & IMAGES (portfolio.php) ---
        document.addEventListener("DOMContentLoaded", function () {
            fetchPortfolioData();
        });

        function fetchPortfolioData() {
            fetch('portfolio.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error("portfolio.php offline or unreachable.");
                    }
                    return response.json();
                })
                .then(data => {
                    if (data && typeof data === 'object') {
                        console.log("Live portfolio data fetched from portfolio.php");

                        if (data.images && Array.isArray(data.images) && data.images.length > 0) {
                            renderDynamicImages(data.images);
                        }
                    }
                })
                .catch(error => {
                    console.log("portfolio.php missing or offline (" + error.message + "). Displaying default portfolio with image placeholders.");
                });
        }

        function renderDynamicImages(images) {
            const galleryGrid = document.getElementById("dynamic-gallery-grid");
            if (!galleryGrid) return;

            galleryGrid.innerHTML = "";

            images.forEach((imgUrl, idx) => {
                const col = document.createElement("div");
                col.className = "col-md-4";
                col.innerHTML = `
                    <img src="${imgUrl}" alt="Project Media ${idx + 1}" class="portfolio-img" onerror="this.src='https://via.placeholder.com/400x250/1e293b/00d2ff?text=Media+Asset+${idx+1}'">
                `;
                galleryGrid.appendChild(col);
            });
        }

        // --- THREE.JS EARTH & NASA ISS 3D BACKGROUND ---
        let scene, camera, renderer, earth, issGroup;

        function initEarthISS3D() {
            const container = document.getElementById('canvas-container');
            scene = new THREE.Scene();

            camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
            camera.position.set(0, 0, 25);

            renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            container.appendChild(renderer.domElement);

            // Ambient & Sun Directional Lighting
            const ambientLight = new THREE.AmbientLight(0xffffff, 0.7);
            scene.add(ambientLight);

            const sunLight = new THREE.DirectionalLight(0x00d2ff, 1.8);
            sunLight.position.set(30, 20, 20);
            scene.add(sunLight);

            // --- PLANET EARTH ---
            const earthGeo = new THREE.SphereGeometry(6.5, 32, 32);
            const earthMat = new THREE.MeshStandardMaterial({
                color: 0x1d70b8,
                roughness: 0.6,
                wireframe: false
            });
            earth = new THREE.Mesh(earthGeo, earthMat);
            earth.position.set(10, -2, -5);
            scene.add(earth);

            // --- NASA ISS MODEL SYMBOL ---
            issGroup = new THREE.Group();

            const bodyGeo = new THREE.CylinderGeometry(0.3, 0.3, 2.2, 16);
            const bodyMat = new THREE.MeshStandardMaterial({ color: 0xdddddd, metalness: 0.8 });
            const moduleBody = new THREE.Mesh(bodyGeo, bodyMat);
            moduleBody.rotation.z = Math.PI / 2;
            issGroup.add(moduleBody);

            const panelGeo = new THREE.BoxGeometry(3.5, 0.05, 0.8);
            const panelMat = new THREE.MeshStandardMaterial({ color: 0xffb703, metalness: 0.5 });

            const leftPanel = new THREE.Mesh(panelGeo, panelMat);
            leftPanel.position.set(-2.2, 0, 0);
            issGroup.add(leftPanel);

            const rightPanel = new THREE.Mesh(panelGeo, panelMat);
            rightPanel.position.set(2.2, 0, 0);
            issGroup.add(rightPanel);

            scene.add(issGroup);

            // Background Starfield
            const starsGeo = new THREE.BufferGeometry();
            const starCount = 1200;
            const positions = new Float32Array(starCount * 3);
            for (let i = 0; i < starCount * 3; i++) {
                positions[i] = (Math.random() - 0.5) * 200;
            }
            starsGeo.setAttribute('position', new THREE.BufferAttribute(positions, 3));
            const starsMat = new THREE.PointsMaterial({ color: 0xffffff, size: 0.4 });
            scene.add(new THREE.Points(starsGeo, starsMat));

            // Window Resize
            window.addEventListener('resize', () => {
                camera.aspect = window.innerWidth / window.innerHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(window.innerWidth, window.innerHeight);
            });

            // Orbital Animation Variables
            let angle = 0;

            function animate() {
                requestAnimationFrame(animate);

                if (earth) earth.rotation.y += 0.002;

                if (issGroup && earth) {
                    angle += 0.008;
                    const radius = 9.5;
                    issGroup.position.x = earth.position.x + Math.sin(angle) * radius;
                    issGroup.position.z = earth.position.z + Math.cos(angle) * radius;
                    issGroup.position.y = earth.position.y + Math.sin(angle * 0.5) * 2;
                    issGroup.rotation.y += 0.01;
                }

                renderer.render(scene, camera);
            }
            animate();
        }

        initEarthISS3D();