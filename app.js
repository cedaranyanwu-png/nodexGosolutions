/**
 * app.js - Main Frontend Application Logic
 *
 * Modular client-side controller managing dynamic SPA tab routing, File Manager
 * interactions, Subscription payment checkouts, Website workspace creation,
 * and custom domain connections via clean API endpoints.
 */

(function($) {
  'use strict';

  // Shared feedback helpers keep all user-facing messages consistent and accessible.
  function nxToast(message, icon = 'info') {
    if (window.Swal) return Swal.fire({ toast: true, position: 'top-end', timer: 3200, showConfirmButton: false, icon: icon, title: String(message || '') });
    return Promise.resolve();
  }
  function nxDialog(options) {
    if (window.Swal) return Swal.fire(Object.assign({ confirmButtonColor: '#0d6efd' }, options));
    return Promise.resolve({ isConfirmed: window.confirm(options.title || 'Continue?') });
  }
  function nxPrompt(title, inputValue = '') {
    if (!window.Swal) return Promise.resolve(window.prompt(title, inputValue));
    return Swal.fire({ title: title, input: 'text', inputValue: inputValue, showCancelButton: true, confirmButtonText: 'Continue', confirmButtonColor: '#0d6efd', inputValidator: value => value ? undefined : 'A value is required.' }).then(result => result.isConfirmed ? result.value : null);
  }

  // --- 1. GLOBAL TAB NAVIGATION CONTROLLER ---
  function activateTab(tabId, updateHash = true) {
    if (!tabId || !document.getElementById(tabId)) tabId = 'overview';
    $('.tab-section').removeClass('active-tab');
    $('#' + tabId).addClass('active-tab');

    $('.nav-tab-btn').removeClass('active bg-blue-600 text-white').addClass('text-gray-600 hover:bg-gray-100');
    $('.nav-tab-btn[data-tab="' + tabId + '"]').addClass('active bg-blue-600 text-white').removeClass('text-gray-600 hover:bg-gray-100');

    if (updateHash && window.location.hash !== '#' + tabId) {
      if (history.replaceState) history.replaceState(null, '', '#' + tabId);
      else window.location.hash = '#' + tabId;
    }
  }

  $(document).ready(function() {
    // Tab Button Handler
    $(document).on('click', '.nav-tab-btn', function(e) {
      const tabTarget = $(this).attr('data-tab');
      if (tabTarget) {
        activateTab(tabTarget);
        if (tabTarget === 'manage-files') {
          window.setTimeout(function () {
            if (typeof loadDirectoryContents === 'function') loadDirectoryContents(getSelectedSubdomain(), fmCurrentPath);
          }, 0);
        }
      }
    });

    // Hash Target Initialization. Invalid hashes safely fall back to Overview.
    const initialHash = decodeURIComponent(window.location.hash.replace(/^#/, ''));
    activateTab(initialHash || 'overview', false);

    // Listen for hashchange events (e.g. from sidebar clicks)
    $(window).on('hashchange', function() {
      activateTab(decodeURIComponent(window.location.hash.replace(/^#/, '')) || 'overview', false);
    });

    // --- 2. FILE MANAGER CONTROLLER ---
    let fmActiveSubdomain = '';
    let fmCurrentPath = '';
    let fmActiveFile = '';
    let gjsEditor = null;
    let fmActiveMode = 'code';

    function initGrapesJsIfNeeded() {
      if (!gjsEditor && typeof grapesjs !== 'undefined' && $('#gjs-file-container').length) {
        gjsEditor = grapesjs.init({
          container: '#gjs-file-container',
          height: '500px',
          width: 'auto',
          storageManager: false,
          panels: { defaults: [] }
        });
      }
    }

    $(document).on('click', '#fmBtnModeCode', function() {
      fmActiveMode = 'code';
      $('#fmBtnModeCode').removeClass('text-slate-400 bg-transparent').addClass('text-white bg-blue-600');
      $('#fmBtnModeVisual').removeClass('text-white bg-blue-600').addClass('text-slate-400 bg-transparent');

      if (gjsEditor) {
        const hCode = gjsEditor.getHtml();
        const cCode = gjsEditor.getCss();
        if (hCode) {
          const combined = cCode ? `<style>\n${cCode}\n</style>\n${hCode}` : hCode;
          $('#fmCodeArea').val(combined);
        }
      }
      $('#gjs-file-container').addClass('d-none');
      $('#fmCodeArea').removeClass('d-none');
    });

    $(document).on('click', '#fmBtnModeVisual', function() {
      fmActiveMode = 'visual';
      $('#fmBtnModeVisual').removeClass('text-slate-400 bg-transparent').addClass('text-white bg-blue-600');
      $('#fmBtnModeCode').removeClass('text-white bg-blue-600').addClass('text-slate-400 bg-transparent');

      $('#fmCodeArea').addClass('d-none');
      $('#gjs-file-container').removeClass('d-none');

      initGrapesJsIfNeeded();
      if (gjsEditor) {
        const rawCode = $('#fmCodeArea').val() || '';
        gjsEditor.setComponents(rawCode);
      }
    });

    function getSelectedSubdomain() {
      return $('#fmWebsiteSelect').val() || fmActiveSubdomain;
    }

    function escapeHtml(value) {
      return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function handleApiError(xhr, fallbackMessage) {
      if (xhr && (xhr.status === 401 || xhr.status === 403)) {
        window.location.href = '/login?redirect=' + encodeURIComponent(window.location.pathname + window.location.hash);
        return;
      }
      $('#fmItemsContainer').html('<div class="text-center text-danger py-10 text-xs">' + escapeHtml(fallbackMessage || 'Request failed.') + '</div>');
    }

    function loadDirectoryContents(subdomain, path) {
      subdomain = subdomain || getSelectedSubdomain();
      path = path || '';
      fmActiveSubdomain = subdomain;
      fmCurrentPath = path;

      if (!subdomain) {
        $('#fmItemsContainer').html('<div class="text-center text-gray-400 py-10 text-xs">No website workspace selected.</div>');
        return;
      }

      $('#fmItemsContainer').html('<div class="text-center text-gray-400 py-10 text-xs"><i class="fas fa-spinner fa-spin me-2"></i> Loading directory...</div>');

      $.ajax({
        url: '/api/files/list',
        type: 'GET',
        dataType: 'json',
        data: { subdomain: subdomain, path: path },
        success: function(res) {
          if (!res.success) {
            $('#fmItemsContainer').html('<div class="text-center text-danger py-10 text-xs">' + (res.message || 'Error loading files.') + '</div>');
            return;
          }

          renderBreadcrumbs(path);
          renderDirectoryItems(res.files || []);
        },
        error: function(err) {
          handleApiError(err, 'Failed to connect to file manager API.');
        }
      });
    }

    function renderBreadcrumbs(path) {
      let html = '<span class="badge bg-blue-50 text-blue-700 px-2 py-1 rounded cursor-pointer fm-crumb-item" data-path="">/</span>';
      if (path) {
        const parts = path.split('/').filter(Boolean);
        let accumulated = '';
        parts.forEach(function(part) {
          accumulated += (accumulated ? '/' : '') + part;
          html += ' <span class="text-gray-300">/</span> <span class="badge bg-blue-50 text-blue-700 px-2 py-1 rounded cursor-pointer fm-crumb-item" data-path="' + escapeHtml(accumulated) + '">' + escapeHtml(part) + '</span>';
        });
      }
      $('#fmBreadcrumbs').html(html);
    }

    function renderDirectoryItems(files) {
      if (!files || files.length === 0) {
        $('#fmItemsContainer').html('<div class="text-center text-gray-400 py-8 text-xs">Directory is empty.</div>');
        return;
      }

      let html = '';
      files.forEach(function(f) {
        const icon = f.is_dir ? 'fa-folder text-amber-500' : 'fa-file-code text-blue-500';
        const safePath = escapeHtml(f.path || '');
        const safeName = escapeHtml(f.name || '');
        const safeSize = escapeHtml(f.formatted_size || '-');
        html += `
          <div class="file-item-row d-flex justify-content-between align-items-center fm-item" data-path="${safePath}" data-is-dir="${f.is_dir === true ? 'true' : 'false'}" data-name="${safeName}">
            <div class="d-flex align-items-center gap-2 overflow-hidden me-2">
              <i class="fas ${icon} text-sm flex-shrink-0"></i>
              <span class="font-mono text-xs font-semibold truncate">${safeName}</span>
            </div>
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
              <span class="text-2xs text-gray-400 font-mono">${safeSize}</span>
              <button class="btn btn-xs btn-link text-gray-300 hover:text-rose-600 p-0 fm-btn-delete-item" data-path="${safePath}"><i class="fas fa-xmark"></i></button>
            </div>
          </div>
        `;
      });

      $('#fmItemsContainer').html(html);
    }

    // Subdomain selector change
    $('#fmWebsiteSelect').on('change', function() {
      loadDirectoryContents($(this).val(), '');
    });

    // Refresh button
    $('#fmBtnRefresh').on('click', function() {
      loadDirectoryContents(getSelectedSubdomain(), fmCurrentPath);
    });

    // Breadcrumb click navigation
    $(document).on('click', '.fm-crumb-item', function() {
      const pathTarget = $(this).attr('data-path');
      loadDirectoryContents(getSelectedSubdomain(), pathTarget);
    });

    // Item row click handler
    $(document).on('click', '.fm-item', function(e) {
      if ($(e.target).closest('.fm-btn-delete-item').length) return;

      const path = $(this).attr('data-path');
      const isDir = $(this).attr('data-is-dir') === 'true';

      $('.file-item-row').removeClass('active');
      $(this).addClass('active');

      if (isDir) {
        loadDirectoryContents(getSelectedSubdomain(), path);
      } else {
        openFileInEditor(getSelectedSubdomain(), path);
      }
    });

    function openFileInEditor(subdomain, filePath) {
      /* File editing is deliberately isolated in a new tab so the full editor can use the viewport. */
      const editorUrl = '/editor?subdomain=' + encodeURIComponent(subdomain) + '&file=' + encodeURIComponent(filePath);
      const editorWindow = window.open(editorUrl, '_blank');
      if (!editorWindow) nxToast('Enable pop-ups to open the full editor workspace.', 'warning');
    }

    // Save File Changes
    $('#fmBtnSaveFile').on('click', function() {
      let content = $('#fmCodeArea').val();
      if (fmActiveMode === 'visual' && gjsEditor) {
        const hCode = gjsEditor.getHtml();
        const cCode = gjsEditor.getCss();
        content = cCode ? `<style>\n${cCode}\n</style>\n${hCode}` : hCode;
      }
      if (!fmActiveFile) return;

      $.ajax({
        url: '/api/files/save',
        type: 'POST',
        data: { subdomain: getSelectedSubdomain(), file: fmActiveFile, content: content },
        success: function(res) {
          if (res.success) {
            nxToast('File changes saved successfully!', 'success');
          } else {
            nxToast('Save error: ' + res.message, 'error');
          }
        }
      });
    });

    // Delete Active File
    $('#fmBtnDeleteActive').on('click', function() {
      if (!fmActiveFile) return nxToast('Select a file or folder first.', 'info');
      const selectedPath = fmActiveFile;
      nxDialog({title:'Delete this item?',text:selectedPath,icon:'warning',showCancelButton:true,confirmButtonText:'Delete',confirmButtonColor:'#dc3545'}).then(function(result){
        if (!result.isConfirmed) return;
        $.ajax({
        url: '/api/files/delete',
        type: 'POST',
        data: { subdomain: getSelectedSubdomain(), path: fmActiveFile },
        success: function(res) {
          if (res.success) {
            $('#fmEditorActivePanel').removeClass('d-flex').addClass('d-none');
            $('#fmEditorEmptyState').removeClass('d-none');
            fmActiveFile = '';
            loadDirectoryContents(getSelectedSubdomain(), fmCurrentPath);
          } else {
            nxToast('Delete error: ' + res.message, 'error');
          }
        }
        });
      });
    });

    // Delete Item from list row
    $(document).on('click', '.fm-btn-delete-item', function(e) {
      e.stopPropagation();
      const path = $(this).attr('data-path');
      nxDialog({title:'Delete this item?',text:path,icon:'warning',showCancelButton:true,confirmButtonText:'Delete',confirmButtonColor:'#dc3545'}).then(function(result){
        if (!result.isConfirmed) return;
        $.ajax({
        url: '/api/files/delete',
        type: 'POST',
        data: { subdomain: getSelectedSubdomain(), path: path },
        success: function(res) {
          if (res.success) {
            loadDirectoryContents(getSelectedSubdomain(), fmCurrentPath);
          } else {
            nxToast('Delete error: ' + res.message, 'error');
          }
        }
        });
      });
    });

    // Create New File Button
    $('#fmBtnNewFile').on('click', function() {
      nxPrompt('Enter the new file name', 'index.html').then(function(fileName){
      if (!fileName) return;
      $.ajax({
        url: '/api/files/create',
        type: 'POST',
        data: { subdomain: getSelectedSubdomain(), name: fileName, path: fmCurrentPath, type: 'file' },
        success: function(res) {
          if (res.success) {
            loadDirectoryContents(getSelectedSubdomain(), fmCurrentPath);
          } else {
            nxToast('Error creating file: ' + res.message, 'error');
          }
        }
      });
      });
    });

    // Create New Folder Button
    $('#fmBtnNewFolder').on('click', function() {
      nxPrompt('Enter the new folder name', 'assets').then(function(folderName){
      if (!folderName) return;
      $.ajax({
        url: '/api/files/create',
        type: 'POST',
        data: { subdomain: getSelectedSubdomain(), name: folderName, path: fmCurrentPath, type: 'folder' },
        success: function(res) {
          if (res.success) {
            loadDirectoryContents(getSelectedSubdomain(), fmCurrentPath);
          } else {
            nxToast('Error creating folder: ' + res.message, 'error');
          }
        }
      });
      });
    });

    function fmMutation(url, data, done) {
      $.ajax({url:url, type:'POST', data:data, dataType:'json'}).done(function(res){
        if (res.success) { nxToast(res.message || 'Operation completed.', 'success'); if (done) done(); loadDirectoryContents(getSelectedSubdomain(), fmCurrentPath); }
        else nxToast(res.message || 'Operation failed.', 'error');
      }).fail(function(xhr){ handleApiError(xhr, 'File operation failed.'); });
    }

    $('#fmBtnCloseWorkspace').on('click', function(){ activateTab('overview'); });
    $('#fmBtnRoot, #fmBtnRefreshRail').on('click', function(){ loadDirectoryContents(getSelectedSubdomain(), $(this).attr('id') === 'fmBtnRoot' ? '' : fmCurrentPath); });
    $('#fmBtnUploadZipRail').on('click', function(){ $('#fmFileInput').trigger('click'); });
    $('#fmBtnRename').on('click', function(){
      if (!fmActiveFile) return nxToast('Select a file or folder first.', 'info');
      nxPrompt('Enter the new name', fmActiveFile.split('/').pop()).then(function(newName){ if (newName) fmMutation('/api/files/rename', {subdomain:getSelectedSubdomain(), old_path:fmActiveFile, new_name:newName}, function(){ fmActiveFile=''; }); });
    });
    $('#fmBtnCopy').on('click', function(){
      if (!fmActiveFile) return nxToast('Select a file or folder first.', 'info');
      nxPrompt('Enter the copy destination path', fmActiveFile + '-copy').then(function(target){ if (target) fmMutation('/api/files/copy', {subdomain:getSelectedSubdomain(), source_path:fmActiveFile, destination_path:target}); });
    });
    $('#fmBtnMove').on('click', function(){
      if (!fmActiveFile) return nxToast('Select a file or folder first.', 'info');
      nxPrompt('Enter the move destination path', fmActiveFile).then(function(target){ if (target) fmMutation('/api/files/move', {subdomain:getSelectedSubdomain(), source_path:fmActiveFile, destination_path:target}, function(){ fmActiveFile=''; }); });
    });

    // File Upload Handler
    $('#fmFileInput').on('change', function() {
      const fileList = this.files;
      if (!fileList || fileList.length === 0) return;

      const formData = new FormData();
      formData.append('subdomain', getSelectedSubdomain());
      formData.append('path', fmCurrentPath);

      for (let i = 0; i < fileList.length; i++) {
        formData.append('file[]', fileList[i]);
      }

      $.ajax({
        url: '/api/files/upload',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function(res) {
          if (res.success) {
            nxToast(res.message, 'success');
            loadDirectoryContents(getSelectedSubdomain(), fmCurrentPath);
          } else {
            nxToast('Upload error: ' + res.message, 'error');
          }
        },
        error: function(xhr) { handleApiError(xhr, 'Upload failed.'); }
      });
    });

    // Auto-load File Manager if active tab is manage-files
    if ($('#manage-files').hasClass('active-tab')) {
      loadDirectoryContents(getSelectedSubdomain(), '');
    }

    // Browse files button from Overview or other tabs
    $('.btn-browse-files').on('click', function() {
      const sub = $(this).attr('data-subdomain');
      if (sub) {
        $('#fmWebsiteSelect').val(sub);
        activateTab('manage-files');
        loadDirectoryContents(sub, '');
      }
    });

    // --- 3. PAYMENT & SUBSCRIPTION CHECKOUT CONTROLLER ---
    $(document).on('click', '.btn-process-payment', function() {
      const planId = $(this).attr('data-plan-id');
      if (!planId) return;

      const btn = $(this);
      btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Processing...');

      $.ajax({
        url: '/api/payments/initialize',
        type: 'POST',
        data: { plan_id: planId },
        success: function(res) {
          if (res.success && res.link) {
            window.location.href = res.link;
          } else {
            nxToast('Payment Gateway Error: ' + (res.message || 'Unable to initialize checkout.'), 'error');
            btn.prop('disabled', false).html('Upgrade Membership');
          }
        },
        error: function(xhr) {
          const response = xhr && xhr.responseJSON ? xhr.responseJSON : {};
          nxToast(response.message || 'Unable to initialize Flutterwave checkout. Please verify the gateway configuration and try again.', 'error');
          btn.prop('disabled', false).html('Upgrade Membership');
        }
      });
    });

    // --- 4. WEBSITE CREATION FORM CONTROLLER ---
    $('#uploadWebsiteForm').on('submit', function(e) {
      e.preventDefault();
      const name = $('#newWebName').val().trim();
      const subdomain = $('#newWebSubdomain').val().trim();
      const customDomain = $('#newWebCustomDomain').val() ? $('#newWebCustomDomain').val().trim() : '';

      if (!name || !subdomain) return;

      $.ajax({
        url: '/api/websites/create',
        type: 'POST',
        data: { name: name, subdomain: subdomain, custom_domain: customDomain },
        success: function(res) {
          if (res.success) {
            nxToast(res.message || 'Website space provisioned successfully!', 'success');
            location.reload();
          } else {
            nxToast('Creation Error: ' + res.message, 'error');
          }
        },
        error: function() {
          nxToast('Failed to connect to website creation API.', 'error');
        }
      });
    });

    // --- 5. CUSTOM DOMAIN CONNECTION CONTROLLER ---
    $(document).on('click', '.btn-connect-custom-domain', function() {
      const websiteId = $(this).attr('data-website-id');
      if (!websiteId) return;
      nxPrompt('Enter your custom domain', 'mycompany.com').then(function(domainInput){
      if (!domainInput) return;

      $.ajax({
        url: '/api/domains/connect',
        type: 'POST',
        data: { website_id: websiteId, custom_domain: domainInput },
        success: function(res) {
          if (res.success) {
            nxToast(res.message, 'success');
            location.reload();
          } else {
            nxToast('Domain Connection Error: ' + res.message, 'error');
          }
        }
      });
      });
    });

  });

})(jQuery);
