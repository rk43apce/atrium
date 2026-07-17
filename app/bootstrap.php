<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Support\Logger;

define('BASE_PATH', dirname(__DIR__));

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

\App\Support\Environment::load(BASE_PATH . '/.env');

foreach ([BASE_PATH . '/storage', BASE_PATH . '/storage/logs'] as $directory) {
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException("Unable to create {$directory}");
    }
}

$config = require BASE_PATH . '/config/database.php';
$pdo = Connection::create($config);
Connection::migrate($pdo, BASE_PATH . '/database/schema.mysql.sql');
$logger = new Logger(BASE_PATH . '/storage/logs/application.log');

return ['pdo' => $pdo, 'logger' => $logger];
