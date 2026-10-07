<?php
/**
 * backend/services/DomainService.php
 *
 * Domain Management Service
 * Enforces plan eligibility rules for custom domains, handles cPanel mapping,
 * and maintains database consistency between user websites and registered domains.
 */

declare(strict_types=1);

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/SubscriptionService.php';
require_once __DIR__ . '/CpanelService.php';

class DomainService {
    private Database $db;
    private SubscriptionService $subscriptionService;
    private CpanelService $cpanelService;

    public function __construct(?Database $db = null) {
        global $conn;
        $this->db = $db ?? $conn;
        $this->subscriptionService = new SubscriptionService($this->db);
        $this->cpanelService = new CpanelService();
        $this->db->createTable('domains');
        $this->db->createTable('websites');
    }

    public function connectCustomDomain(array $user, int $websiteId, string $customDomain): array {
        $customDomain = strtolower(trim($customDomain));
        $customDomain = preg_replace('/^https?:\/\//', '', $customDomain);
        $customDomain = rtrim($customDomain, '/');

        if (empty($customDomain) || !filter_var("http://" . $customDomain, FILTER_VALIDATE_URL)) {
            return ['success' => false, 'message' => 'Please enter a valid domain name (e.g. mybrand.com).'];
        }

        // Verify user subscription eligibility for custom domain
        if (!$this->subscriptionService->canConnectCustomDomain($user)) {
            return [
                'success' => false,
                'message' => 'Custom domain connection requires a Growth or Business Pro plan upgrade. Free Trial and Micro plans support platform subdomains only.'
            ];
        }

        // Verify website ownership
        $website = $this->db->selectOne('websites', ['id' => $websiteId, 'user_id' => $user['id']]);
        if (!$website) {
            return ['success' => false, 'message' => 'Website workspace not found or unauthorized.'];
        }

        // Check if custom domain is already connected to another site
        $existingDomain = $this->db->selectOne('domains', ['domain_name' => $customDomain]);
        if ($existingDomain && (int)$existingDomain['website_id'] !== $websiteId) {
            return ['success' => false, 'message' => 'This custom domain is already registered to another website.'];
        }

        $dirPath = TENANT_PUBLIC_DIR . '/' . $website['subdomain'];

        // Trigger cPanel addon domain mapping
        $cpRes = $this->cpanelService->addCustomDomain($customDomain, $dirPath);

        // Update website record with custom domain
        $this->db->update('websites', [
            'custom_domain' => $customDomain,
            'domain_status' => 'active',
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $websiteId]);

        // Insert or update domain record
        if (!$existingDomain) {
            $this->db->insert('domains', [
                'website_id' => $websiteId,
                'user_id' => $user['id'],
                'domain_name' => $customDomain,
                'domain_type' => 'custom',
                'cpanel_status' => $cpRes['success'] ? 'mapped' : 'pending',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            $this->db->update('domains', [
                'cpanel_status' => 'mapped',
                'is_active' => 1
            ], ['id' => $existingDomain['id']]);
        }

        return [
            'success' => true,
            'message' => "Custom domain '{$customDomain}' connected successfully to website.",
            'cpanel' => $cpRes
        ];
    }

    public function removeCustomDomain(array $user, int $websiteId): array {
        $website = $this->db->selectOne('websites', ['id' => $websiteId, 'user_id' => $user['id']]);
        if (!$website) {
            return ['success' => false, 'message' => 'Website workspace not found or unauthorized.'];
        }

        $customDomain = $website['custom_domain'] ?? '';
        if (!empty($customDomain)) {
            $this->cpanelService->removeCustomDomain($customDomain, $website['subdomain']);
            $this->db->delete('domains', ['website_id' => $websiteId, 'domain_name' => $customDomain]);
        }

        $this->db->update('websites', [
            'custom_domain' => null,
            'domain_status' => 'subdomain_only',
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $websiteId]);

        return ['success' => true, 'message' => 'Custom domain disconnected successfully.'];
    }

    public function getWebsiteDomains(int $websiteId): array {
        return $this->db->select('domains', ['website_id' => $websiteId]) ?: [];
    }
}
