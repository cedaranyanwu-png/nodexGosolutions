 // Initialize AOS
        AOS.init({
            duration: 1000,
            once: false,
            mirror: true
        });

        // Scroll Progress Bar
        window.onscroll = function() {
            let winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            let height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            let scrolled = (winScroll / height) * 100;
            document.getElementById("scroll-progress").style.width = scrolled + "%";
        };

        // --- Basic Three.js 3D Background (Stars/Particles) ---
        let scene, camera, renderer, stars, starGeo;

        function init3D() {
            scene = new THREE.Scene();
            camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 1, 1000);
            camera.position.z = 1;
            camera.rotation.x = Math.PI/2;

            renderer = new THREE.WebGLRenderer({canvas: document.getElementById('background-canvas'), antialias: true, alpha: true});
            renderer.setSize(window.innerWidth, window.innerHeight);
            renderer.setClearColor(0x0a0a12, 1);

            // Create particles (stars)
            starGeo = new THREE.BufferGeometry();
            let positions = [];
            let velocities = [];
            for(let i=0; i<6000; i++) {
                positions.push(Math.random() * 600 - 300);
                positions.push(Math.random() * 600 - 300);
                positions.push(Math.random() * 600 - 300);
                velocities.push(0);
            }
            starGeo.setAttribute('position', new THREE.Float32BufferAttribute(positions, 3));
            starGeo.setAttribute('velocity', new THREE.Float32BufferAttribute(velocities, 1));

            let starMaterial = new THREE.PointsMaterial({
                color: 0xaaaaaa,
                size: 0.7,
                transparent: true
            });

            stars = new THREE.Points(starGeo, starMaterial);
            scene.add(stars);

            window.addEventListener('resize', onWindowResize, false);
            animate3D();
        }

        function onWindowResize() {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        }

        function animate3D() {
            const positionAttribute = starGeo.getAttribute('position');
            const velocityAttribute = starGeo.getAttribute('velocity');

            for (let i = 0; i < positionAttribute.count; i++) {
                let p = positionAttribute.getY(i);
                let v = velocityAttribute.getX(i);

                v += 0.02;
                p -= v;

                if (p < -200) {
                    p = 200;
                    v = 0;
                }
                positionAttribute.setY(i, p);
                velocityAttribute.setX(i, v);
            }
            positionAttribute.needsUpdate = true;

            stars.rotation.y += 0.002;

            renderer.render(scene, camera);
            requestAnimationFrame(animate3D);
        }

        init3D();