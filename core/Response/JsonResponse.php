<?php
/**
 * Consistent JSON response helper for modular API controllers.
 *
 * Existing endpoints may keep their current response bodies during migration.
 * New modules should use this helper so success and error payloads have the
 * same predictable shape for jQuery clients.
 */
declare(strict_types=1);

final class JsonResponse
{
    public static function success(string $message = '', mixed $data = null, int $status = 200): never
    {
        self::send(['success' => true, 'message' => $message, 'data' => $data], $status);
    }

    public static function error(string $message, mixed $data = null, int $status = 400): never
    {
        self::send(['success' => false, 'message' => $message, 'data' => $data], $status);
    }

    private static function send(array $payload, int $status): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
