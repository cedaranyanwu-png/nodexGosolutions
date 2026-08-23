<?php
/**
 * NodeXGo modular core bootstrap.
 *
 * New modules should include this file instead of manually requiring several
 * unrelated infrastructure files. Existing route handlers may continue using
 * their compatibility includes until they are migrated and tested.
 */
declare(strict_types=1);

require_once __DIR__ . '/../backend/config/config.php';
require_once __DIR__ . '/JsonStorage/JsonStorage.php';
require_once __DIR__ . '/Response/JsonResponse.php';
require_once __DIR__ . '/Authorization/AuthorizationGateway.php';
