<?php

declare(strict_types=1);

use Main\Config\DomainConfig;
use Main\Database\JsonDatabase;
use Main\Services\TenantManager;

header('Content-Type: application/json');

$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$db = new JsonDatabase(__DIR__ . '/../storage/db');

if ($requestUri === '/api/health' || $requestUri === '/health') {
    echo json_encode([
        'status' => 'healthy',
        'main_domain' => DomainConfig::mainDomain(),
        'timestamp' => date('c'),
    ]);
    exit;
}

if ($requestUri === '/api/tenants') {
    $tenantManager = new TenantManager($db, __DIR__ . '/../../public');
    echo json_encode([
        'success' => true,
        'projects' => $tenantManager->listProjects(),
        'domains' => $tenantManager->listDomains(),
    ]);
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'API Endpoint Not Found']);
