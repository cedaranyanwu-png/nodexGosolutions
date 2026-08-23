<?php
/**
 * php/SystemDiscovery.php
 *
 * Universal System Discovery & Integration Engine.
 * Automatically discovers new tools, projects, and services inside system folders
 * (/tools, /apps, /system, /modules, /services).
 *
 * Determines RBAC access rights (public, user, tenant, admin, superadmin, etc.)
 * and dynamic placement targets (sidebar, topnav, footer, dashboard, custom buttons).
 */

declare(strict_types=1);

class SystemDiscovery {
    /**
     * Target system directories to scan for discovered tools, apps, and services.
     */
    private array $scanDirectories;

    /**
     * Cache map of discovered items indexed by unique slug.
     */
    private array $discoveredItems = [];

    /**
     * Flag indicating whether the discovery scan has been performed.
     */
    private bool $isDiscovered = false;

    /**
     * Constructor.
     *
     * @param array|null $directories Custom directories or default system scan folders.
     */
    public function __construct(?array $directories = null) {
        $baseDir = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
        if ($directories !== null) {
            $this->scanDirectories = $directories;
        } else {
            $this->scanDirectories = [
                $baseDir . '/tools',
                $baseDir . '/apps',
                $baseDir . '/system',
                $baseDir . '/modules',
                $baseDir . '/services'
            ];
        }
    }

