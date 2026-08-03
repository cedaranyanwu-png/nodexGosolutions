$(document).ready(function() {

  // Handle Form Submission via AJAX
  $('#shortenerForm').on('submit', function(e) {
    e.preventDefault();

    const longUrl = $('#longUrl').val().trim();
    const submitBtn = $('#submitBtn');
    const errorAlert = $('#errorAlert');

    // Reset feedback states
    errorAlert.hide().text('');

    // UI Loading State
    submitBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i> Shortening...');

    // Send AJAX POST Request to PHP
    $.ajax({
      url: '/php/url_shortner.php',
      type: 'POST',
      dataType: 'json',
      data: {
        long_url: longUrl
      },
      success: function(response) {
        if (response.success) {
          $('#shortUrlInput').val(response.short_url);
          $('#visitLink').attr('href', response.short_url);
          $('#resultBox').slideDown();
        } else {
          errorAlert.text(response.error || 'Failed to shorten URL.').slideDown();
        }
      },
      error: function() {
        errorAlert.text('An error occurred while connecting to the server.').slideDown();
      },
      complete: function() {
        // Restore Button State
        submitBtn.prop('disabled', false).html('<i class="fa-solid fa-bolt me-2"></i> Shorten URL');
      }
    });
  });

  // Copy to Clipboard Functionality
  $('#copyBtn').on('click', function() {
    const linkInput = $('#shortUrlInput');
    linkInput.select();
    document.execCommand('copy');

    const originalHtml = $(this).html();
    $(this).html('<i class="fa-solid fa-check me-1"></i> Copied!')
           .addClass('btn-success')
           .removeClass('btn-outline-secondary');

    setTimeout(() => {
      $(this).html(originalHtml)
             .removeClass('btn-success')
             .addClass('btn-outline-secondary');
    }, 2000);
  });

});