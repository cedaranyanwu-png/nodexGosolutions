// img_com.js
// Handles client-side drag-and-drop actions, sliders, canvas scaling, and asynchronous batch uploads.

// Execute actions upon DOM readiness
$(document).ready(function() {

  // Hold queue array items list
  let batchQueue = [];

  // Update slider quality value display upon input slider movements
  $('#qualityRange').on('input', function() {
    // Show current quality percentage
    $('#qualityVal').text($(this).val() + '%');
  });

  // Query dropzone visual element handles
  const dropzone = $('#dropzone');
  const fileInput = $('#fileInput');

  // Trigger file browser upon dropzone clicks
  dropzone.on('click', () => fileInput.click());

  // Listen for file dragging triggers
  dropzone.on('dragover dragenter', function(e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).addClass('dragover');
  });

  // Listen for file drag leave triggers
  dropzone.on('dragleave drop', function(e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).removeClass('dragover');
  });

  // Intercept files drops
  dropzone.on('drop', function(e) {
    // Select dropped files
    const files = e.originalEvent.dataTransfer.files;
    // Process files if present
    if (files.length) handleFiles(files);
  });

  // Listen for file uploader changes
  fileInput.on('change', function() {
    // Process selected files
    if (this.files.length) handleFiles(this.files);
  });

  /**
   * Processes the collection of uploaded files, appending them to the batch queue.
   *
   * @param FileList $files Selected image files.
   */
  function handleFiles(files) {
    // Hide empty placeholder info box
    $('#emptyQueueMsg').addClass('d-none');
    // Display bulk download button
    $('#downloadAllBtn').removeClass('d-none');

    // Iterate through files
    Array.from(files).forEach(file => {
      // Validate file type matches image
      if (!file.type.match('image.*')) return;

      // Format unique id
      const itemId = 'item-' + Math.random().toString(36).substring(2, 9);
      // Create new queue item record
      const itemData = { id: itemId, file: file, processedUrl: null };
      // Push to active batch list queue
      batchQueue.push(itemData);

      // Render queue row displays
      renderQueueItem(itemData);
      // Initiate image compression
      processImage(itemData);
    });
  }

  /**
   * Appends a new image processing progress row in the UI queue.
   *
   * @param object $item Image queue item details.
   */
  function renderQueueItem(item) {
    // Instantiate new FileReader
    const reader = new FileReader();
    // On reader success
    reader.onload = function(e) {
      // Create HTML element layout structure
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
      // Append row
      $('#queueList').append(html);
    };
    // Load file as data URI
    reader.readAsDataURL(item.file);
  }

  /**
   * Compresses the selected image using an HTML5 Canvas, then submits it to the backend.
   *
   * @param object $item Image queue item details.
   */
  function processImage(item) {
    // Capture quality parameters
    const quality = parseFloat($('#qualityRange').val()) / 100;
    // Capture target format parameters
    const format = $('#targetFormat').val();
    // Capture width and height resize parameters
    const maxWidth = parseInt($('#maxWidth').val()) || null;
    const maxHeight = parseInt($('#maxHeight').val()) || null;

    // Instantiate FileReader
    const reader = new FileReader();
    // On load success
    reader.onload = function(event) {
      // Create new Image object
      const img = new Image();
      // On image load completion
      img.onload = function() {
        // Step 1: Canvas Resizing Aspect Ratio Calculations
        let width = img.width;
        let height = img.height;

        // Apply width resizing boundaries
        if (maxWidth && width > maxWidth) {
          height = Math.round((height * maxWidth) / width);
          width = maxWidth;
        }
        // Apply height resizing boundaries
        if (maxHeight && height > maxHeight) {
          width = Math.round((width * maxHeight) / height);
          height = maxHeight;
        }

        // Create canvas element
        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;

        // Extract canvas context and draw current image
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0, width, height);

        // Step 2: Client Canvas Data Conversion to base64
        const exportFormat = format === 'original' ? item.file.type : 'image/' + format;
        const base64Data = canvas.toDataURL(exportFormat, quality);

        // Step 3: Send base64 payload asynchronously to the root /php/img_com.php handler
        $.ajax({
          url: '/php/img_com.php',
          type: 'POST',
          dataType: 'json',
          data: {
            image_data: base64Data,
            file_name: item.file.name,
            format: format
          },
          // Handle saving success scenario
          success: function(res) {
            if (res.success) {
              // Store backend file URL reference
              item.processedUrl = res.file_url;

              // Compute reduction rates
              const savedSize = res.new_size;
              const reduction = Math.round(((item.file.size - savedSize) / item.file.size) * 100);

              // Update item status displays with save button controls
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
      // Set image source
      img.src = event.target.result;
    };
    // Load as data URL
    reader.readAsDataURL(item.file);
  }

  // Trigger Bulk Download ZIP requests pointing to root /php/img_com.php handler
  $('#downloadAllBtn').on('click', function() {
    // Filter out unprocessed files
    const processedFiles = batchQueue.map(i => i.processedUrl).filter(Boolean);
    // Abort if list is empty
    if (!processedFiles.length) return;

    // Send redirection ZIP package request
    window.location.href = '/php/img_com.php?action=download_zip&files=' + encodeURIComponent(JSON.stringify(processedFiles));
  });

  /**
   * Helper to format raw bytes into human readable string representations (KB, MB, GB).
   *
   * @param int $bytes Input bytes amount.
   * @return string Formatted result.
   */
  function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }

});
