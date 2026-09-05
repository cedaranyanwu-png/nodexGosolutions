<?php

declare(strict_types=1);

namespace Main\Auth;

use Main\Database\JsonDatabase;
use Main\Session\Session;

class Auth
{
    private JsonDatabase $db;

    public function __construct(?JsonDatabase $db = null)
    {
        $this->db = $db ?? new JsonDatabase();
    }

    public function login(string $usernameOrEmail, string $password): bool
    {
        $user = $this->db->table('users')
            ->where('username', $usernameOrEmail)
            ->first();

        if (!$user) {
            $user = $this->db->table('users')
                ->where('email', $usernameOrEmail)
                ->first();
        }

        if (!$user || ($user['status'] ?? '') !== 'active') {
            return false;
        }

        if (password_verify($password, $user['password'])) {
            Session::regenerate();
            Session::set('user_id', $user['id']);
            Session::set('user_role', $user['role']);
            Session::set('username', $user['username']);
            return true;
        }

        return false;
    }

    public static function user(): ?array
    {
        $userId = Session::get('user_id');
        if (!$userId) {
            return null;
        }

        $db = new JsonDatabase();
        return $db->table('users')->find($userId);
    }

    public static function check(): bool
    {
        return Session::has('user_id');
    }

    public static function hasRole(string ...$roles): bool
    {
        $userRole = Session::get('user_role');
        return $userRole && in_array($userRole, $roles, true);
    }

    public static function logout(): void
    {
        Session::destroy();
    }
}
