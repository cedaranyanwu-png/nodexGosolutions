<?php

declare(strict_types=1);

use Main\Config\DomainConfig;
use Main\Database\JsonDatabase;
use Main\Discovery\MainDiscovery;
use Main\Services\DomainScanner;
use Main\Services\TenantManager;

$db = new JsonDatabase(__DIR__ . '/../../storage/db');
$tenantManager = new TenantManager($db, __DIR__ . '/../../../public');
$discovery = new MainDiscovery(__DIR__ . '/../..');
$scanner = new DomainScanner(__DIR__ . '/../../..');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create_tenant_project') {
            $tenantId = trim($_POST['tenant_id'] ?? '');
            $tenantName = trim($_POST['tenant_name'] ?? '');
            $projectId = trim($_POST['project_id'] ?? '');
            $folder = trim($_POST['folder'] ?? '');
            $domain = trim($_POST['domain'] ?? '');
            $domainType = trim($_POST['domain_type'] ?? 'custom_domain');

            if ($tenantId && $tenantName && $projectId && $folder) {
                // Ensure tenant exists or create it
                if (!$db->table('tenants')->where('id', $tenantId)->exists()) {
                    $tenantManager->createTenant($tenantId, $tenantName, 'usr_admin_01');
                }

                $tenantManager->createProject($projectId, $tenantId, 'usr_admin_01', $folder);

                if ($domain) {
                    $tenantManager->registerDomain($domain, $projectId, $tenantId, $domainType);
                }

                $message = "Project '{$projectId}' created and registered successfully!";
            } else {
                $error = "Please fill in all required fields.";
            }
        } elseif ($action === 'toggle_project_status') {
            $projectId = $_POST['project_id'] ?? '';
            $status = $_POST['status'] ?? 'active';
            $tenantManager->setProjectStatus($projectId, $status);
            $message = "Status for project '{$projectId}' updated to {$status}.";
        } elseif ($action === 'register_domain') {
            $domain = trim($_POST['domain'] ?? '');
            $projectId = trim($_POST['project_id'] ?? '');
            $tenantId = trim($_POST['tenant_id'] ?? '');
            $domainType = trim($_POST['domain_type'] ?? 'custom_domain');

            if ($domain && $projectId && $tenantId) {
                $tenantManager->registerDomain($domain, $projectId, $tenantId, $domainType);
                $message = "Domain '{$domain}' registered for project '{$projectId}'.";
            }
        }
    } catch (\Throwable $e) {
        $error = $e->getMessage();
    }
}

$projects = $tenantManager->listProjects();
$domains = $tenantManager->listDomains();
$discovered = $discovery->discoverAll();
$scanFindings = $scanner->scanForHardcodedMainDomain();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NodeX Main Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <div class="navbar navbar-dark bg-dark px-4 py-3 shadow-sm">
        <a class="navbar-brand fw-bold" href="#">NodeX Admin Control Panel</a>
        <span class="navbar-text text-light">Main Domain: <code><?= htmlspecialchars(DomainConfig::mainDomain()) ?></code></span>
    </div>

    <div class="container py-4">
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Create Tenant Project Card -->
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-primary text-white fw-bold">Provision New Tenant Project</div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="create_tenant_project">
                            <div class="mb-2">
                                <label class="form-label">Tenant ID</label>
                                <input type="text" name="tenant_id" class="form-control" placeholder="tenant_789" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Tenant Name</label>
                                <input type="text" name="tenant_name" class="form-control" placeholder="Acme Corp" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Project ID / Name</label>
                                <input type="text" name="project_id" class="form-control" placeholder="acme" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Folder Name (/public/...)</label>
                                <input type="text" name="folder" class="form-control" placeholder="acme" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Domain / Subdomain (Optional)</label>
                                <input type="text" name="domain" class="form-control" placeholder="acme.<?= htmlspecialchars(DomainConfig::mainDomain()) ?> or acme.com">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Domain Type</label>
                                <select name="domain_type" class="form-select">
                                    <option value="tenant_subdomain">Tenant Subdomain</option>
                                    <option value="custom_domain">Custom Domain</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success w-100 fw-bold">Create & Register Tenant</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Self-Discovery Diagnostics -->
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-secondary text-white fw-bold">MAIN Self-Discovery & Diagnostics</div>
                    <div class="card-body">
                        <h6>Discovered MAIN Modules:</h6>
                        <ul>
                            <?php foreach ($discovered['modules'] as $m): ?>
                                <li><strong><?= htmlspecialchars($m['slug']) ?></strong> - <?= htmlspecialchars($m['name'] ?? '') ?></li>
                            <?php endforeach; ?>
                        </ul>

                        <h6>Domain Scanner Diagnostic:</h6>
                        <p class="mb-1">Hardcoded Main Domain References Found: <strong><?= count($scanFindings) ?></strong></p>
                        <?php if (empty($scanFindings)): ?>
                            <span class="badge bg-success">Clean - Completely Configuration Driven</span>
                        <?php else: ?>
                            <ul class="text-danger small">
                                <?php foreach ($scanFindings as $f): ?>
                                    <li><?= htmlspecialchars($f['file']) ?>:<?= $f['line'] ?> - <?= htmlspecialchars($f['content']) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Projects Table -->
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-bold">Registered Tenant Projects</div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Project ID</th>
                                    <th>Tenant ID</th>
                                    <th>Folder Path</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projects as $p): ?>
                                    <tr>
                                        <td><code><?= htmlspecialchars($p['id']) ?></code></td>
                                        <td><?= htmlspecialchars($p['tenant_id']) ?></td>
                                        <td>/public/<?= htmlspecialchars($p['folder']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $p['status'] === 'active' ? 'success' : 'danger' ?>">
                                                <?= strtoupper($p['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="toggle_project_status">
                                                <input type="hidden" name="project_id" value="<?= htmlspecialchars($p['id']) ?>">
                                                <input type="hidden" name="status" value="<?= $p['status'] === 'active' ? 'suspended' : 'active' ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-<?= $p['status'] === 'active' ? 'warning' : 'success' ?>">
                                                    <?= $p['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Domain Registry Table -->
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-bold">Domain Registry</div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Domain Name</th>
                                    <th>Project ID</th>
                                    <th>Tenant ID</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($domains as $d): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($d['domain']) ?></strong></td>
                                        <td><?= htmlspecialchars($d['project_id']) ?></td>
                                        <td><?= htmlspecialchars($d['tenant_id']) ?></td>
                                        <td><span class="badge bg-info text-dark"><?= htmlspecialchars($d['type']) ?></span></td>
                                        <td><span class="badge bg-<?= $d['status'] === 'active' ? 'success' : 'secondary' ?>"><?= strtoupper($d['status']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</body>
</html>
