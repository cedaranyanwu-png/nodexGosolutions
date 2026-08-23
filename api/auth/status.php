<?php
/**
 * api/auth/status.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../../backend/auth/AuthService.php';

$auth = new AuthService();
$user = $auth->getCurrentUser();

if ($user) {
    jsonResponse(['success' => true, 'authenticated' => true, 'user' => [
        'id' => $user['id'],
        'email' => $user['email'],
        'fullname' => $user['fullname'],
        'role' => $user['role'],
        'subscription_status' => $user['subscription_status'] ?? 'trial'
    ]]);
} else {
    jsonResponse(['success' => true, 'authenticated' => false]);
}
