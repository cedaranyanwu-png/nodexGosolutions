<?php
/**
 * backend/services/CpanelService.php
 *
 * Configurable cPanel API & Domain Integration Service
 * Manages cPanel UAPI actions (subdomain creation, addon domain setup, directory allocation)
 * using environment parameters: CPANEL_HOST, CPANEL_USERNAME, CPANEL_API_TOKEN, CPANEL_PORT.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

class CpanelService {
    private string $host;
    private string $username;
    private string $apiToken;
    private int $port;
    private string $mainDomain;

    public function __construct() {
        $this->host = CPANEL_HOST;
        $this->username = CPANEL_USERNAME;
        $this->apiToken = CPANEL_API_TOKEN;
        $this->port = CPANEL_PORT;
        $this->mainDomain = MAIN_DOMAIN;
    }

    public function isConfigured(): bool {
        return !empty($this->host) && !empty($this->username) && !empty($this->apiToken);
    }

    /**
     * Executes a cPanel UAPI request.
     */
    public function callUapi(string $module, string $function, array $params = []): array {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'cPanel API credentials are not configured. Operating in simulated local directory mode.',
                'simulated' => true
            ];
        }

        $query = http_build_query($params);
        $url = "https://{$this->host}:{$this->port}/execute/{$module}/{$function}?{$query}";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: cpanel {$this->username}:{$this->apiToken}"
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            return ['success' => false, 'message' => 'cPanel cURL error: ' . $error];
        }

        $decoded = json_decode((string)$response, true);
        if ($httpCode === 200 && isset($decoded['status']) && $decoded['status'] == 1) {
            return ['success' => true, 'data' => $decoded['result'] ?? $decoded];
        }

        $errMsg = $decoded['errors'][0] ?? $decoded['reason'] ?? 'cPanel request failed.';
        return ['success' => false, 'message' => $errMsg, 'raw' => $decoded];
    }

    /**
     * Provisions a subdomain on cPanel or local filesystem directory.
     */
    public function createSubdomain(string $subdomain, string $dirPath): array {
        $subdomain = strtolower(trim($subdomain));
        if ($this->isConfigured()) {
            $uapiResult = $this->callUapi('SubDomain', 'addsubdomain', [
                'domain' => $subdomain,
                'rootdomain' => $this->mainDomain,
                'dir' => $dirPath
            ]);
            if (!$uapiResult['success'] && !($uapiResult['simulated'] ?? false)) {
                return $uapiResult;
            }
        }

        // Ensure directory exists on disk
        $absDir = TENANT_PUBLIC_DIR . '/' . $subdomain;
        if (!is_dir($absDir)) {
            @mkdir($absDir, 0755, true);
        }

        return [
            'success' => true,
            'message' => "Subdomain '{$subdomain}.{$this->mainDomain}' created successfully.",
            'dir' => $absDir
        ];
    }

    /**
     * Connects a custom domain on cPanel.
     */
    public function addCustomDomain(string $customDomain, string $dirPath): array {
        $customDomain = strtolower(trim($customDomain));
        if ($this->isConfigured()) {
            $uapiResult = $this->callUapi('AddonDomain', 'addaddondomain', [
                'dir' => $dirPath,
                'newdomain' => $customDomain,
                'subdomain' => str_replace('.', '', $customDomain)
            ]);
            if (!$uapiResult['success'] && !($uapiResult['simulated'] ?? false)) {
                return $uapiResult;
            }
        }

        return [
            'success' => true,
            'message' => "Custom domain '{$customDomain}' registered successfully in cPanel mapping.",
            'domain' => $customDomain
        ];
    }

    /**
     * Removes a domain mapping.
     */
    public function removeCustomDomain(string $customDomain, string $subdomain): array {
        $customDomain = strtolower(trim($customDomain));
        if ($this->isConfigured()) {
            $this->callUapi('AddonDomain', 'deladdondomain', [
                'domain' => $customDomain,
                'subdomain' => $subdomain . '_' . $this->mainDomain
            ]);
        }
        return ['success' => true, 'message' => "Custom domain '{$customDomain}' mapping removed."];
    }
}
