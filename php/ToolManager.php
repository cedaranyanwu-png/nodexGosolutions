<?php
/**
 * php/ToolManager.php
 *
 * Automatic Tool Discovery & Integration Service.
 * Scans the /tools/ directory for modular tools, validates config.json definitions,
 * enforces role-based access control (RBAC), and integrates with the sidebar and dashboard loaders.
 */

declare(strict_types=1);

class ToolManager {
    /**
     * Absolute base path to tools directory.
     */
    private string $toolsDir;

    /**
     * Cache array of discovered and validated tool modules.
     */
    private array $discoveredTools = [];

    /**
     * Flag indicating whether discovery scan has completed.
     */
    private bool $isDiscovered = false;

    /**
     * Constructor.
     *
     * @param string|null $toolsDir Custom tools directory path or default /tools/ root.
     */
    public function __construct(?string $toolsDir = null) {
        if ($toolsDir === null) {
            $this->toolsDir = realpath(__DIR__ . '/../tools') ?: (__DIR__ . '/../tools');
        } else {
            $this->toolsDir = $toolsDir;
        }
    }

    /**
     * Scans the tools directory on disk and registers all valid tool modules.
     *
     * @return array Map of discovered tools indexed by slug.
     */
    public function discover(): array {
        if ($this->isDiscovered) {
            return $this->discoveredTools;
        }

        $this->discoveredTools = [];

        if (!is_dir($this->toolsDir)) {
            $this->isDiscovered = true;
            return $this->discoveredTools;
        }

        $items = @scandir($this->toolsDir);
        if ($items === false) {
            $this->isDiscovered = true;
            return $this->discoveredTools;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $toolFolder = $this->toolsDir . '/' . $item;
            if (!is_dir($toolFolder)) {
                continue;
            }

            $configFile = $toolFolder . '/config.json';
            if (!is_file($configFile)) {
                continue;
            }

            $configContent = @file_get_contents($configFile);
            if ($configContent === false) {
                continue;
            }

            $configData = json_decode($configContent, true);
            if (!is_array($configData)) {
                continue;
            }

            // Validate configuration schema
            $validatedTool = $this->validateConfig($configData, $toolFolder, $item);
            if ($validatedTool !== null) {
                $this->discoveredTools[$validatedTool['slug']] = $validatedTool;
            }
        }

        $this->isDiscovered = true;
        return $this->discoveredTools;
    }

    /**
     * Validates config.json attributes and ensures safe file path boundaries.
     *
     * @param array $config Raw config data array.
     * @param string $toolFolder Absolute path to tool folder.
     * @param string $folderName Tool folder name.
     * @return array|null Validated tool metadata array or null if invalid.
     */
    private function validateConfig(array $config, string $toolFolder, string $folderName): ?array {
        // Name & Slug checks
        $name = trim((string)($config['name'] ?? $folderName));
        $slug = strtolower(trim((string)($config['slug'] ?? $folderName)));

        if (empty($slug) || !preg_match('/^[a-z0-9\-_]+$/', $slug)) {
            return null;
        }

        // Entry file check
        $entryFile = trim((string)($config['entry'] ?? 'index.php'));
        if (empty($entryFile)) {
            $entryFile = 'index.php';
        }

        // Prevent path traversal in entry file
        if (str_contains($entryFile, '..')) {
            return null;
        }

        $fullEntryPath = $toolFolder . '/' . ltrim($entryFile, '/');
        $realToolFolder = realpath($toolFolder);
        $realEntryPath = realpath($fullEntryPath);

        if ($realToolFolder === false || $realEntryPath === false || !is_file($realEntryPath)) {
            return null;
        }

        // Ensure entry path stays inside tool folder
        if (!str_starts_with($realEntryPath, $realToolFolder)) {
            return null;
        }

        // Enabled flag
        $enabled = isset($config['enabled']) ? (bool)$config['enabled'] : true;

        // Roles extraction
        $allowedRoles = [];
        if (isset($config['access']['roles']) && is_array($config['access']['roles'])) {
            $allowedRoles = $config['access']['roles'];
        } elseif (isset($config['roles']) && is_array($config['roles'])) {
            $allowedRoles = $config['roles'];
        } elseif (isset($config['access']['roles']) && is_string($config['access']['roles'])) {
            $allowedRoles = [$config['access']['roles']];
        } elseif (isset($config['roles']) && is_string($config['roles'])) {
            $allowedRoles = [$config['roles']];
        } else {
            // Default roles if unassigned
            $allowedRoles = ['user', 'tenant', 'admin', 'superadmin', 'super admin'];
        }

        $normalizedRoles = array_map(function($r) {
            return strtolower(trim((string)$r));
        }, $allowedRoles);

        // Visibility settings
        $sidebarVisible = true;
        if (isset($config['visibility']['sidebar'])) {
            $sidebarVisible = (bool)$config['visibility']['sidebar'];
        } elseif (isset($config['sidebar']) && is_bool($config['sidebar'])) {
            $sidebarVisible = $config['sidebar'];
        }

        $dashboardVisible = true;
        if (isset($config['visibility']['dashboard'])) {
            $dashboardVisible = (bool)$config['visibility']['dashboard'];
        }

        // Sidebar metadata
        $sidebarSection = 'Tools';
        $sidebarLabel = $name;
        $sidebarIcon = 'fa-solid fa-cube';
        $sidebarOrder = 10;

        if (isset($config['sidebar']) && is_array($config['sidebar'])) {
            $sidebarSection = trim((string)($config['sidebar']['section'] ?? 'Tools'));
            $sidebarLabel = trim((string)($config['sidebar']['label'] ?? $name));
            $iconRaw = trim((string)($config['sidebar']['icon'] ?? 'fa-solid fa-cube'));

            if (!empty($iconRaw)) {
                if (!str_contains($iconRaw, 'fa-')) {
                    $sidebarIcon = 'fa-solid fa-' . ltrim($iconRaw, 'fa-');
                } else {
                    $sidebarIcon = $iconRaw;
                }
            }

            $sidebarOrder = (int)($config['sidebar']['order'] ?? 10);
        }

        return [
            'name' => $name,
            'slug' => $slug,
            'description' => trim((string)($config['description'] ?? '')),
            'version' => trim((string)($config['version'] ?? '1.0.0')),
            'entry' => $entryFile,
            'entry_path' => $realEntryPath,
            'folder' => $realToolFolder,
            'enabled' => $enabled,
            'roles' => $normalizedRoles,
            'visibility' => [
                'sidebar' => $sidebarVisible,
                'dashboard' => $dashboardVisible
            ],
            'sidebar' => [
                'section' => $sidebarSection,
                'label' => $sidebarLabel,
                'icon' => $sidebarIcon,
                'order' => $sidebarOrder
            ],
            'config' => $config
        ];
    }

