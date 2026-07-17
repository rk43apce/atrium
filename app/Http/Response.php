<?php

declare(strict_types=1);

namespace App\Http;

final class Response
{
    public static function redirect(string $location): never
    {
        header('Location: ' . $location, true, 303);
        exit;
    }
}
