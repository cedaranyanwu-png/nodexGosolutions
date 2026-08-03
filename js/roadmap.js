  AOS.init({ duration: 800, once: true });

        // Scroll Progress Bar
        window.onscroll = function() {
            let winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            let height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            let scrolled = (winScroll / height) * 100;
            document.getElementById("scroll-progress").style.width = scrolled + "%";
        };

        // --- FETCH DYNAMIC ROADMAP DATA FROM roadmap.php ---
        document.addEventListener("DOMContentLoaded", function () {
            fetchRoadmapData();
        });

        function fetchRoadmapData() {
            fetch('roadmap.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error("roadmap.php unreachable or offline.");
                    }
                    return response.json();
                })
                .then(data => {
                    if (data && Array.isArray(data) && data.length > 0) {
                        renderDynamicTimeline(data);
                    } else {
                        console.warn("roadmap.php returned empty data. Displaying fallback corporate timeline.");
                    }
                })
                .catch(error => {
                    console.log("roadmap.php missing or offline (" + error.message + "). Displaying default corporate timeline.");
                });
        }

        function renderDynamicTimeline(timelineItems) {
            const container = document.getElementById("roadmap-timeline");
            container.innerHTML = ""; // Clear default hardcoded nodes if PHP returns live items

            timelineItems.forEach((phase, index) => {
                const node = document.createElement("div");
                node.className = "timeline-node";
                node.setAttribute("data-aos", "fade-up");
                node.setAttribute("data-aos-delay", `${(index + 1) * 100}`);

                let pointsHtml = "";
                if (phase.points && Array.isArray(phase.points)) {
                    phase.points.forEach(point => {
                        pointsHtml += `<li><i class="fa-solid fa-angle-right text-warning me-2"></i> ${point}</li>`;
                    });
                }

                node.innerHTML = `
                    <div class="glass-card">
                        <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
                            <span class="badge ${phase.badge_class || 'bg-warning text-dark'} fw-bold px-3 py-2 rounded-pill mb-1">
                                ${phase.phase_label}
                            </span>
                            <span class="text-warning fw-bold">${phase.timeframe}</span>
                        </div>
                        <h3 class="text-white mb-3">${phase.title}</h3>
                        <ul class="text-muted-custom mb-0 d-flex flex-column gap-2">
                            ${pointsHtml}
                        </ul>
                    </div>
                `;
                container.appendChild(node);
            });
        }

        // --- THREE.JS SATURN BACKGROUND LAYER ---
        let scene, camera, renderer, saturnGroup;

        function initSaturn3D() {
            const container = document.getElementById('canvas-container');
            scene = new THREE.Scene();

            camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 1000);
            camera.position.set(0, 4, 28);

            renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            container.appendChild(renderer.domElement);

            // Lights
            const ambientLight = new THREE.AmbientLight(0xffffff, 0.7);
            scene.add(ambientLight);

            const sunLight = new THREE.DirectionalLight(0xffdf9e, 2.0);
            sunLight.position.set(40, 20, 20);
            scene.add(sunLight);

            // Group for Saturn + Rings
            saturnGroup = new THREE.Group();

            // Saturn Sphere
            const sphereGeo = new THREE.SphereGeometry(5.5, 32, 32);
            const sphereMat = new THREE.MeshStandardMaterial({
                color: 0xe2b04f,
                roughness: 0.7
            });
            const saturnBody = new THREE.Mesh(sphereGeo, sphereMat);
            saturnGroup.add(saturnBody);

            // Saturn Rings (Inner Radius 7.0, Outer Radius 11.5)
            const ringGeo = new THREE.RingGeometry(7.0, 11.5, 64);
            const ringMat = new THREE.MeshStandardMaterial({
                color: 0xc49b45,
                side: THREE.DoubleSide,
                transparent: true,
                opacity: 0.85,
                roughness: 0.5
            });
            const saturnRings = new THREE.Mesh(ringGeo, ringMat);
            saturnRings.rotation.x = Math.PI / 2; // Lay rings flat relative to planet
            saturnGroup.add(saturnRings);

            // Tilt Saturn Group on axis
            saturnGroup.rotation.z = Math.PI / 7;
            saturnGroup.rotation.x = Math.PI / 8;
            saturnGroup.position.set(12, -1, -5); // Positioned slightly right

            scene.add(saturnGroup);

            // Background Star Field
            const starsGeo = new THREE.BufferGeometry();
            const starCount = 1200;
            const positions = new Float32Array(starCount * 3);
            for (let i = 0; i < starCount * 3; i++) {
                positions[i] = (Math.random() - 0.5) * 220;
            }
            starsGeo.setAttribute('position', new THREE.BufferAttribute(positions, 3));
            const starsMat = new THREE.PointsMaterial({ color: 0xffffff, size: 0.45 });
            scene.add(new THREE.Points(starsGeo, starsMat));

            // Window Resize Handler
            window.addEventListener('resize', () => {
                camera.aspect = window.innerWidth / window.innerHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(window.innerWidth, window.innerHeight);
            });

            // Animation Loop
            function animate() {
                requestAnimationFrame(animate);
                if (saturnGroup) {
                    saturnGroup.rotation.y += 0.0025; // Smooth planetary rotation
                }
                renderer.render(scene, camera);
            }
            animate();
        }

        initSaturn3D();
