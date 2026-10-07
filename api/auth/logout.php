<?php
/**
 * api/auth/logout.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../../backend/auth/AuthService.php';

$auth = new AuthService();
$auth->logout();

jsonResponse(['success' => true, 'message' => 'Logged out successfully.']);
