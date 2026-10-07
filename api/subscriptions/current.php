<?php
/**
 * api/subscriptions/current.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../../backend/auth/AuthService.php';
require_once __DIR__ . '/../../backend/services/SubscriptionService.php';

enforceAuth(true);

$auth = new AuthService();
$user = $auth->getCurrentUser();

$service = new SubscriptionService();
$subInfo = $service->getUserSubscription($user);

jsonResponse(['success' => true, 'subscription' => $subInfo]);
