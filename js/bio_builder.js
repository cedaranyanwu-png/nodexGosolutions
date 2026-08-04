$(document).ready(function() {

  // Add initial empty link field
  addLinkRow('My Portfolio', 'https://example.com');

  // Add Link Row Event
  $('#addLinkBtn').on('click', function() {
    addLinkRow('', '');
    updatePreview();
  });

  // Dynamic Row Removal
  $(document).on('click', '.remove-link-btn', function() {
    $(this).closest('.link-row').remove();
    updatePreview();
  });

  // Live Preview Update Listeners
  $('#displayName, #bio, #avatarUrl').on('input', updatePreview);
  $(document).on('input', '.link-title, .link-url', updatePreview);

  function addLinkRow(title = '', url = '') {
    const rowHtml = `
      <div class="link-row">
        <div class="row g-2 align-items-center">
          <div class="col-md-5">
            <input type="text" class="form-control link-title" placeholder="Link Title (e.g. Instagram)" value="${title}" required>
          </div>
          <div class="col-md-6">
            <input type="url" class="form-control link-url" placeholder="https://..." value="${url}" required>
          </div>
          <div class="col-md-1 text-end">
            <button type="button" class="btn btn-outline-danger btn-sm remove-link-btn"><i class="fa-solid fa-trash"></i></button>
          </div>
        </div>
      </div>
    `;
    $('#linksContainer').append(rowHtml);
  }

  function updatePreview() {
    const name = $('#displayName').val().trim() || 'Your Name';
    const bioText = $('#bio').val().trim() || 'Your bio description will appear here...';
    const avatar = $('#avatarUrl').val().trim() || 'https://via.placeholder.com/150';

    $('#previewName').text(name);
    $('#previewBio').text(bioText);
    $('#previewAvatar').attr('src', avatar);

    $('#previewLinks').empty();

    $('.link-row').each(function() {
      const title = $(this).find('.link-title').val().trim();
      const url = $(this).find('.link-url').val().trim();

      if (title !== '') {
        const btnHtml = `<a href="${url || '#'}" target="_blank" class="preview-link-btn">${title}</a>`;
        $('#previewLinks').append(btnHtml);
      }
    });
  }

  // Handle AJAX Submission to PHP
  $('#bioForm').on('submit', function(e) {
    e.preventDefault();

    const username = $('#username').val().trim();
    const displayName = $('#displayName').val().trim();
    const bio = $('#bio').val().trim();
    const avatarUrl = $('#avatarUrl').val().trim();
    const saveBtn = $('#saveBioBtn');
    const errorAlert = $('#errorAlert');

    errorAlert.hide().text('');

    // Collect array of link objects
    const links = [];
    $('.link-row').each(function() {
      const title = $(this).find('.link-title').val().trim();
      const url = $(this).find('.link-url').val().trim();
      if (title && url) {
        links.push({ title: title, url: url });
      }
    });

    saveBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i> Saving Bio...');

    $.ajax({
      url: '/php/bio_builder.php',
      type: 'POST',
      dataType: 'json',
      data: {
        action: 'save',
        username: username,
        display_name: displayName,
        bio: bio,
        avatar_url: avatarUrl,
        links: JSON.stringify(links)
      },
      success: function(response) {
        if (response.success) {
          $('#publishedUrl').val(response.page_url);
          $('#visitBioBtn').attr('href', response.page_url);
          $('#resultBox').slideDown();
        } else {
          errorAlert.text(response.error || 'Could not save bio page.').slideDown();
        }
      },
      error: function() {
        errorAlert.text('Failed to connect to server.').slideDown();
      },
      complete: function() {
        saveBtn.prop('disabled', false).html('<i class="fa-solid fa-cloud-arrow-up me-2"></i> Publish Bio Page');
      }
    });
  });

  // Copy URL
  $('#copyBtn').on('click', function() {
    const input = $('#publishedUrl');
    input.select();
    document.execCommand('copy');

    const originalHtml = $(this).html();
    $(this).html('<i class="fa-solid fa-check me-1"></i> Copied!').addClass('btn-success').removeClass('btn-outline-secondary');
    setTimeout(() => {
      $(this).html(originalHtml).removeClass('btn-success').addClass('btn-outline-secondary');
    }, 2000);
  });

  updatePreview();
});