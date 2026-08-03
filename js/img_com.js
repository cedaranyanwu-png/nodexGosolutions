$(document).ready(function() {

  let batchQueue = [];

  // Update slider value display
  $('#qualityRange').on('input', function() {
    $('#qualityVal').text($(this).val() + '%');
  });

  // Dropzone click & drag handlers
  const dropzone = $('#dropzone');
  const fileInput = $('#fileInput');

  dropzone.on('click', () => fileInput.click());

  dropzone.on('dragover dragenter', function(e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).addClass('dragover');
  });

  dropzone.on('dragleave drop', function(e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).removeClass('dragover');
  });

  dropzone.on('drop', function(e) {
    const files = e.originalEvent.dataTransfer.files;
    if (files.length) handleFiles(files);
  });

  fileInput.on('change', function() {
    if (this.files.length) handleFiles(this.files);
  });

  // Handle uploaded batch files
  function handleFiles(files) {
    $('#emptyQueueMsg').addClass('d-none');
    $('#downloadAllBtn').removeClass('d-none');

    Array.from(files).forEach(file => {
      if (!file.type.match('image.*')) return;

      const itemId = 'item-' + Math.random().toString(36).substring(2, 9);
      const itemData = { id: itemId, file: file, processedUrl: null };
      batchQueue.push(itemData);

      renderQueueItem(itemData);
      processImage(itemData);
    });
  }

  // Render Queue Row UI
  function renderQueueItem(item) {
    const reader = new FileReader();
    reader.onload = function(e) {
      const html = `
        <div class="queue-item p-3 mb-2 rounded-3 d-flex align-items-center justify-content-between" id="${item.id}">
          <div class="d-flex align-items-center me-3" style="min-width: 0;">
            <img src="${e.target.result}" class="preview-thumb me-3">
            <div class="text-truncate">
              <h6 class="mb-0 fw-bold text-truncate" style="max-width: 180px;">${item.file.name}</h6>
              <span class="text-muted small">Original: ${formatBytes(item.file.size)}</span>
            </div>
          </div>

          <div class="status-container text-end" style="min-width: 160px;">
            <span class="badge bg-warning text-dark status-badge"><i class="fa-solid fa-spinner fa-spin me-1"></i> Processing</span>
          </div>
        </div>
      `;
      $('#queueList').append(html);
    };
    reader.readAsDataURL(item.file);
  }

  // Process Compression & Format Conversion via Canvas & PHP
  function processImage(item) {
    const quality = parseFloat($('#qualityRange').val()) / 100;
    const format = $('#targetFormat').val();
    const maxWidth = parseInt($('#maxWidth').val()) || null;
    const maxHeight = parseInt($('#maxHeight').val()) || null;

    const reader = new FileReader();
    reader.onload = function(event) {
      const img = new Image();
      img.onload = function() {
        // Step 1: Canvas Resizing Calculation
        let width = img.width;
        let height = img.height;

        if (maxWidth && width > maxWidth) {
          height = Math.round((height * maxWidth) / width);
          width = maxWidth;
        }
        if (maxHeight && height > maxHeight) {
          width = Math.round((width * maxHeight) / height);
          height = maxHeight;
        }

        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;

        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0, width, height);

        // Step 2: Client Canvas Data Conversion
        const exportFormat = format === 'original' ? item.file.type : 'image/' + format;
        const base64Data = canvas.toDataURL(exportFormat, quality);

        // Step 3: Send to PHP Server for Output & Packaging
        $.ajax({
          url: 'php/img_com.php',
          type: 'POST',
          dataType: 'json',
          data: {
            image_data: base64Data,
            file_name: item.file.name,
            format: format
          },
          success: function(res) {
            if (res.success) {
              item.processedUrl = res.file_url;

              const savedSize = res.new_size;
              const reduction = Math.round(((item.file.size - savedSize) / item.file.size) * 100);
              const badgeClass = reduction > 0 ? 'bg-success' : 'bg-secondary';

              $(`#${item.id} .status-container`).html(`
                <div class="small fw-bold text-success">${formatBytes(savedSize)} (${reduction > 0 ? '-' + reduction : '0'}%)</div>
                <a href="${res.file_url}" download class="btn btn-sm btn-outline-primary mt-1">
                  <i class="fa-solid fa-download me-1"></i> Save
                </a>
              `);
            }
          }
        });
      };
      img.src = event.target.result;
    };
    reader.readAsDataURL(item.file);
  }

  // Trigger Bulk Download Request
  $('#downloadAllBtn').on('click', function() {
    const processedFiles = batchQueue.map(i => i.processedUrl).filter(Boolean);
    if (!processedFiles.length) return;

    window.location.href = 'process.php?action=download_zip&files=' + encodeURIComponent(JSON.stringify(processedFiles));
  });

  // Utility Size Formatter
  function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }

});