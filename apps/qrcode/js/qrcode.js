$(document).ready(function() {

  let uploadedLogo = null;

  // Load initial analytics table
  fetchAnalytics();

  // Radio button type help text toggle
  $('input[name="qr_type"]').on('change', function() {
    if ($(this).val() === 'dynamic') {
      $('#typeHelpText').text('Dynamic QR uses a trackable short URL hosted via PHP backend.');
    } else {
      $('#typeHelpText').text('Static QR encodes data directly into the image.');
    }
  });

  // Handle Logo Image Upload & Cache
  $('#logoInput').on('change', function(e) {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(event) {
        const img = new Image();
        img.onload = function() {
          uploadedLogo = img;
        };
        img.src = event.target.result;
      };
      reader.readAsDataURL(file);
    } else {
      uploadedLogo = null;
    }
  });

  // Form Submit Handler
  $('#qrForm').on('submit', function(e) {
    e.preventDefault();

    const qrType = $('input[name="qr_type"]:checked').val();
    const content = $('#qrContent').val().trim();
    const errorAlert = $('#errorAlert');

    errorAlert.hide().text('');

    if (!content) return;

    if (qrType === 'dynamic') {
      // Send to PHP AJAX to register dynamic shortcode
      $('#generateBtn').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i> Processing...');

      $.ajax({
        url: 'php/qrcode.php?action=create_dynamic',
        type: 'POST',
        dataType: 'json',
        data: { target_url: content },
        success: function(res) {
          if (res.success) {
            renderQRCanvas(res.qr_url);
            $('#qrMetaInfo').html(`<span class="badge bg-success mb-1">Dynamic Code: ${res.code}</span><br>Redirects to: ${content}`);
            fetchAnalytics();
          } else {
            errorAlert.text(res.error || 'Failed to create dynamic code.').slideDown();
          }
        },
        error: function() {
          errorAlert.text('Failed to connect to backend server.').slideDown();
        },
        complete: function() {
          $('#generateBtn').prop('disabled', false).html('<i class="fa-solid fa-gear me-2"></i> Generate QR Code');
        }
      });
    } else {
      // Direct static QR code generation
      renderQRCanvas(content);
      $('#qrMetaInfo').html(`<span class="badge bg-secondary mb-1">Static QR</span><br>Encodes: ${content}`);
    }
  });

  // Draw QR Code onto Canvas with embedded logo overlay
  function renderQRCanvas(textToEncode) {
    const canvas = document.getElementById('qrCanvas');
    const ctx = canvas.getContext('2d');

    // Clear previous canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Temp container for QRCode.js engine
    const tempDiv = document.createElement('div');
    new QRCode(tempDiv, {
      text: textToEncode,
      width: 260,
      height: 260,
      correctLevel: QRCode.CorrectLevel.H // 30% Error Correction for logo space
    });

    // Wait for internal image render
    setTimeout(() => {
      const qrImg = tempDiv.querySelector('img') || tempDiv.querySelector('canvas');
      if (qrImg) {
        ctx.drawImage(qrImg, 0, 0, 260, 260);

        // Overlay Logo in Center if available
        if (uploadedLogo) {
          const logoSize = 60; // Size of center logo
          const x = (canvas.width - logoSize) / 2;
          const y = (canvas.height - logoSize) / 2;

          // Draw White background padding circle behind logo
          ctx.fillStyle = '#ffffff';
          ctx.beginPath();
          ctx.arc(canvas.width / 2, canvas.height / 2, (logoSize / 2) + 4, 0, 2 * Math.PI);
          ctx.fill();

          // Draw Logo
          ctx.drawImage(uploadedLogo, x, y, logoSize, logoSize);
        }

        $('#downloadBtn').prop('disabled', false);
      }
    }, 100);
  }

  // Download Canvas as PNG
  $('#downloadBtn').on('click', function() {
    const canvas = document.getElementById('qrCanvas');
    const imageURI = canvas.toDataURL('image/png');

    const link = document.createElement('a');
    link.download = 'business-qrcode.png';
    link.href = imageURI;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  });

  // Fetch Analytics Data
  $('#refreshAnalyticsBtn').on('click', fetchAnalytics);

  function fetchAnalytics() {
    $.ajax({
      url: 'api.php?action=get_analytics',
      type: 'GET',
      dataType: 'json',
      success: function(res) {
        if (res.success && res.data) {
          const tbody = $('#analyticsBody');
          tbody.empty();

          const codes = Object.keys(res.data);
          if (codes.length === 0) {
            tbody.html('<tr><td colspan="4" class="text-center text-muted">No dynamic codes generated yet.</td></tr>');
            return;
          }

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
            tbody.append(row);
          });
        }
      }
    });
  }

});