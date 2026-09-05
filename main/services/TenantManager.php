<?php

declare(strict_types=1);

namespace Main\Services;

use Main\Config\DomainConfig;
use Main\Database\JsonDatabase;
use RuntimeException;

class TenantManager
{
    private JsonDatabase $db;
    private string $publicPath;

    public function __construct(?JsonDatabase $db = null, ?string $publicPath = null)
    {
        $this->db = $db ?? new JsonDatabase();
        $this->publicPath = $publicPath ?? dirname(__DIR__, 2) . '/public';
    }

    public function createTenant(string $tenantId, string $name, string $ownerId): array
    {
        if ($this->db->table('tenants')->where('id', $tenantId)->exists()) {
            throw new RuntimeException("Tenant with ID '{$tenantId}' already exists.");
        }

        $tenantData = [
            'id' => $tenantId,
            'name' => $name,
            'owner_id' => $ownerId,
            'status' => 'active',
        ];

        return $this->db->table('tenants')->insert($tenantData);
    }

    public function createProject(string $projectId, string $tenantId, string $ownerId, string $folder, string $type = 'website'): array
    {
        if ($this->db->table('projects')->where('id', $projectId)->exists()) {
            throw new RuntimeException("Project with ID '{$projectId}' already exists.");
        }

        $safeFolder = preg_replace('/[^a-zA-Z0-9_-]/', '', $folder);
        $folderPath = $this->publicPath . '/' . $safeFolder;

        if (!is_dir($folderPath)) {
            mkdir($folderPath, 0755, true);
            // Create default template index.php
            $defaultIndex = "<?php\n" .
                "/** Tenant Project: {$projectId} */\n" .
                "echo '<h1>Welcome to ' . htmlspecialchars(\$tenantProject['id'] ?? '{$projectId}') . '</h1>';\n";
            file_put_contents($folderPath . '/index.php', $defaultIndex);

            $defaultConfig = [
                'project_id' => $projectId,
                'tenant_id' => $tenantId,
                'created_at' => date('Y-m-d H:i:s'),
            ];
            file_put_contents($folderPath . '/config.json', json_encode($defaultConfig, JSON_PRETTY_PRINT));
        }

        $projectData = [
            'id' => $projectId,
            'tenant_id' => $tenantId,
            'owner_id' => $ownerId,
            'folder' => $safeFolder,
            'type' => $type,
            'status' => 'active',
            'managed_by' => 'main',
        ];

        return $this->db->table('projects')->insert($projectData);
    }

    public function registerDomain(string $domain, string $projectId, string $tenantId, string $type = 'custom_domain'): array
    {
        $cleanDomain = DomainConfig::normalizeHost($domain);

        if (DomainConfig::isMainDomain($cleanDomain) || DomainConfig::isMainSubdomain($cleanDomain)) {
            throw new RuntimeException("Cannot register domain '{$cleanDomain}' because it belongs to MAIN configuration.");
        }

        if ($this->db->table('domains')->where('domain', $cleanDomain)->exists()) {
            throw new RuntimeException("Domain '{$cleanDomain}' is already registered.");
        }

        $domainData = [
            'id' => 'dom_' . bin2hex(random_bytes(4)),
            'domain' => $cleanDomain,
            'project_id' => $projectId,
            'tenant_id' => $tenantId,
            'type' => $type,
            'status' => 'active',
        ];

        return $this->db->table('domains')->insert($domainData);
    }

    public function setProjectStatus(string $projectId, string $status): bool
    {
        $validStatuses = ['CREATED', 'REGISTERED', 'CONNECTED', 'ACTIVE', 'SUSPENDED', 'DISABLED', 'DELETED'];
        $upperStatus = strtoupper($status);

        if (!in_array($upperStatus, $validStatuses, true)) {
            throw new RuntimeException("Invalid project status '{$status}'.");
        }

        return $this->db->table('projects')->update($projectId, ['status' => strtolower($upperStatus)]);
    }

    public function setDomainStatus(string $domainId, string $status): bool
    {
        return $this->db->table('domains')->update($domainId, ['status' => strtolower($status)]);
    }

    public function listProjects(): array
    {
        return $this->db->table('projects')->get();
    }

    public function listDomains(): array
    {
        return $this->db->table('domains')->get();
    }
}
