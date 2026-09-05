<?php
/**
 * Sample Tenant Application: Customer One Subdomain
 */
use Main\Config\DomainConfig;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer One</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="p-5 mb-4 bg-white rounded-3 shadow">
            <h1 class="display-4 fw-bold text-primary">Customer One</h1>
            <p class="lead">Welcome to Customer One tenant subdomain.</p>
            <hr class="my-4">
            <p>Managed securely by NodeX Platform. Tenant ID: <code><?= htmlspecialchars($tenantProject['tenant_id'] ?? 'tenant_456') ?></code></p>
        </div>
    </div>
</body>
</html>
