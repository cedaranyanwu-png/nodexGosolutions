<?php

declare(strict_types=1);

namespace Main\Security;

use RuntimeException;

class Security
{
    public static function sanitizeString(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    public static function validateSafePath(string $baseDir, string $requestedPath): string
    {
        $realBase = realpath($baseDir);
        if ($realBase === false) {
            throw new RuntimeException("Invalid base directory: {$baseDir}");
        }

        $targetPath = $baseDir . '/' . ltrim($requestedPath, '/');
        $realTarget = realpath($targetPath);

        if ($realTarget === false || !str_starts_with($realTarget, $realBase)) {
            throw new RuntimeException("Access Denied: Path traversal detected outside base directory.");
        }

        return $realTarget;
    }

    public static function enforceMethod(string ...$allowedMethods): void
    {
        $currentMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $allowed = array_map('strtoupper', $allowedMethods);

        if (!in_array($currentMethod, $allowed, true)) {
            http_response_code(405);
            header('Allow: ' . implode(', ', $allowed));
            echo json_encode(['error' => 'Method Not Allowed']);
            exit;
        }
    }
}
