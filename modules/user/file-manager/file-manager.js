/**
 * NodeXGo User File Manager module boundary.
 *
 * The current legacy implementation is still bootstrapped from app.js so all
 * existing selectors and AJAX contracts remain stable. This module exposes a
 * small lifecycle event bridge for the incremental migration: future code can
 * subscribe to workspace events without copying the file operation logic.
 */
(function (window, document) {
  'use strict';

  window.NodeXGo = window.NodeXGo || {};
  window.NodeXGo.fileManager = {
    version: '1.0.0',
    events: {
      opened: 'nx:file-manager:opened',
      fileSelected: 'nx:file-manager:file-selected',
      saved: 'nx:file-manager:saved'
    },
    emit: function (name, detail) {
      document.dispatchEvent(new CustomEvent(name, { detail: detail || {} }));
    }
  };
})(window, document);
