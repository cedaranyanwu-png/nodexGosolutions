<?php
/**
 * backend/services/WebsiteService.php
 *
 * Central Website Workspace Service
 * Coordinates website creation, template deployment, subdomain allocation,
 * workspace mapping, and lifecycle operations (suspension/deletion).
 */

declare(strict_types=1);

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/SubscriptionService.php';
require_once __DIR__ . '/CpanelService.php';

class WebsiteService {
    private Database $db;
    private SubscriptionService $subscriptionService;
    private CpanelService $cpanelService;

    public function __construct(?Database $db = null) {
        global $conn;
        $this->db = $db ?? $conn;
        $this->subscriptionService = new SubscriptionService($this->db);
        $this->cpanelService = new CpanelService();
        $this->db->createTable('websites');
    }

    public function createWebsite(array $user, string $name, string $subdomain, ?string $templateFolder = null, ?string $customDomain = null): array {
        $name = cleanInput($name);
        $subdomain = strtolower(trim($subdomain));
        $subdomain = preg_replace('/[^a-z0-9\-]/', '', $subdomain);

        if (empty($name) || empty($subdomain)) {
            return ['success' => false, 'message' => 'Website name and subdomain prefix are required.'];
        }

        if (strlen($subdomain) < 3) {
            return ['success' => false, 'message' => 'Subdomain must be at least 3 characters.'];
        }

        // Check user subscription limits
        if (!$this->subscriptionService->canCreateWebsite($user)) {
            return [
                'success' => false,
                'message' => 'Website creation limit reached or subscription is inactive. Please upgrade your plan.'
            ];
        }

        // Check subdomain uniqueness
        $existing = $this->db->selectOne('websites', ['subdomain' => $subdomain]);
        if ($existing) {
            return ['success' => false, 'message' => "Subdomain '{$subdomain}' is already taken. Please choose another."];
        }

        $activeWorkspace = getActiveWorkspace($user);
        $workspaceId = (int)$activeWorkspace['id'];

        $targetDir = TENANT_PUBLIC_DIR . '/' . $subdomain;

        // Create workspace directory natively using PHP
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        // Deploy template if selected
        if (!empty($templateFolder)) {
            $this->deployTemplate($templateFolder, $targetDir);
        } else {
            // Default index HTML placeholder
            $defaultHtml = "<!DOCTYPE html>\n<html>\n<head><title>" . htmlspecialchars($name) . "</title></head>\n<body style='font-family:sans-serif;text-align:center;padding:50px;'>\n<h1>Welcome to " . htmlspecialchars($name) . "</h1>\n<p>Your website space has been provisioned successfully!</p>\n</body>\n</html>";
            @file_put_contents($targetDir . '/index.html', $defaultHtml);
        }

        // Handle custom domain if requested
        $assignedCustomDomain = null;
        $domainStatus = 'subdomain_only';
        $customDomainMsg = '';

        if (!empty($customDomain)) {
            $customDomainClean = strtolower(trim($customDomain));
            $customDomainClean = preg_replace('/^https?:\/\//', '', $customDomainClean);
            $customDomainClean = rtrim($customDomainClean, '/');

            if ($this->subscriptionService->canConnectCustomDomain($user)) {
                $assignedCustomDomain = $customDomainClean;
                $domainStatus = 'active';
                $this->cpanelService->addCustomDomain($customDomainClean, $targetDir);
            } else {
                $customDomainMsg = ' Note: Custom domain requires a Growth (₦25,000) or Business Pro (₦75,000) plan upgrade.';
            }
        }

        $publicUrl = tenantWebsiteUrl($subdomain);
        $website = $this->db->insert('websites', [
            'user_id' => $user['id'],
            'workspace_id' => $workspaceId,
            'name' => $name,
            'subdomain' => $subdomain,
            'url' => $publicUrl,
            'custom_domain' => $assignedCustomDomain,
            'domain_status' => $domainStatus,
            'template' => $templateFolder,
            'is_suspended' => 0,
            'monetization_status' => 'active',
            'ad_placements' => json_encode(['header' => 1, 'footer' => 1]),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        if (is_array($website) && empty($website['url'])) {
            $website['url'] = $publicUrl;
        }

        if ($assignedCustomDomain) {
            $this->db->createTable('domains');
            $this->db->insert('domains', [
                'website_id' => $website['id'],
                'user_id' => $user['id'],
                'domain_name' => $assignedCustomDomain,
                'domain_type' => 'custom',
                'cpanel_status' => 'mapped',
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        return [
            'success' => true,
            'message' => 'Website workspace created successfully.' . $customDomainMsg,
            'website' => $website
        ];
    }

    public function getUserWebsites(int $userId): array {
        return $this->db->select('websites', ['user_id' => $userId]) ?: [];
    }

    public function getWebsiteById(int $id): ?array {
        return $this->db->selectOne('websites', ['id' => $id]);
    }

    public function deleteWebsite(array $user, int $websiteId): array {
        $website = $this->getWebsiteById($websiteId);
        if (!$website) {
            return ['success' => false, 'message' => 'Website workspace not found.'];
        }

        $uRole = strtolower((string)($user['role'] ?? ''));
        $isAdmin = in_array($uRole, ['admin', 'super admin', 'superadmin'], true);

        if (!$isAdmin && (int)$website['user_id'] !== (int)$user['id']) {
            return ['success' => false, 'message' => 'Unauthorized to delete this website workspace.'];
        }

        // Delete physical files
        $dir = TENANT_PUBLIC_DIR . '/' . $website['subdomain'];
        if (is_dir($dir)) {
            $this->recursiveDeleteDir($dir);
        }

        $this->db->delete('websites', ['id' => $websiteId]);
        $this->db->delete('domains', ['website_id' => $websiteId]);

        return ['success' => true, 'message' => 'Website workspace deleted successfully.'];
    }

    public function toggleSuspension(int $websiteId, bool $suspend): array {
        $website = $this->getWebsiteById($websiteId);
        if (!$website) {
            return ['success' => false, 'message' => 'Website not found.'];
        }

        $this->db->update('websites', [
            'is_suspended' => $suspend ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $websiteId]);

        $statusStr = $suspend ? 'suspended' : 'unsuspended';
        return ['success' => true, 'message' => "Website workspace '{$website['subdomain']}' has been {$statusStr}."];
    }

    private function deployTemplate(string $templateFolder, string $targetDir): void {
        $templatesDir = realpath(__DIR__ . '/../../templates');
        if (!$templatesDir) return;

        // Search for template path
        $sourcePath = null;
        $categories = scandir($templatesDir);
        foreach ($categories as $cat) {
            if ($cat !== '.' && $cat !== '..' && is_dir($templatesDir . '/' . $cat . '/' . $templateFolder)) {
                $sourcePath = $templatesDir . '/' . $cat . '/' . $templateFolder;
                break;
            }
        }

        if ($sourcePath && is_dir($sourcePath)) {
            $this->recursiveCopyDir($sourcePath, $targetDir);
        }
    }

    private function recursiveCopyDir(string $src, string $dst): void {
        $dir = opendir($src);
        @mkdir($dst, 0755, true);
        while (($file = readdir($dir)) !== false) {
            if ($file !== '.' && $file !== '..' && $file !== 'template.json') {
                if (is_dir($src . '/' . $file)) {
                    $this->recursiveCopyDir($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    private function recursiveDeleteDir(string $dir): void {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->recursiveDeleteDir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
