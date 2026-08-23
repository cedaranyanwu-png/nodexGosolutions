<?php
/**
 * Shared authorization gateway for modular controllers.
 *
 * This adapter deliberately delegates to the existing security functions. It
 * centralizes future module calls without inventing a second permission model
 * or weakening the legacy role and tenant ownership checks.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../php/db.php';

final class AuthorizationGateway
{
    /** Ensure the request has the same secure session required by legacy pages. */
    public function requireSession(): void
    {
        secureSession();
    }

    /** Return the current role from the authenticated session. */
    public function role(): string
    {
        return strtolower((string)($_SESSION['role'] ?? 'tenant'));
    }

    /** Reuse the platform's centralized admin permission decision. */
    public function canAccessAdminPage(string $requestUri): bool
    {
        return hasAdminPagePermission($this->role(), $requestUri);
    }

    /** Keep ownership decisions inside the existing tenant-aware service layer. */
    public function isOwner(int $userId, string $subdomain): bool
    {
        $db = new Database();
        $website = $db->selectOne('websites', ['subdomain' => $subdomain]);
        return $website !== null && (int)($website['user_id'] ?? 0) === $userId;
    }
}
