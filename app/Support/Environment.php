<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class Environment
{
    public static function load(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            throw new RuntimeException('Unable to read the environment file.');
        }

        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_starts_with($line, 'export ')) {
                $line = trim(substr($line, 7));
            }

            if (!str_contains($line, '=')) {
                throw new RuntimeException(sprintf(
                    'Invalid environment entry on line %d.',
                    $lineNumber + 1,
                ));
            }

            [$name, $value] = array_map('trim', explode('=', $line, 2));
            if (!preg_match('/^[A-Z_][A-Z0-9_]*$/i', $name)) {
                throw new RuntimeException(sprintf(
                    'Invalid environment variable name on line %d.',
                    $lineNumber + 1,
                ));
            }

            // Values supplied by the process or deployment platform take precedence.
            if (getenv($name) !== false) {
                continue;
            }

            $value = self::normalizeValue($value);
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }

    private static function normalizeValue(string $value): string
    {
        $length = strlen($value);
        if ($length >= 2) {
            $first = $value[0];
            $last = $value[$length - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
                return $first === '"' ? stripcslashes($value) : $value;
            }
        }

        // Permit inline comments only when separated from the value by whitespace.
        return preg_replace('/\s+#.*$/', '', $value) ?? $value;
    }
}
