<?php

declare(strict_types=1);

namespace App\Support;

use JsonException;

final class Logger
{
    public function __construct(private readonly string $path)
    {
    }

    public function info(string $message, array $context = []): void
    {
        $this->write('INFO', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('ERROR', $message, $context);
    }

    private function write(string $level, string $message, array $context): void
    {
        try {
            $record = json_encode([
                'timestamp' => gmdate(DATE_ATOM),
                'level' => $level,
                'message' => $message,
                'context' => $context,
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . PHP_EOL;
            file_put_contents($this->path, $record, FILE_APPEND | LOCK_EX);
        } catch (JsonException) {
            // Logging must never take down the request.
        }
    }
}
