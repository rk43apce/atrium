<?php

declare(strict_types=1);

return [
    'dsn' => getenv('DB_DSN') ?: 'sqlite:' . dirname(__DIR__) . '/storage/database.sqlite',
    'username' => getenv('DB_USERNAME') ?: null,
    'password' => getenv('DB_PASSWORD') ?: null,
];
