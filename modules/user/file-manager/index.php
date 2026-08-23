<?php
/**
 * User File Manager module entry point.
 *
 * This controller is intentionally thin. It receives the already-authenticated
 * dashboard context and delegates the UI to view.php. File operations remain in
 * the existing API/service layer so this module cannot bypass workspace rules.
 */
declare(strict_types=1);
require_once __DIR__ . '/view.php';