    /**
     * Returns all registered tools.
     *
     * @return array List of all discovered tools.
     */
    public function all(): array {
        return array_values($this->discover());
    }

    /**
     * Returns all enabled tools.
     *
     * @return array List of enabled tools.
     */
    public function enabled(): array {
        return array_values(array_filter($this->discover(), function($tool) {
            return (bool)($tool['enabled'] ?? false);
        }));
    }

    /**
     * Retrieves a tool by slug.
     *
     * @param string $slug Tool unique slug.
     * @return array|null Tool metadata array or null if missing.
     */
    public function get(string $slug): ?array {
        $tools = $this->discover();
        $slugClean = strtolower(trim($slug));
        return $tools[$slugClean] ?? null;
    }

    /**
     * Resolves a tool slug (alias of get).
     */
    public function resolve(string $slug): ?array {
        return $this->get($slug);
    }

    /**
     * Verifies if a given user role is authorized to access a specific tool.
     *
     * @param string $slug Tool unique slug.
     * @param string $userRole User role string.
     * @return bool True if authorized and enabled, false otherwise.
     */
    public function canAccess(string $slug, string $userRole): bool {
        $tool = $this->get($slug);
        if (!$tool || !($tool['enabled'] ?? false)) {
            return false;
        }

        $roleClean = strtolower(trim($userRole));
        $roleNormalized = str_replace(' ', '', str_replace('_', '', $roleClean));

        $allowed = $tool['roles'] ?? [];

        if (in_array('*', $allowed, true) || in_array('all', $allowed, true)) {
            return true;
        }

        // Check exact or normalized matches
        foreach ($allowed as $r) {
            $rClean = strtolower(trim($r));
            $rNorm = str_replace(' ', '', str_replace('_', '', $rClean));

            if ($rClean === $roleClean || $rNorm === $roleNormalized) {
                return true;
            }

            // Universal admin mapping checks
            if (($roleNormalized === 'admin' || $roleNormalized === 'superadmin') && ($rNorm === 'admin' || $rNorm === 'superadmin' || $rNorm === 'user' || $rNorm === 'tenant')) {
                return true;
            }

            // Generic user/tenant mapping check
            if (($roleNormalized === 'tenant' || $roleNormalized === 'user') && ($rNorm === 'user' || $rNorm === 'tenant')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns list of tools visible in the sidebar for a specific user role.
     *
     * @param string $userRole Active user role string.
     * @return array Filtered list of tools sorted by order.
     */
    public function getSidebarItems(string $userRole): array {
        $tools = $this->enabled();
        $items = [];

        foreach ($tools as $tool) {
            if (!($tool['visibility']['sidebar'] ?? true)) {
                continue;
            }

            if ($this->canAccess($tool['slug'], $userRole)) {
                $items[] = $tool;
            }
        }

        // Sort items by sidebar order
        usort($items, function($a, $b) {
            $orderA = (int)($a['sidebar']['order'] ?? 10);
            $orderB = (int)($b['sidebar']['order'] ?? 10);
            return $orderA <=> $orderB;
        });

        return $items;
    }
}
