/**
 * app.js - Main Frontend Application Logic
 *
 * Modular client-side controller managing dynamic SPA tab routing, File Manager
 * interactions, Subscription payment checkouts, Website workspace creation,
 * and custom domain connections via clean API endpoints.
 */

(function($) {
  'use strict';

  // --- 1. GLOBAL TAB NAVIGATION CONTROLLER ---
  function activateTab(tabId) {
    if (!tabId) tabId = 'overview';
    $('.tab-section').removeClass('active-tab');
    $('#' + tabId).addClass('active-tab');

    $('.nav-tab-btn').removeClass('active bg-blue-600 text-white').addClass('text-gray-600 hover:bg-gray-100');
    $('.nav-tab-btn[data-tab="' + tabId + '"]').addClass('active bg-blue-600 text-white').removeClass('text-gray-600 hover:bg-gray-100');

    if (history.pushState) {
      history.pushState(null, null, '#' + tabId);
    } else {
      location.hash = '#' + tabId;
    }
  }

  $(document).ready(function() {
    // Tab Button Handler
    $(document).on('click', '.nav-tab-btn', function(e) {
      const tabTarget = $(this).attr('data-tab');
      if (tabTarget) {
        activateTab(tabTarget);
      }
    });

    // Hash Target Initialization
    const initialHash = window.location.hash.replace('#', '');
    if (initialHash && $('#' + initialHash).length) {
      activateTab(initialHash);
    } else {
      activateTab('overview');
    }

    // --- 2. FILE MANAGER CONTROLLER ---
    let fmActiveSubdomain = '';
    let fmCurrentPath = '';
    let fmActiveFile = '';

    function getSelectedSubdomain() {
      return $('#fmWebsiteSelect').val() || fmActiveSubdomain;
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
          $('#fmItemsContainer').html('<div class="text-center text-danger py-10 text-xs">Failed to connect to file manager API.</div>');
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
          html += ' <span class="text-gray-300">/</span> <span class="badge bg-blue-50 text-blue-700 px-2 py-1 rounded cursor-pointer fm-crumb-item" data-path="' + accumulated + '">' + part + '</span>';
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
        html += `
          <div class="file-item-row d-flex justify-content-between align-items-center fm-item" data-path="${f.path}" data-is-dir="${f.is_dir}" data-name="${f.name}">
            <div class="d-flex align-items-center gap-2 overflow-hidden me-2">
              <i class="fas ${icon} text-sm flex-shrink-0"></i>
              <span class="font-mono text-xs font-semibold truncate">${f.name}</span>
            </div>
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
              <span class="text-2xs text-gray-400 font-mono">${f.formatted_size}</span>
              <button class="btn btn-xs btn-link text-gray-300 hover:text-rose-600 p-0 fm-btn-delete-item" data-path="${f.path}"><i class="fas fa-xmark"></i></button>
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
      fmActiveFile = filePath;
      $('#fmActiveFileName').text(filePath);

      $.ajax({
        url: '/api/files/read',
        type: 'GET',
        data: { subdomain: subdomain, file: filePath },
        success: function(res) {
          if (res.success) {
            $('#fmEditorEmptyState').addClass('d-none');
            $('#fmEditorActivePanel').removeClass('d-none').addClass('d-flex');
            $('#fmCodeArea').val(res.content || '');
          } else {
            alert('Error loading file content: ' + res.message);
          }
        }
      });
    }

    // Save File Changes
    $('#fmBtnSaveFile').on('click', function() {
      const content = $('#fmCodeArea').val();
      if (!fmActiveFile) return;

      $.ajax({
        url: '/api/files/save',
        type: 'POST',
        data: { subdomain: getSelectedSubdomain(), file: fmActiveFile, content: content },
        success: function(res) {
          if (res.success) {
            alert('File changes saved successfully!');
          } else {
            alert('Save error: ' + res.message);
          }
        }
      });
    });

    // Delete Active File
    $('#fmBtnDeleteActive').on('click', function() {
      if (!fmActiveFile || !confirm('Are you sure you want to delete ' + fmActiveFile + '?')) return;

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
            alert('Delete error: ' + res.message);
          }
        }
      });
    });

    // Delete Item from list row
    $(document).on('click', '.fm-btn-delete-item', function(e) {
      e.stopPropagation();
      const path = $(this).attr('data-path');
      if (!confirm('Are you sure you want to delete ' + path + '?')) return;

      $.ajax({
        url: '/api/files/delete',
        type: 'POST',
        data: { subdomain: getSelectedSubdomain(), path: path },
        success: function(res) {
          if (res.success) {
            loadDirectoryContents(getSelectedSubdomain(), fmCurrentPath);
          } else {
            alert('Delete error: ' + res.message);
          }
        }
      });
    });

    // Create New File Button
    $('#fmBtnNewFile').on('click', function() {
      const fileName = prompt('Enter new file name (e.g. style.css, about.html):');
      if (!fileName) return;

      $.ajax({
        url: '/api/files/create',
        type: 'POST',
        data: { subdomain: getSelectedSubdomain(), name: fileName, path: fmCurrentPath, type: 'file' },
        success: function(res) {
          if (res.success) {
            loadDirectoryContents(getSelectedSubdomain(), fmCurrentPath);
          } else {
            alert('Error creating file: ' + res.message);
          }
        }
      });
    });

    // Create New Folder Button
    $('#fmBtnNewFolder').on('click', function() {
      const folderName = prompt('Enter new folder name:');
      if (!folderName) return;

      $.ajax({
        url: '/api/files/create',
        type: 'POST',
        data: { subdomain: getSelectedSubdomain(), name: folderName, path: fmCurrentPath, type: 'folder' },
        success: function(res) {
          if (res.success) {
            loadDirectoryContents(getSelectedSubdomain(), fmCurrentPath);
          } else {
            alert('Error creating folder: ' + res.message);
          }
        }
      });
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
            alert(res.message);
            loadDirectoryContents(getSelectedSubdomain(), fmCurrentPath);
          } else {
            alert('Upload error: ' + res.message);
          }
        }
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
            alert('Payment Gateway Error: ' + (res.message || 'Unable to initialize checkout.'));
            btn.prop('disabled', false).html('Upgrade Membership');
          }
        },
        error: function(err) {
          alert('Network connection error initializing payment checkout.');
          btn.prop('disabled', false).html('Upgrade Membership');
        }
      });
    });

    // --- 4. WEBSITE CREATION FORM CONTROLLER ---
    $('#uploadWebsiteForm').on('submit', function(e) {
      e.preventDefault();
      const name = $('#newWebName').val().trim();
      const subdomain = $('#newWebSubdomain').val().trim();

      if (!name || !subdomain) return;

      $.ajax({
        url: '/api/websites/create',
        type: 'POST',
        data: { name: name, subdomain: subdomain },
        success: function(res) {
          if (res.success) {
            alert('Website space provisioned successfully!');
            location.reload();
          } else {
            alert('Creation Error: ' + res.message);
          }
        },
        error: function() {
          alert('Failed to connect to website creation API.');
        }
      });
    });

    // --- 5. CUSTOM DOMAIN CONNECTION CONTROLLER ---
    $(document).on('click', '.btn-connect-custom-domain', function() {
      const websiteId = $(this).attr('data-website-id');
      const domainInput = prompt('Enter your custom domain (e.g. mycompany.com):');
      if (!domainInput || !websiteId) return;

      $.ajax({
        url: '/api/domains/connect',
        type: 'POST',
        data: { website_id: websiteId, custom_domain: domainInput },
        success: function(res) {
          if (res.success) {
            alert(res.message);
            location.reload();
          } else {
            alert('Domain Connection Error: ' + res.message);
          }
        }
      });
    });

  });

})(jQuery);
