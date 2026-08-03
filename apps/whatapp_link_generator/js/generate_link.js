$(document).ready(function() {

  // Real-time chat preview
  $('#message').on('input', function() {
    let text = $(this).val().trim();
    if (text !== '') {
      $('#previewText').removeClass('fst-italic text-muted').text(text);
    } else {
      $('#previewText').addClass('fst-italic text-muted').text('Your message preview will appear here...');
    }
  });

  // Form Submission
  $('#waForm').on('submit', function(e) {
    e.preventDefault();

    let rawPhone = $('#phone').val();
    let cleanPhone = rawPhone.replace(/[^0-9]/g, '');

    if (!cleanPhone) {
      alert('Please enter a valid phone number with your country code.');
      $('#phone').focus();
      return;
    }

    let message = $('#message').val().trim();
    let encodedMessage = encodeURIComponent(message);

    let waUrl = `https://wa.me/${cleanPhone}`;
    if (encodedMessage !== '') {
      waUrl += `?text=${encodedMessage}`;
    }

    $('#generatedLink').val(waUrl);
    $('#testLink').attr('href', waUrl);
    $('#outputSection').slideDown();
  });

  // Copy to Clipboard
  $('#copyBtn').on('click', function() {
    let linkInput = $('#generatedLink');
    linkInput.select();
    document.execCommand('copy');

    let originalBtnText = $(this).html();
    $(this).html('<i class="fa-solid fa-check me-1"></i> Copied!').addClass('btn-success').removeClass('btn-outline-secondary');

    setTimeout(() => {
      $(this).html(originalBtnText).removeClass('btn-success').addClass('btn-outline-secondary');
    }, 2000);
  });

});