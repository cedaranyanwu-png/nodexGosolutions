<?php
/**
 * sys_router.php
 *
 * Central Request Router and Domain Isolation Engine.
 * Evaluates host context and routes appropriately between MAIN, MAIN-CONNECTED, and TENANT contexts.
 */

declare(strict_types=1);

require_once __DIR__ . '/sys_config.php';
require_once __DIR__ . '/DomainManager.php';
require_once __DIR__ . '/TenantManager.php';
require_once __DIR__ . '/sys_services.php';

class SysRouter
{
    private TenantManager $tenantManager;
    private DomainManager $domainManager;

    public function __construct(?TenantManager $tenantManager = null, ?DomainManager $domainManager = null)
    {
        $this->tenantManager = $tenantManager ?? new TenantManager();
        $this->domainManager = $domainManager ?? new DomainManager();
    }

    /**
     * Dispatch incoming request based on HTTP_HOST and tenant detection.
     */
    public function dispatch(?string $host = null): void
    {
        $host = $host ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $domainType = $this->domainManager->getDomainType($host);
        $tenant = $this->tenantManager->getTenantByHost($host) ?? $this->tenantManager->getDefaultTenant();

        // Pass context variables into view scope
        $context = [
            'domain_type' => $domainType,
            'tenant' => $tenant,
            'main_domain' => $this->domainManager->getMainDomain()
        ];

        switch ($domainType) {
            case 'MAIN':
                $this->renderMain($context);
                break;

            case 'MAIN-CONNECTED':
                $this->renderMainConnected($context);
                break;

            case 'TENANT':
            default:
                $this->renderTenant($context);
                break;
        }
    }

    /**
     * Render MAIN Website & OS Control Center.
     */
    private function renderMain(array $context): void
    {
        $tenant = $context['tenant'];
        $mainIndexPath = dirname(__DIR__, 2) . '/main/index.php';

        if (file_exists($mainIndexPath)) {
            require $mainIndexPath;
        } else {
            $sysTemplate = dirname(__DIR__, 2) . '/sys_template.php';
            if (file_exists($sysTemplate)) {
                require $sysTemplate;
            }
        }
    }

    /**
     * Render MAIN-CONNECTED public website with full access to /main/ services.
     */
    private function renderMainConnected(array $context): void
    {
        $tenant = $context['tenant'];
        // Enable central OS services in scope
        $sysServices = new SysServices();

        $tenantPath = $tenant['path'] ?? 'public/about';
        $connectedFile = dirname(__DIR__, 2) . '/' . ltrim($tenantPath, '/') . '/index.php';

        if (file_exists($connectedFile)) {
            require $connectedFile;
        } else {
            $sysTemplate = dirname(__DIR__, 2) . '/sys_template.php';
            if (file_exists($sysTemplate)) {
                require $sysTemplate;
            }
        }
    }

    /**
     * Render ISOLATED TENANT website.
     */
    private function renderTenant(array $context): void
    {
        $tenant = $context['tenant'];
        $tenantPath = $tenant['path'] ?? 'public/customer1';
        $tenantFile = dirname(__DIR__, 2) . '/' . ltrim($tenantPath, '/') . '/index.php';

        if (file_exists($tenantFile)) {
            require $tenantFile;
        } else {
            $sysTemplate = dirname(__DIR__, 2) . '/sys_template.php';
            if (file_exists($sysTemplate)) {
                require $sysTemplate;
            }
        }
    }
}
