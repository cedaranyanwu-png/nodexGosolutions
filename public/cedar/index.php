<?php
/**
 * Sample Tenant Application: Cedar Enterprise
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cedar Enterprise</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="p-5 mb-4 bg-white rounded-3 shadow">
            <h1 class="display-4 fw-bold text-success">Cedar Enterprise</h1>
            <p class="lead">Welcome to Cedar Enterprise custom domain tenant site (cedar.com).</p>
            <hr class="my-4">
            <p>Managed securely by NodeX Platform. Tenant ID: <code><?= htmlspecialchars($tenantProject['tenant_id'] ?? 'tenant_123') ?></code></p>
        </div>
    </div>
</body>
</html>
