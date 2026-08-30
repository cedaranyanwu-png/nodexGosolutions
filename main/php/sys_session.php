<?php
/**
 * sys_session.php
 *
 * Centralized Session Manager for the Main OS and Main-Connected Applications.
 */

declare(strict_types=1);

require_once __DIR__ . '/sys_config.php';

class SysSession
{
    private static bool $started = false;

    /**
     * Start or resume session safely.
     */
    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $sessionLifetime = (int) sys_config('services.auth.session_lifetime', 86400);

        if (!headers_sent()) {
            ini_set('session.gc_maxlifetime', (string) $sessionLifetime);
            session_set_cookie_params([
                'lifetime' => $sessionLifetime,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            session_start();
            self::$started = true;
        }
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
        self::$started = false;
    }
}
