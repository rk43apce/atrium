<?php

declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money(int $cents, string $currency = 'USD'): string
{
    return $currency . ' ' . number_format($cents / 100, 2);
}

function compactIdentifier(string $value, int $maximumLength = 32): string
{
    if (mb_strlen($value) <= $maximumLength) {
        return $value;
    }

    $visibleLength = $maximumLength - 1;
    $prefixLength = (int) ceil($visibleLength * 0.65);
    $suffixLength = $visibleLength - $prefixLength;

    return mb_substr($value, 0, $prefixLength)
        . '…'
        . mb_substr($value, -$suffixLength);
}

function csrfToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(mixed $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function url(string $path = '', array $query = []): string
{
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
    return $base . '/index.php' . ($path !== '' ? '?route=' . rawurlencode($path) . ($query ? '&' . http_build_query($query) : '') : '');
}
