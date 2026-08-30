<?php
/**
 * sys_services.php
 *
 * Platform Central Services container.
 * Accessible to MAIN OS and MAIN-CONNECTED applications.
 */

declare(strict_types=1);

require_once __DIR__ . '/sys_config.php';
require_once __DIR__ . '/sys_session.php';
require_once __DIR__ . '/sys_auth.php';

class SysServices
{
    private static array $services = [];

    /**
     * Register a shared service.
     */
    public static function register(string $name, callable $resolver): void
    {
        self::$services[$name] = $resolver;
    }

    /**
     * Resolve a shared service.
     */
    public static function get(string $name): mixed
    {
        if (isset(self::$services[$name])) {
            return call_user_func(self::$services[$name]);
        }
        return null;
    }

    /**
     * Get system metrics/status summary.
     */
    public static function getSystemStatus(): array
    {
        return [
            'status' => 'operational',
            'app_name' => sys_config('app.name', 'NodeX Platform'),
            'version' => sys_config('app.version', '1.0.0'),
            'main_domain' => sys_config('domains.main', ''),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}
