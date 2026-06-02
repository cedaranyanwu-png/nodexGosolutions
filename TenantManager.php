<?php
/**
 * TenantManager.php
 *
 * This file contains the TenantManager class which is responsible for
 * detecting the current tenant based on the HTTP request host and
 * retrieving the corresponding mock data.
 */

declare(strict_types=1);

/**
 * Class TenantManager
 *
 * Handles multi-tenancy logic by mapping domains/subdomains to specific tenant data.
 */
class TenantManager
{
    /**
     * @var array $tenants
     * A hardcoded associative array acting as a mock database.
     * Each entry represents a tenant with its configuration and content.
     */
    private array $tenants = [
        // Tenant 1: The primary domain entry
        [
            'id'         => 1,
            'identifier' => 'nodexgosolutions.com', // The domain name to match
            'name'       => 'NodeX Go Solutions HQ', // Display name
            'theme_color'=> '#2c3e50',               // Primary brand color (Midnight Blue)
            'logo_url'   => 'https://placehold.co/200x50/2c3e50/ffffff?text=NodeX+HQ', // Logo placeholder
            'content'    => 'Welcome to the central hub of NodeX Go Solutions. We provide modular PHP architectures.'
        ],
        // Tenant 2: A subdomain-based tenant
        [
            'id'         => 2,
            'identifier' => 'tenant1.nodexgosolutions.com', // The subdomain to match
            'name'       => 'Acme Corp Portal',             // Tenant specific name
            'theme_color'=> '#27ae60',                      // Tenant brand color (Nephritis Green)
            'logo_url'   => 'https://placehold.co/200x50/27ae60/ffffff?text=Acme+Corp', // Logo placeholder
            'content'    => 'This is the private portal for Acme Corp. Managed by NodeX Go Solutions.'
        ],
        // Tenant 3: A custom apex domain tenant
        [
            'id'         => 3,
            'identifier' => 'custom-tenant.com',       // A completely different domain
            'name'       => 'Independent Ventures',    // Tenant specific name
            'theme_color'=> '#e67e22',                 // Tenant brand color (Carrot Orange)
            'logo_url'   => 'https://placehold.co/200x50/e67e22/ffffff?text=Independent', // Logo placeholder
            'content'    => 'Welcome to Independent Ventures. Powered by our multi-tenant engine.'
        ],
        // Tenant 4: For local development testing
        [
            'id'         => 4,
            'identifier' => 'localhost',               // Matching localhost
            'name'       => 'Local Development',
            'theme_color'=> '#9b59b6',                 // Amethyst purple
            'logo_url'   => 'https://placehold.co/200x50/9b59b6/ffffff?text=Local+Dev',
            'content'    => 'You are viewing the local development environment.'
        ],
    ];

    /**
     * getTenantByHost
     *
     * Parses the incoming host string and searches the mock data for a match.
     *
     * @param string $host The value of $_SERVER['HTTP_HOST']
     * @return array|null Returns the tenant data array if found, otherwise null.
     */
    public function getTenantByHost(string $host): ?array
    {
        // We iterate through each tenant in our mock "database"
        foreach ($this->tenants as $tenant) {
            // We perform a case-insensitive comparison of the identifier and the host
            if (strcasecmp($tenant['identifier'], $host) === 0) {
                // If a match is found, we return the entire tenant configuration
                return $tenant;
            }
        }

        // If the loop completes without a match, we return null
        return null;
    }

    /**
     * getDefaultTenant
     *
     * Provides a fallback configuration in case the domain is not recognized.
     *
     * @return array A default set of tenant data.
     */
    public function getDefaultTenant(): array
    {
        // Return a generic configuration to keep the site functional
        return [
            'id'         => 0,
            'identifier' => 'default',
            'name'       => 'Generic NodeX Instance',
            'theme_color'=> '#7f8c8d', // Asbestos gray
            'logo_url'   => 'https://placehold.co/200x50/7f8c8d/ffffff?text=Default+NodeX',
            'content'    => 'The domain you requested is not registered in our system. Showing default content.'
        ];
    }
}
