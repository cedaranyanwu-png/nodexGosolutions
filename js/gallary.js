  AOS.init({ duration: 800, once: true });

        // Scroll Progress Line
        window.onscroll = function() {
            let winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            let height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            let scrolled = (winScroll / height) * 100;
            document.getElementById("scroll-progress").style.width = scrolled + "%";
        };

        // --- FETCH DYNAMIC MEDIA FROM gallery.php ---
        document.addEventListener("DOMContentLoaded", function () {
            fetchGalleryMedia();
        });

        function fetchGalleryMedia() {
            fetch('gallery.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error("gallery.php offline or unreachable.");
                    }
                    return response.json();
                })
                .then(data => {
                    if (data && typeof data === 'object') {
                        console.log("Live gallery media fetched from gallery.php", data);

                        // If gallery.php provides dynamic videos
                        if (data.videos && Array.isArray(data.videos) && data.videos.length > 0) {
                            renderDynamicVideos(data.videos);
                        }

                        // If gallery.php provides dynamic images
                        if (data.images && Array.isArray(data.images) && data.images.length > 0) {
                            renderDynamicImages(data.images);
                        }
                    }
                })
                .catch(error => {
                    console.log("gallery.php missing or offline (" + error.message + "). Displaying default gallery layout.");
                });
        }

        function renderDynamicVideos(videos) {
            const videoGrid = document.getElementById("video-grid");
            if (!videoGrid) return;

            videoGrid.innerHTML = ""; // Clear placeholders and load server videos

            videos.forEach(video => {
                const col = document.createElement("div");
                col.className = "col-lg-6";
                col.innerHTML = `
                    <div class="video-container shadow-lg">
                        <iframe src="https://www.youtube-nocookie.com/embed/${video.youtube_id}" title="${video.title || 'Demo'}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </div>
                    <h5 class="text-white mt-3 mb-1"><i class="fa-brands fa-youtube text-danger me-2"></i> ${video.title}</h5>
                    <p class="text-muted-custom small">${video.description || ''}</p>
                `;
                videoGrid.appendChild(col);
            });
        }

        function renderDynamicImages(images) {
            const streamGrid = document.getElementById("dynamic-gallery-stream");
            if (!streamGrid) return;

            streamGrid.innerHTML = ""; // Clear placeholders

            images.forEach((item, idx) => {
                const imgUrl = typeof item === 'string' ? item : item.url;
                const caption = typeof item === 'object' && item.caption ? item.caption : `System Asset ${idx + 1}`;

                const col = document.createElement("div");
                col.className = "col-md-4";
                col.innerHTML = `
                    <div class="gallery-img-wrapper">
                        <img src="${imgUrl}" class="gallery-img" alt="${caption}" onerror="this.src='https://via.placeholder.com/400x250/0f172a/00d2ff?text=Media+Asset+${idx+1}'">
                        <div class="gallery-caption">${caption}</div>
                    </div>
                `;
                streamGrid.appendChild(col);
            });
        }

        // --- THREE.JS EARTH & SATELLITE CONSTELLATION 3D BACKGROUND ---
        let scene, camera, renderer, earth;
        let satellites = [];

        function initSatellites3D() {
            const container = document.getElementById('canvas-container');
            scene = new THREE.Scene();

            camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
            camera.position.set(0, 0, 26);

            renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            container.appendChild(renderer.domElement);

            // Lighting
            const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
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
            earth.position.set(9, -2, -4);
            scene.add(earth);

            // --- SATELLITE CONSTELLATION ---
            const numSatellites = 12;
            const satelliteGroup = new THREE.Group();

            for (let i = 0; i < numSatellites; i++) {
                const satMesh = new THREE.Group();

                // Central Satellite Hub
                const hubGeo = new THREE.BoxGeometry(0.3, 0.3, 0.5);
                const hubMat = new THREE.MeshStandardMaterial({ color: 0xffffff, metalness: 0.8 });
                const hub = new THREE.Mesh(hubGeo, hubMat);
                satMesh.add(hub);

                // Solar Wings
                const wingGeo = new THREE.BoxGeometry(1.6, 0.04, 0.4);
                const wingMat = new THREE.MeshStandardMaterial({ color: 0x00d2ff, metalness: 0.5 });
                const wing = new THREE.Mesh(wingGeo, wingMat);
                satMesh.add(wing);

                // Orbit properties
                const orbitRadius = 8.8 + (Math.random() * 2.5);
                const orbitSpeed = 0.005 + (Math.random() * 0.008);
                const orbitAngle = (Math.PI * 2 / numSatellites) * i;
                const inclination = (Math.random() - 0.5) * 1.5;

                satellites.push({
                    mesh: satMesh,
                    radius: orbitRadius,
                    speed: orbitSpeed,
                    angle: orbitAngle,
                    inclination: inclination
                });

                satelliteGroup.add(satMesh);
            }

            scene.add(satelliteGroup);

            // Background Starfield
            const starsGeo = new THREE.BufferGeometry();
            const starCount = 1400;
            const positions = new Float32Array(starCount * 3);
            for (let i = 0; i < starCount * 3; i++) {
                positions[i] = (Math.random() - 0.5) * 200;
            }
            starsGeo.setAttribute('position', new THREE.BufferAttribute(positions, 3));
            const starsMat = new THREE.PointsMaterial({ color: 0xffffff, size: 0.4 });
            scene.add(new THREE.Points(starsGeo, starsMat));

            // Window Resize Handler
            window.addEventListener('resize', () => {
                camera.aspect = window.innerWidth / window.innerHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(window.innerWidth, window.innerHeight);
            });

            // Orbital Animation Loop
            function animate() {
                requestAnimationFrame(animate);

                if (earth) earth.rotation.y += 0.002;

                satellites.forEach(sat => {
                    sat.angle += sat.speed;
                    sat.mesh.position.x = earth.position.x + Math.sin(sat.angle) * sat.radius;
                    sat.mesh.position.z = earth.position.z + Math.cos(sat.angle) * sat.radius;
                    sat.mesh.position.y = earth.position.y + Math.sin(sat.angle + sat.inclination) * (sat.radius * 0.3);
                    sat.mesh.rotation.y += 0.02;
                });

                renderer.render(scene, camera);
            }
            animate();
        }

        initSatellites3D();