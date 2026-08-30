<?php
/**
 * sys_auth.php
 *
 * Central Authentication Manager for Main OS and Main-Connected Applications.
 */

declare(strict_types=1);

require_once __DIR__ . '/sys_session.php';
require_once __DIR__ . '/sys_config.php';

class SysAuth
{
    /**
     * Check if user is currently authenticated in the system session.
     */
    public static function check(): bool
    {
        return SysSession::has('user_id');
    }

    /**
     * Get the authenticated user details.
     */
    public static function user(): ?array
    {
        return SysSession::get('user');
    }

    /**
     * Authenticate a user session.
     */
    public static function login(array $user): void
    {
        SysSession::start();
        SysSession::set('user_id', $user['id'] ?? 1);
        SysSession::set('user', $user);
    }

    /**
     * Terminate the user session.
     */
    public static function logout(): void
    {
        SysSession::remove('user_id');
        SysSession::remove('user');
        SysSession::destroy();
    }
}
