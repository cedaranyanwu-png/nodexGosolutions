<?php
/**
 * api/subscriptions/plans.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../../backend/services/SubscriptionService.php';

$service = new SubscriptionService();
$plans = $service->getActivePlans();

jsonResponse(['success' => true, 'plans' => $plans]);
