<?php
/**
 * TenantManager.php / sys_tenant.php
 *
 * Handles multi-tenancy logic by mapping domains/subdomains to specific tenant configuration data
 * loaded dynamically from the central configuration engine (sys_config.php).
 */

declare(strict_types=1);

require_once __DIR__ . '/sys_config.php';
require_once __DIR__ . '/DomainManager.php';

class TenantManager
{
    private array $tenants;
    private DomainManager $domainManager;

    public function __construct(?DomainManager $domainManager = null)
    {
        $this->domainManager = $domainManager ?? new DomainManager();
        $this->loadTenants();
    }

    /**
     * Load tenants dynamically from central config sys_config('tenants').
     */
    public function loadTenants(): void
    {
        $configuredTenants = sys_config('tenants', []);
        $this->tenants = is_array($configuredTenants) ? $configuredTenants : [];
    }

    /**
     * Get all registered tenants.
     */
    public function getTenants(): array
    {
        return $this->tenants;
    }

    /**
     * Parse incoming host and retrieve tenant details.
     *
     * @param string $host The value of $_SERVER['HTTP_HOST']
     * @return array|null Returns tenant data if found.
     */
    public function getTenantByHost(string $host): ?array
    {
        $cleanHost = strtolower(trim($host));
        if (str_contains($cleanHost, ':')) {
            $cleanHost = explode(':', $cleanHost)[0];
        }

        foreach ($this->tenants as $tenant) {
            if (isset($tenant['identifier']) && strcasecmp($tenant['identifier'], $cleanHost) === 0) {
                // Annotate tenant structure with dynamically resolved context type
                $tenant['type'] = $tenant['type'] ?? $this->domainManager->getDomainType($cleanHost);
                return $tenant;
            }
        }

        return null;
    }

    /**
     * Get domain context type ('MAIN', 'MAIN-CONNECTED', or 'TENANT') for a host.
     */
    public function getDomainType(string $host): string
    {
        return $this->domainManager->getDomainType($host);
    }

    /**
     * Fallback default tenant data.
     */
    public function getDefaultTenant(): array
    {
        return [
            'id'          => 0,
            'identifier'  => 'default',
            'name'        => 'Generic NodeX Instance',
            'type'        => 'TENANT',
            'path'        => 'public/default',
            'theme_color' => '#7f8c8d',
            'logo_url'    => 'https://placehold.co/200x50/7f8c8d/ffffff?text=Default+NodeX',
            'content'     => 'The requested domain is not registered. Showing default tenant content.'
        ];
    }
}
