<?php
/**
 * api/auth/register.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../../backend/auth/AuthService.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$fullname = (string)($_POST['fullname'] ?? '');
$email = (string)($_POST['email'] ?? '');
$password = (string)($_POST['password'] ?? '');

$auth = new AuthService();
$res = $auth->register($fullname, $email, $password);

jsonResponse($res, $res['success'] ? 200 : 400);
