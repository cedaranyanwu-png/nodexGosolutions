<?php

declare(strict_types=1);

namespace Main\Config;

use InvalidArgumentException;
use RuntimeException;

class DomainConfig
{
    private static ?array $configCache = null;
    private static ?string $configPath = null;

    public static function setConfigPath(string $path): void
    {
        self::$configPath = $path;
        self::$configCache = null;
    }

    private static function getFilePath(): string
    {
        if (self::$configPath !== null) {
            return self::$configPath;
        }
        return __DIR__ . '/domain_config.json';
    }

    public static function reload(): void
    {
        self::$configCache = null;
        self::load();
    }

    public static function load(): array
    {
        if (self::$configCache !== null) {
            return self::$configCache;
        }

        $filePath = self::getFilePath();
        if (!file_exists($filePath)) {
            throw new RuntimeException("Domain configuration file not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if ($content === false || trim($content) === '') {
            throw new RuntimeException("Domain configuration file is empty: {$filePath}");
        }

        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Invalid JSON in domain configuration file: " . json_last_error_msg());
        }

        self::validate($data);
        self::$configCache = $data;

        return self::$configCache;
    }

    public static function validate(array $config): void
    {
        if (empty($config['main_domain']) || !is_string($config['main_domain'])) {
            throw new InvalidArgumentException("Domain configuration missing required valid 'main_domain' field.");
        }

        if (!isset($config['main_subdomains']) || !is_array($config['main_subdomains'])) {
            throw new InvalidArgumentException("Domain configuration missing required 'main_subdomains' array.");
        }

        $mainDomain = strtolower(trim($config['main_domain']));
        if (!filter_var($mainDomain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) && $mainDomain !== 'localhost') {
            throw new InvalidArgumentException("Invalid 'main_domain' format: {$mainDomain}");
        }

        // Check for duplicate subdomain mappings or conflicting values
        $seenHosts = [$mainDomain];
        foreach ($config['main_subdomains'] as $key => $subHost) {
            $subHostLower = strtolower(trim((string)$subHost));
            if (in_array($subHostLower, $seenHosts, true)) {
                throw new InvalidArgumentException("Conflicting or duplicate domain/subdomain mapping: {$subHostLower}");
            }
            $seenHosts[] = $subHostLower;
        }
    }

    public static function mainDomain(): string
    {
        $config = self::load();
        return strtolower(trim($config['main_domain']));
    }

    public static function mainSubdomains(): array
    {
        $config = self::load();
        $subdomains = [];
        foreach ($config['main_subdomains'] as $key => $val) {
            $subdomains[$key] = strtolower(trim((string)$val));
        }
        return $subdomains;
    }

    public static function subdomain(string $key): ?string
    {
        $subdomains = self::mainSubdomains();
        return $subdomains[$key] ?? null;
    }

    public static function isMainDomain(string $host): bool
    {
        $cleanHost = self::normalizeHost($host);
        return $cleanHost === self::mainDomain();
    }

    public static function isMainSubdomain(string $host): bool
    {
        $cleanHost = self::normalizeHost($host);
        $subdomains = self::mainSubdomains();
        return in_array($cleanHost, array_values($subdomains), true);
    }

    public static function isMainHost(string $host): bool
    {
        return self::isMainDomain($host) || self::isMainSubdomain($host);
    }

    public static function generateTenantSubdomain(string $subdomainPrefix): string
    {
        $prefix = preg_replace('/[^a-z0-9-]/', '', strtolower($subdomainPrefix));
        return $prefix . '.' . self::mainDomain();
    }

    public static function normalizeHost(string $host): string
    {
        $clean = strtolower(trim($host));
        if (str_contains($clean, ':')) {
            $clean = explode(':', $clean)[0];
        }
        return $clean;
    }
}
