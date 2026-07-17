<?php

declare(strict_types=1);

use App\Repositories\ImportRepository;
use App\Repositories\RejectedRowRepository;
use App\Repositories\TransactionRepository;
use App\Services\CsvImportService;
use App\Validation\TransactionValidator;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$container = require dirname(__DIR__) . '/app/bootstrap.php';
$file = $argv[1] ?? '';
if ($file === '' || !is_file($file)) {
    fwrite(STDERR, "Usage: php bin/import.php /path/to/transactions.csv\n");
    exit(2);
}

$service = new CsvImportService(
    $container['pdo'],
    new ImportRepository($container['pdo']),
    new TransactionRepository($container['pdo']),
    new RejectedRowRepository($container['pdo']),
    new TransactionValidator(),
    $container['logger'],
);

try {
    $result = $service->import($file, basename($file));
    fwrite(STDOUT, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
} catch (Throwable $exception) {
    fwrite(STDERR, "Import failed: {$exception->getMessage()}\n");
    exit(1);
}
