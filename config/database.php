<?php

declare(strict_types=1);

$dsn = getenv('DB_DSN');
$username = getenv('DB_USERNAME');
$password = getenv('DB_PASSWORD');

if (!is_string($dsn) || !str_starts_with($dsn, 'mysql:')) {
    throw new RuntimeException('DB_DSN must contain a valid MySQL PDO DSN.');
}
if (!is_string($username) || $username === '') {
    throw new RuntimeException('DB_USERNAME is required.');
}
if (!is_string($password) || $password === '') {
    throw new RuntimeException('DB_PASSWORD is required.');
}

return ['dsn' => $dsn, 'username' => $username, 'password' => $password];
