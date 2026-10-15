<?php
/**
 * TenantManager.php
 *
 * This file contains the TenantManager class which is responsible for
 * detecting the current tenant based on the HTTP request host and
 * retrieving corresponding tenant data.
 */

declare(strict_types=1);

class TenantManager
{
    /**
     * @var array $tenants
     * Hardcoded associative array acting as mock database.
     */
    private array $tenants = [
        [
            'id'         => 1,
            'identifier' => 'nodexgosolutions.com',
            'name'       => 'NodeX Go Solutions HQ',
            'theme_color'=> '#2c3e50',
            'logo_url'   => 'https://placehold.co/200x50/2c3e50/ffffff?text=NodeX+HQ',
            'content'    => 'Welcome to the central hub of NodeX Go Solutions. We provide modular PHP & Python WebSocket architectures.'
        ],
        [
            'id'         => 2,
            'identifier' => 'tenant1.nodexgosolutions.com',
            'name'       => 'Acme Corp Portal',
            'theme_color'=> '#27ae60',
            'logo_url'   => 'https://placehold.co/200x50/27ae60/ffffff?text=Acme+Corp',
            'content'    => 'This is the private portal for Acme Corp. Managed real-time by NodeX Go Solutions.'
        ],
        [
            'id'         => 3,
            'identifier' => 'custom-tenant.com',
            'name'       => 'Independent Ventures',
            'theme_color'=> '#e67e22',
            'logo_url'   => 'https://placehold.co/200x50/e67e22/ffffff?text=Independent',
            'content'    => 'Welcome to Independent Ventures. Powered by our multi-tenant real-time engine.'
        ],
        [
            'id'         => 4,
            'identifier' => 'localhost',
            'name'       => 'Local Development',
            'theme_color'=> '#9b59b6',
            'logo_url'   => 'https://placehold.co/200x50/9b59b6/ffffff?text=Local+Dev',
            'content'    => 'You are viewing the local development environment with WebSocket support.'
        ],
    ];

    /**
     * getTenantByHost
     */
    public function getTenantByHost(string $host): ?array
    {
        foreach ($this->tenants as $tenant) {
            if (strcasecmp($tenant['identifier'], $host) === 0) {
                return $tenant;
            }
        }
        return null;
    }

    /**
     * getDefaultTenant
     */
    public function getDefaultTenant(): array
    {
        return [
            'id'         => 0,
            'identifier' => 'default',
            'name'       => 'Generic NodeX Instance',
            'theme_color'=> '#7f8c8d',
            'logo_url'   => 'https://placehold.co/200x50/7f8c8d/ffffff?text=Default+NodeX',
            'content'    => 'The domain you requested is not registered in our system. Showing default content.'
        ];
    }

    /**
     * getAllTenants
     */
    public function getAllTenants(): array
    {
        return $this->tenants;
    }
}
