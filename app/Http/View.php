<?php

declare(strict_types=1);

namespace App\Http;

use RuntimeException;

final class View
{
    public static function render(string $template, array $data = [], int $status = 200): void
    {
        $file = BASE_PATH . '/views/' . $template . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("View {$template} not found.");
        }
        http_response_code($status);
        extract($data, EXTR_SKIP);
        require BASE_PATH . '/views/layouts/header.php';
        require $file;
        require BASE_PATH . '/views/layouts/footer.php';
    }
}
