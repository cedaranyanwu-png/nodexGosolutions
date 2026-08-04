<?php
/**
 * index.php
 *
 * This is an example custom tenant entry point file located in the root public directory.
 * It demonstrates how tenant websites are isolated from each other and served dynamically.
 */

// Enable strict typing for highest reliability
declare(strict_types=1);

// Emit a custom header to indicate that this is served from the public folder of the main host directory
header('X-NodeX-Tenant-Source: Public-Folder');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Set a beautiful, unique title for the tenant website -->
    <title>Tenant 1 Portal | NodeX Platform</title>
    <style>
        /* Modern aesthetic styling for the tenant website */
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #f0f4f8;
            color: #1a202c;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .card {
            background: #ffffff;
            border-radius: 12px;
            padding: 2.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            max-width: 500px;
            text-align: center;
        }
        h1 {
            color: #2b6cb0;
            margin-top: 0;
        }
        p {
            font-size: 1.1rem;
            color: #4a5568;
            line-height: 1.6;
        }
        .badge {
            background: #ebf8ff;
            color: #2b6cb0;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <!-- Tenant Site Content Card -->
    <div class="card">
        <span class="badge">Active Deployment</span>
        <h1>Welcome to Tenant 1's Site</h1>
        <!-- Explanatory text detailing how multi-tenancy resolves this file -->
        <p>This page is served dynamically from the <code>/public/tenant1/</code> directory at the root of the NodeX Platform host directory.</p>
        <p>This allows full code isolation for custom tenant websites while keeping them managed under one unified code base!</p>
    </div>
</body>
</html>
