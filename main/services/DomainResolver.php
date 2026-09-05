<?php

declare(strict_types=1);

namespace Main\Services;

use Main\Config\DomainConfig;
use Main\Database\JsonDatabase;

class DomainResolver
{
    private JsonDatabase $db;

    public function __construct(?JsonDatabase $db = null)
    {
        $this->db = $db ?? new JsonDatabase();
    }

    public function resolve(string $rawHost): array
    {
        $host = DomainConfig::normalizeHost($rawHost);

        // 1. Check if MAIN domain or MAIN-owned subdomain
        if (DomainConfig::isMainDomain($host)) {
            return [
                'type' => 'MAIN',
                'target' => 'main_domain',
                'host' => $host,
            ];
        }

        if (DomainConfig::isMainSubdomain($host)) {
            $subdomains = DomainConfig::mainSubdomains();
            $subKey = array_search($host, $subdomains, true);
            return [
                'type' => 'MAIN',
                'target' => 'main_subdomain',
                'subdomain_key' => $subKey ?: 'admin',
                'host' => $host,
            ];
        }

        // 2. Search tenant domain registry in JSON database
        $domainRecord = $this->db->table('domains')
            ->where('domain', $host)
            ->where('status', 'active')
            ->first();

        if ($domainRecord) {
            return [
                'type' => 'TENANT',
                'status' => 'ACTIVE',
                'domain_record' => $domainRecord,
                'project_id' => $domainRecord['project_id'],
                'tenant_id' => $domainRecord['tenant_id'],
                'domain_type' => $domainRecord['type'],
                'host' => $host,
            ];
        }

        // Also check if domain exists but is not active
        $inactiveDomainRecord = $this->db->table('domains')
            ->where('domain', $host)
            ->first();

        if ($inactiveDomainRecord) {
            return [
                'type' => 'TENANT_INACTIVE',
                'status' => $inactiveDomainRecord['status'] ?? 'inactive',
                'domain_record' => $inactiveDomainRecord,
                'host' => $host,
            ];
        }

        // 3. Unknown Domain
        return [
            'type' => 'UNKNOWN_DOMAIN',
            'status' => 'unregistered',
            'host' => $host,
        ];
    }
}
