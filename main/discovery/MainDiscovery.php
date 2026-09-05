<?php

declare(strict_types=1);

namespace Main\Discovery;

use RuntimeException;

class MainDiscovery
{
    private string $mainPath;
    private array $discoveredCache = [];

    public function __construct(?string $mainPath = null)
    {
        $this->mainPath = $mainPath ?? dirname(__DIR__);
    }

    public function discoverModules(): array
    {
        if (isset($this->discoveredCache['modules'])) {
            return $this->discoveredCache['modules'];
        }

        $modulesDir = $this->mainPath . '/modules';
        $modules = [];

        if (is_dir($modulesDir)) {
            $dirs = scandir($modulesDir);
            foreach ($dirs as $dir) {
                if ($dir === '.' || $dir === '..') {
                    continue;
                }

                $modulePath = $modulesDir . '/' . $dir;
                if (!is_dir($modulePath)) {
                    continue;
                }

                $metaFile = $this->findMetaFile($modulePath);
                if ($metaFile !== null) {
                    $meta = json_decode(file_get_contents($metaFile), true);
                    if (is_array($meta) && !empty($meta['slug'])) {
                        $meta['path'] = $modulePath;
                        $meta['type'] = 'module';
                        $meta['enabled'] = $meta['enabled'] ?? true;
                        $modules[$meta['slug']] = $meta;
                    }
                }
            }
        }

        $this->discoveredCache['modules'] = $modules;
        return $modules;
    }

    public function discoverServices(): array
    {
        if (isset($this->discoveredCache['services'])) {
            return $this->discoveredCache['services'];
        }

        $servicesDir = $this->mainPath . '/services';
        $services = [];

        if (is_dir($servicesDir)) {
            $files = scandir($servicesDir);
            foreach ($files as $file) {
                if (str_ends_with($file, '.php')) {
                    $serviceName = basename($file, '.php');
                    $services[$serviceName] = [
                        'name' => $serviceName,
                        'file' => $servicesDir . '/' . $file,
                        'type' => 'service',
                    ];
                }
            }
        }

        $this->discoveredCache['services'] = $services;
        return $services;
    }

    public function discoverRoutes(): array
    {
        if (isset($this->discoveredCache['routes'])) {
            return $this->discoveredCache['routes'];
        }

        $routesDir = $this->mainPath . '/routes';
        $routes = [];

        if (is_dir($routesDir)) {
            $files = scandir($routesDir);
            foreach ($files as $file) {
                if (str_ends_with($file, '.php') || str_ends_with($file, '.json')) {
                    $routes[] = [
                        'file' => $routesDir . '/' . $file,
                        'name' => basename($file),
                    ];
                }
            }
        }

        $this->discoveredCache['routes'] = $routes;
        return $routes;
    }

    public function discoverAll(): array
    {
        return [
            'modules' => $this->discoverModules(),
            'services' => $this->discoverServices(),
            'routes' => $this->discoverRoutes(),
        ];
    }

    public function getModule(string $slug): ?array
    {
        $modules = $this->discoverModules();
        return $modules[$slug] ?? null;
    }

    private function findMetaFile(string $dirPath): ?string
    {
        $candidates = ['module.json', 'config.json', 'manifest.json'];
        foreach ($candidates as $candidate) {
            $file = $dirPath . '/' . $candidate;
            if (file_exists($file)) {
                return $file;
            }
        }
        return null;
    }

    public static function isPublicTenantFolderDiscoverable(string $folderName): bool
    {
        // Public folders are NEVER automatically self-discovered by rule!
        return false;
    }
}