    /**
     * Scans all configured directories on disk and registers valid modules.
     *
     * @return array Map of discovered items indexed by slug.
     */
    public function discover(): array {
        if ($this->isDiscovered) {
            return $this->discoveredItems;
        }

        $this->discoveredItems = [];

        foreach ($this->scanDirectories as $scanDir) {
            if (!is_dir($scanDir)) {
                continue;
            }

            $items = @scandir($scanDir);
            if ($items === false) {
                continue;
            }

            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }

                $folderPath = $scanDir . '/' . $item;
                if (!is_dir($folderPath)) {
                    continue;
                }

                $metaData = $this->loadManifest($folderPath, $item);
                if ($metaData !== null) {
                    $this->discoveredItems[$metaData['slug']] = $metaData;
                }
            }
        }

        $this->isDiscovered = true;
        return $this->discoveredItems;
    }

    /**
     * Loads config.json or manifest.json from folder and normalizes metadata.
     *
     * @param string $folderPath Absolute path to item folder.
     * @param string $folderName Directory name.
     * @return array|null Normalized item array or null if invalid.
     */
    private function loadManifest(string $folderPath, string $folderName): ?array {
        $configFile = null;
        if (is_file($folderPath . '/config.json')) {
            $configFile = $folderPath . '/config.json';
        } elseif (is_file($folderPath . '/manifest.json')) {
            $configFile = $folderPath . '/manifest.json';
        }

        $configData = [];
        if ($configFile !== null) {
            $content = @file_get_contents($configFile);
            if ($content !== false) {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    $configData = $decoded;
                }
            }
        }

        $name = trim((string)($configData['name'] ?? ucwords(str_replace(['_', '-'], ' ', $folderName))));
        $slug = strtolower(trim((string)($configData['slug'] ?? $folderName)));

        if (empty($slug) || !preg_match('/^[a-z0-9\-_]+$/', $slug)) {
            return null;
        }

        // Determine entry file
        $entryFile = trim((string)($configData['entry'] ?? 'index.php'));
        if (!is_file($folderPath . '/' . $entryFile)) {
            if (is_file($folderPath . '/index.html')) {
                $entryFile = 'index.html';
            } elseif (is_file($folderPath . '/index.php')) {
                $entryFile = 'index.php';
            }
        }

        $fullEntryPath = $folderPath . '/' . ltrim($entryFile, '/');
        $realFolder = realpath($folderPath);
        $realEntry = realpath($fullEntryPath);

        if ($realFolder === false || $realEntry === false || !is_file($realEntry)) {
            return null;
        }

        // Path traversal prevention
        if (!str_starts_with($realEntry, $realFolder)) {
            return null;
        }

        // Enabled flag
        $enabled = isset($configData['enabled']) ? (bool)$configData['enabled'] : true;

        // Roles resolution
        $roles = [];
        if (isset($configData['access']['roles']) && is_array($configData['access']['roles'])) {
            $roles = $configData['access']['roles'];
        } elseif (isset($configData['roles']) && is_array($configData['roles'])) {
            $roles = $configData['roles'];
        } elseif (isset($configData['access']['roles']) && is_string($configData['access']['roles'])) {
            $roles = [$configData['access']['roles']];
        } elseif (isset($configData['roles']) && is_string($configData['roles'])) {
            $roles = [$configData['roles']];
        } else {
            // Default roles
            $roles = ['public', 'guest', 'user', 'tenant', 'admin', 'superadmin'];
        }

        $normalizedRoles = array_map(function($r) {
            return strtolower(trim((string)$r));
        }, $roles);

        // Placements resolution (sidebar, topnav, footer, dashboard, buttons)
        $sidebar = true;
        $topnav = false;
        $footer = false;
        $dashboard = true;
        $buttonPlacement = [];

        if (isset($configData['placement']) && is_array($configData['placement'])) {
            $sidebar   = isset($configData['placement']['sidebar']) ? (bool)$configData['placement']['sidebar'] : $sidebar;
            $topnav    = isset($configData['placement']['topnav']) ? (bool)$configData['placement']['topnav'] : $topnav;
            $footer    = isset($configData['placement']['footer']) ? (bool)$configData['placement']['footer'] : $footer;
            $dashboard = isset($configData['placement']['dashboard']) ? (bool)$configData['placement']['dashboard'] : $dashboard;
            if (isset($configData['placement']['buttons']) && is_array($configData['placement']['buttons'])) {
                $buttonPlacement = $configData['placement']['buttons'];
            }
        } elseif (isset($configData['visibility']) && is_array($configData['visibility'])) {
            $sidebar   = isset($configData['visibility']['sidebar']) ? (bool)$configData['visibility']['sidebar'] : $sidebar;
            $topnav    = isset($configData['visibility']['topnav']) ? (bool)$configData['visibility']['topnav'] : $topnav;
            $footer    = isset($configData['visibility']['footer']) ? (bool)$configData['visibility']['footer'] : $footer;
            $dashboard = isset($configData['visibility']['dashboard']) ? (bool)$configData['visibility']['dashboard'] : $dashboard;
        }

        // Category / Section
        $section = trim((string)($configData['section'] ?? $configData['category'] ?? 'System Tools'));

        // Icon
        $icon = trim((string)($configData['icon'] ?? $configData['sidebar']['icon'] ?? 'fa-solid fa-cube'));
        if (!empty($icon) && !str_contains($icon, 'fa-')) {
            $icon = 'fa-solid fa-' . ltrim($icon, 'fa-');
        }

        // Order
        $order = (int)($configData['order'] ?? $configData['sidebar']['order'] ?? 10);

        // Construct target web URL path
        $relParent = basename(dirname($realFolder));
        $urlPath = '/' . $relParent . '/' . $slug;

        return [
            'name' => $name,
            'slug' => $slug,
            'description' => trim((string)($configData['description'] ?? '')),
            'version' => trim((string)($configData['version'] ?? '1.0.0')),
            'entry' => $entryFile,
            'entry_path' => $realEntry,
            'folder' => $realFolder,
            'url' => $urlPath,
            'enabled' => $enabled,
            'roles' => $normalizedRoles,
            'placement' => [
                'sidebar' => $sidebar,
                'topnav' => $topnav,
                'footer' => $footer,
                'dashboard' => $dashboard,
                'buttons' => $buttonPlacement
            ],
            'section' => $section,
            'icon' => $icon,
            'order' => $order,
            'config' => $configData
        ];
    }

    /**
     * Checks if a user role has access rights to a discovered item.
     *
     * @param string $slug Slug of discovered item or raw item array.
     * @param string $userRole Active user role string ('public', 'user', 'admin', etc.).
     * @return bool True if authorized, false otherwise.
     */
    public function canAccess(string|array $item, string $userRole = 'public'): bool {
        $data = is_array($item) ? $item : $this->get($item);
        if (!$data || !($data['enabled'] ?? false)) {
            return false;
        }

        $roleClean = strtolower(trim($userRole));
        if (empty($roleClean)) {
            $roleClean = 'public';
        }

        $roleNorm = str_replace(' ', '', str_replace('_', '', $roleClean));
        $allowed = $data['roles'] ?? [];

        if (in_array('*', $allowed, true) || in_array('all', $allowed, true) || in_array('public', $allowed, true)) {
            if ($roleClean === 'public' || $roleNorm === 'guest') {
                return true;
            }
        }

        if (in_array('*', $allowed, true) || in_array('all', $allowed, true)) {
            return true;
        }

        foreach ($allowed as $r) {
            $rClean = strtolower(trim($r));
            $rNorm = str_replace(' ', '', str_replace('_', '', $rClean));

            if ($rClean === $roleClean || $rNorm === $roleNorm) {
                return true;
            }

            // Admin mapping checks
            if (($roleNorm === 'admin' || $roleNorm === 'superadmin') && ($rNorm === 'admin' || $rNorm === 'superadmin' || $rNorm === 'user' || $rNorm === 'tenant')) {
                return true;
            }

            // Tenant mapping checks
            if (($roleNorm === 'tenant' || $roleNorm === 'user') && ($rNorm === 'user' || $rNorm === 'tenant')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieves items filtered by target placement and user access role.
     *
     * @param string $placement Placement target ('sidebar', 'topnav', 'footer', 'dashboard', 'button').
     * @param string $userRole User role string.
     * @return array List of authorized, enabled items sorted by order.
     */
    public function getByPlacement(string $placement, string $userRole = 'public'): array {
        $items = array_values($this->discover());
        $filtered = [];

        foreach ($items as $item) {
            if (!($item['enabled'] ?? false)) {
                continue;
            }

            $placements = $item['placement'] ?? [];
            $isMatch = false;

            if ($placement === 'button') {
                $isMatch = !empty($placements['buttons']);
            } else {
                $isMatch = !empty($placements[$placement]);
            }

            if ($isMatch && $this->canAccess($item, $userRole)) {
                $filtered[] = $item;
            }
        }

        usort($filtered, function($a, $b) {
            return ((int)($a['order'] ?? 10)) <=> ((int)($b['order'] ?? 10));
        });

        return $filtered;
    }

    /**
     * Retrieves single item by slug.
     */
    public function get(string $slug): ?array {
        $items = $this->discover();
        return $items[strtolower(trim($slug))] ?? null;
    }

    /**
     * Returns all discovered items.
     */
    public function all(): array {
        return array_values($this->discover());
    }
}
