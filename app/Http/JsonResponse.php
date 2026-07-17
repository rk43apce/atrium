<?php

declare(strict_types=1);

namespace App\Http;

final class JsonResponse
{
    public static function success(
        mixed $data,
        array $meta = [],
        int $status = 200,
    ): never {
        self::send([
            'data' => $data,
            'meta' => $meta === [] ? null : $meta,
            'error' => null,
        ], $status);
    }

    public static function error(
        string $code,
        string $message,
        int $status,
        array $details = [],
    ): never {
        self::send([
            'data' => null,
            'meta' => null,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details === [] ? null : $details,
            ],
        ], $status);
    }

    private static function send(array $payload, int $status): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode(
            $payload,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE,
        );
        exit;
    }
}
