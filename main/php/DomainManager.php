<?php
/**
 * DomainManager.php / sys_domain.php
 *
 * Domain Manager class that utilizes the central configuration system (sys_config.php)
 * to categorize domain requests into MAIN, MAIN-CONNECTED, or TENANT.
 */

declare(strict_types=1);

require_once __DIR__ . '/sys_config.php';

class DomainManager
{
    private string $mainDomain;
    private array $connectedDomains;

    public function __construct()
    {
        // Obtain main domain and connected domains dynamically from central config
        $this->mainDomain = (string) sys_config('domains.main', '');
        $this->connectedDomains = (array) sys_config('domains.connected', []);
    }

    /**
     * Get the configured main domain.
     */
    public function getMainDomain(): string
    {
        return $this->mainDomain;
    }

    /**
     * Get list of domains/identifiers configured as MAIN-CONNECTED.
     */
    public function getConnectedDomains(): array
    {
        return $this->connectedDomains;
    }

    /**
     * Determine the domain context: MAIN, MAIN-CONNECTED, or TENANT.
     *
     * @param string $host The incoming host header (e.g., HTTP_HOST)
     * @return string 'MAIN', 'MAIN-CONNECTED', or 'TENANT'
     */
    public function getDomainType(string $host): string
    {
        $cleanHost = strtolower(trim($host));
        if (str_contains($cleanHost, ':')) {
            $cleanHost = explode(':', $cleanHost)[0];
        }

        // Match localhost / loopback or exact main domain
        if ($cleanHost === 'localhost' || $cleanHost === '127.0.0.1' || ($this->mainDomain !== '' && strcasecmp($cleanHost, $this->mainDomain) === 0)) {
            return 'MAIN';
        }

        // Check if host matches any connected domain or subdomain
        foreach ($this->connectedDomains as $connected) {
            if (strcasecmp($cleanHost, $connected) === 0) {
                return 'MAIN-CONNECTED';
            }
        }

        // Check configured tenants list in sys_config for explicit MAIN-CONNECTED flag
        $tenants = (array) sys_config('tenants', []);
        foreach ($tenants as $tenant) {
            if (isset($tenant['identifier']) && strcasecmp($tenant['identifier'], $cleanHost) === 0) {
                if (isset($tenant['type']) && strtoupper($tenant['type']) === 'MAIN-CONNECTED') {
                    return 'MAIN-CONNECTED';
                }
                if (isset($tenant['type']) && strtoupper($tenant['type']) === 'MAIN') {
                    return 'MAIN';
                }
            }
        }

        // Default fallback for other subdomains or custom domains
        return 'TENANT';
    }

    public function isMainDomain(string $host): bool
    {
        return $this->getDomainType($host) === 'MAIN';
    }

    public function isMainConnectedDomain(string $host): bool
    {
        return $this->getDomainType($host) === 'MAIN-CONNECTED';
    }

    public function isTenantDomain(string $host): bool
    {
        return $this->getDomainType($host) === 'TENANT';
    }
}
