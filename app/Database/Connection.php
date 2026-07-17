<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use RuntimeException;

final class Connection
{
    public static function create(array $config): PDO
    {
        $pdo = new PDO($config['dsn'], $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        if (str_starts_with($config['dsn'], 'sqlite:')) {
            $pdo->exec('PRAGMA foreign_keys = ON');
            $pdo->exec('PRAGMA busy_timeout = 5000');
        }
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $pdo->exec("SET time_zone = '+00:00'");
        }
        return $pdo;
    }

    public static function migrate(PDO $pdo, string $schema): void
    {
        $sql = file_get_contents($schema);
        if ($sql === false) {
            throw new RuntimeException('Database schema could not be read.');
        }
        $pdo->exec($sql);
    }
}
