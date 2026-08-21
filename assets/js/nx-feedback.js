/*
 * NodeXGo feedback bridge.
 * Existing modules can continue calling alert() while the UI is migrated;
 * production users receive the branded SweetAlert2 surface instead.
 */
(function () {
  'use strict';
  if (!window.Swal) return;
  window.nxToast = window.nxToast || function (message, icon) {
    return window.Swal.fire({ toast: true, position: 'top-end', timer: 3200, showConfirmButton: false, icon: icon || 'info', title: String(message || '') });
  };
  window.alert = function (message) {
    return window.Swal.fire({ icon: 'info', title: String(message || ''), confirmButtonColor: '#0d6efd' });
  };
}());
