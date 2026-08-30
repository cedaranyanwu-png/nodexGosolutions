<?php
/**
 * sys_config.php
 *
 * Central Dynamic Configuration Engine.
 * Automatically discovers and loads all JSON configuration files inside /databases/config/
 * without relying on hardcoded file lists.
 */

declare(strict_types=1);

class SysConfig
{
    private static ?SysConfig $instance = null;
    private array $config = [];
    private string $configDir;

    /**
     * Private constructor for singleton pattern (or standalone instantiation).
     */
    public function __construct(?string $configDir = null)
    {
        $this->configDir = $configDir ?? dirname(__DIR__, 2) . '/databases/config';
        $this->loadAllConfigurations();
    }

    /**
     * Get the singleton instance of SysConfig.
     */
    public static function getInstance(): SysConfig
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Dynamically discover and load all *.json files in the configuration directory.
     */
    public function loadAllConfigurations(): void
    {
        $this->config = [];
        if (!is_dir($this->configDir)) {
            return;
        }

        $jsonFiles = glob(rtrim($this->configDir, '/\\') . '/*.json');
        if ($jsonFiles === false) {
            return;
        }

        foreach ($jsonFiles as $file) {
            if (is_file($file) && is_readable($file)) {
                $content = file_get_contents($file);
                if ($content !== false) {
                    $decoded = json_decode($content, true);
                    if (is_array($decoded)) {
                        $this->config = array_replace_recursive($this->config, $decoded);
                    }
                }
            }
        }
    }

    /**
     * Get a configuration value by dot-notation key (e.g., 'app.name', 'domains.main').
     *
     * @param string $key Dot-notation key string
     * @param mixed $default Default fallback if key not found
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if ($key === '') {
            return $this->config;
        }

        $segments = explode('.', $key);
        $current = $this->config;

        foreach ($segments as $segment) {
            if (is_array($current) && array_key_exists($segment, $current)) {
                $current = $current[$segment];
            } else {
                return $default;
            }
        }

        return $current;
    }

    /**
     * Check if a configuration key exists.
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Return all aggregated configuration data.
     */
    public function all(): array
    {
        return $this->config;
    }
}

/**
 * Global helper function to quickly access system configuration settings.
 */
if (!function_exists('sys_config')) {
    function sys_config(?string $key = null, mixed $default = null): mixed
    {
        $instance = SysConfig::getInstance();
        if ($key === null) {
            return $instance;
        }
        return $instance->get($key, $default);
    }
}
