// register.js
// Controls user registration processes, 3D Canvas visual effects, and form submission flows.

// Initialize AOS (Animate on Scroll) transitions if library context is available
if (typeof AOS !== 'undefined') AOS.init({ duration: 800, once: true });

// Listen for DOM content loading completeness
$(document).ready(function() {
    // Catch the registration Form submit event
    $('#registerForm').on('submit', function(e) {
        // Prevent standard page refresh behavior
        e.preventDefault();

        // Locate visual alerts box container handle
        const alertBox = $('#alertBox');
        // Locate registration submit button handle
        const btn = $('#btnRegister');

        // Transition button state to loading indicator
        btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i>Initializing...');
        // Hide existing alert feedback panels
        alertBox.addClass('d-none').removeClass('alert-success alert-danger');

        // Execute asynchronous POST registration payload
        $.ajax({
            // Target local relative path instead of hardcoded domain
            url: '/main/php/register.php',
            type: 'POST',
            dataType: 'json',
            data: {
                // Pass user full name input value
                fullname: $('#regFullname').val(),
                // Pass user signup email input value
                email: $('#regEmail').val(),
                // Pass user signup password input value
                password: $('#regPassword').val()
            },
            // Handle success response scenario
            success: function(response) {
                // Restore button back to standard active state
                btn.prop('disabled', false).html('<i class="fa-solid fa-user-check me-2"></i>Initialize Account');
                // Display the alert box container
                alertBox.removeClass('d-none');

                // Check if response flag validates successful registration
                if (response.success === true) {
                    // Display success alert message
                    alertBox.addClass('alert-success').html('<i class="fa-solid fa-envelope-circle-check me-1"></i> ' + response.message);
                    // Clear user inputs
                    $('#registerForm')[0].reset();
                } else {
                    // Display server returned validation failure warning message
                    alertBox.addClass('alert-danger').html('<i class="fa-solid fa-triangle-exclamation me-1"></i> ' + response.message);
                }
            },
            // Handle request error failure scenario
            error: function(xhr) {
                // Restore submit button state to active
                btn.prop('disabled', false).html('<i class="fa-solid fa-user-check me-2"></i>Initialize Account');
                // Display danger error alerts panel
                alertBox.removeClass('d-none').addClass('alert-danger');

                // Inspect response JSON structures for descriptive server error messages
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    // Display warning error alert
                    alertBox.html('<i class="fa-solid fa-triangle-exclamation me-1"></i> ' + xhr.responseJSON.message);
                } else {
                    // Provide generic network error fallback messaging
                    alertBox.html('<i class="fa-solid fa-wifi me-1"></i> Server communication error. Please try again.');
                }
            }
        });
    });
});

// Configure 3D Canvas visual effects using Three.js library
window.addEventListener('load', function() {
    // Abort Three.js rendering if library context is missing
    if (typeof THREE === 'undefined') return;
    // Retrieve target canvas container element handle
    const container = document.getElementById('canvas-container');
    // Instantiate Three.js scene environment
    const scene = new THREE.Scene();
    // Configure perspective viewing camera parameters
    const camera = new THREE.PerspectiveCamera(55, window.innerWidth / window.innerHeight, 0.1, 1000);
    // Position the camera within three dimensional space
    camera.position.set(0, 15, 60);

    // Instantiate high quality WebGL renderer
    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    // Set renderer boundaries to fit total screen sizes
    renderer.setSize(window.innerWidth, window.innerHeight);
    // Adjust pixel density parameters
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    // Append the visual canvas to the target container element
    container.appendChild(renderer.domElement);

    // Add ambient light to highlight earth surface textures
    scene.add(new THREE.AmbientLight(0xffffff, 0.5));
    // Define spherical geometry metrics simulating the planet earth
    const earthGeo = new THREE.SphereGeometry(25, 48, 48);
    // Apply shiny metallic mesh standard material textures
    const earthMat = new THREE.MeshStandardMaterial({ color: 0x114477, roughness: 0.7, metalness: 0.1 });
    // Create physical mesh mapping shape and texture variables
    const earth = new THREE.Mesh(earthGeo, earthMat);
    // Reposition the planet Earth in scene coordinates
    earth.position.set(0, -32, -10);
    // Add the Earth model to active scene
    scene.add(earth);

    /**
     * Recursive animation loop function to continuously rotate planet Earth meshes.
     */
    function animate() {
        // Enqueue next animation frame redraw action
        requestAnimationFrame(animate);
        // Gradually smooth camera position shifts
        camera.position.z += (12 - camera.position.z) * 0.04;
        // Increment Earth rotation along the y axis
        earth.rotation.y += 0.0006;
        // Render current perspective scene context
        renderer.render(scene, camera);
    }
    // Launch recursive rendering
    animate();
});
