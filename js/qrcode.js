// qrcode.js
// Handles client-side actions, form submissions, and visual canvas generation with embedded logos.

// Listen for DOM content loading completion
$(document).ready(function() {

  // Hold uploaded logo element reference
  let uploadedLogo = null;

  // Load initial analytics table content
  fetchAnalytics();

  // Radio button type help text toggle change listener
  $('input[name="qr_type"]').on('change', function() {
    // If selected type matches dynamic
    if ($(this).val() === 'dynamic') {
      // Show dynamic info message
      $('#typeHelpText').text('Dynamic QR uses a trackable short URL hosted via PHP backend.');
    } else {
      // Show static info message
      $('#typeHelpText').text('Static QR encodes data directly into the image.');
    }
  });

  // Handle Logo Image Upload & Cache representation
  $('#logoInput').on('change', function(e) {
    // Select uploaded file
    const file = e.target.files[0];
    // Verify file presence
    if (file) {
      // Instantiate new FileReader
      const reader = new FileReader();
      // On loader success
      reader.onload = function(event) {
        // Create new Image object
        const img = new Image();
        // Upon image rendering completion
        img.onload = function() {
          // Set cache element
          uploadedLogo = img;
        };
        // Set raw data URI sources
        img.src = event.target.result;
      };
      // Read selected image file as data URL representation
      reader.readAsDataURL(file);
    } else {
      // Reset cache if empty
      uploadedLogo = null;
    }
  });

  // Intercept generate form submission action
  $('#qrForm').on('submit', function(e) {
    // Prevent normal browser page reload
    e.preventDefault();

    // Fetch active selected qr type
    const qrType = $('input[name="qr_type"]:checked').val();
    // Retrieve target text input value
    const content = $('#qrContent').val().trim();
    // Locate feedback alert container
    const errorAlert = $('#errorAlert');

    // Hide existing error panels
    errorAlert.hide().text('');

    // Abort if no content was provided
    if (!content) return;

    // Process dynamic code request
    if (qrType === 'dynamic') {
      // Transition generate button to loading state
      $('#generateBtn').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i> Processing...');

      // Execute ajax post requests to qr register backend
      $.ajax({
        // Target dynamic creation action path under root php directory
        url: '/php/qrcode.php?action=create_dynamic',
        type: 'POST',
        dataType: 'json',
        data: { target_url: content },
        // Handle success response scenario
        success: function(res) {
          // Verify response flag success status
          if (res.success) {
            // Render QR code image on canvas using generated trackable short link
            renderQRCanvas(res.qr_url);
            // Output code badges and redirect details in UI
            $('#qrMetaInfo').html(`<span class="badge bg-success mb-1">Dynamic Code: ${res.code}</span><br>Redirects to: ${content}`);
            // Fetch updated analytics metrics list
            fetchAnalytics();
          } else {
            // Display error details returned from server
            errorAlert.text(res.error || 'Failed to create dynamic code.').slideDown();
          }
        },
        // Handle request communications failure
        error: function() {
          // Display connection error fallback alert
          errorAlert.text('Failed to connect to backend server.').slideDown();
        },
        // Execute post action completions
        complete: function() {
          // Restore button back to standard active state
          $('#generateBtn').prop('disabled', false).html('<i class="fa-solid fa-gear me-2"></i> Generate QR Code');
        }
      });
    } else {
      // Direct static QR code canvas rendering
      renderQRCanvas(content);
      // Display static code details badge
      $('#qrMetaInfo').html(`<span class="badge bg-secondary mb-1">Static QR</span><br>Encodes: ${content}`);
    }
  });

  // Draw QR Code onto Canvas with embedded logo overlay
  function renderQRCanvas(textToEncode) {
    // Retrieve canvas target handle
    const canvas = document.getElementById('qrCanvas');
    // Retrieve 2D drawing context
    const ctx = canvas.getContext('2d');

    // Clear previous canvas redraw cycles
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Temp container for QRCode.js engine
    const tempDiv = document.createElement('div');
    // Instantiate new QRCode instance
    new QRCode(tempDiv, {
      text: textToEncode,
      width: 260,
      height: 260,
      correctLevel: QRCode.CorrectLevel.H // 30% Error Correction for logo space
    });

    // Wait for internal image render
    setTimeout(() => {
      // Query generated image or canvas child elements
      const qrImg = tempDiv.querySelector('img') || tempDiv.querySelector('canvas');
      // If generated image context exists
      if (qrImg) {
        // Draw the base QR Code image onto canvas
        ctx.drawImage(qrImg, 0, 0, 260, 260);

        // Overlay Logo in Center if available
        if (uploadedLogo) {
          // Define logo overlay dimensions
          const logoSize = 60;
          // Calculate center coordinates
          const x = (canvas.width - logoSize) / 2;
          const y = (canvas.height - logoSize) / 2;

          // Draw White background padding circle behind logo to mask base QR bars
          ctx.fillStyle = '#ffffff';
          ctx.beginPath();
          ctx.arc(canvas.width / 2, canvas.height / 2, (logoSize / 2) + 4, 0, 2 * Math.PI);
          ctx.fill();

          // Draw cached logo image onto canvas center
          ctx.drawImage(uploadedLogo, x, y, logoSize, logoSize);
        }

        // Enable download button once rendering concludes
        $('#downloadBtn').prop('disabled', false);
      }
    }, 100);
  }

  // Download Canvas image as standard PNG file
  $('#downloadBtn').on('click', function() {
    // Fetch current canvas element
    const canvas = document.getElementById('qrCanvas');
    // Export raw data URI representation
    const imageURI = canvas.toDataURL('image/png');

    // Create virtual click anchor element
    const link = document.createElement('a');
    link.download = 'business-qrcode.png';
    link.href = imageURI;
    document.body.appendChild(link);
    // Invoke trigger click
    link.click();
    // Remove element
    document.body.removeChild(link);
  });

  // Wire refresh button actions
  $('#refreshAnalyticsBtn').on('click', fetchAnalytics);

  // Fetch Analytics Data
  function fetchAnalytics() {
    // Run asynchronous fetch requests targeting centralized endpoint URL under root php directory
    $.ajax({
      url: '/php/qrcode.php?action=get_analytics',
      type: 'GET',
      dataType: 'json',
      // Handle successful retrieval response
      success: function(res) {
        // Verify response flag status
        if (res.success && res.data) {
          // Locate body element
          const tbody = $('#analyticsBody');
          // Clear current content
          tbody.empty();

          // Capture list of codes
          const codes = Object.keys(res.data);
          // Handle empty database case
          if (codes.length === 0) {
            tbody.html('<tr><td colspan="4" class="text-center text-muted">No dynamic codes generated yet.</td></tr>');
            return;
          }

          // Build row elements iteratively
          codes.forEach(code => {
            const item = res.data[code];
            const row = `
              <tr>
                <td><code>${code}</code></td>
                <td class="text-truncate" style="max-width: 200px;">${item.target_url}</td>
                <td class="text-center"><span class="badge bg-primary rounded-pill">${item.scans}</span></td>
                <td class="text-end text-muted small">${item.last_scan || 'Never'}</td>
              </tr>
            `;
            // Append row context
            tbody.append(row);
          });
        }
      }
    });
  }

});
