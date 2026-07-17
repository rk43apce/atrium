<?php

declare(strict_types=1);

use App\Controllers\DashboardController;
use App\Controllers\ApiController;
use App\Controllers\ImportController;
use App\Controllers\RejectedRowController;
use App\Controllers\ReportController;
use App\Controllers\TransactionController;
use App\Http\View;
use App\Http\JsonResponse;
use App\Repositories\ImportRepository;
use App\Repositories\RejectedRowRepository;
use App\Repositories\ReportRepository;
use App\Repositories\TransactionRepository;
use App\Services\CsvImportService;
use App\Validation\TransactionValidator;

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);

$container = require dirname(__DIR__) . '/app/bootstrap.php';
require BASE_PATH . '/app/Support/helpers.php';

$transactions = new TransactionRepository($container['pdo']);
$rejections = new RejectedRowRepository($container['pdo']);
$imports = new ImportRepository($container['pdo']);
$reports = new ReportRepository($container['pdo']);
$importService = new CsvImportService(
    $container['pdo'],
    $imports,
    $transactions,
    $rejections,
    new TransactionValidator(),
    $container['logger'],
);
$api = new ApiController($transactions, $rejections, $imports, $reports, $importService);

$route = trim((string) ($_GET['route'] ?? ''), '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$routes = [
    'GET ' => [new DashboardController($transactions), 'index'],
    'GET transactions' => [new TransactionController($transactions), 'index'],
    'GET transaction' => [new TransactionController($transactions), 'show'],
    'GET rejected' => [new RejectedRowController($rejections), 'index'],
    'GET reports/daily' => [new ReportController($reports), 'daily'],
    'GET import' => [new ImportController($importService), 'form'],
    'POST import' => [new ImportController($importService), 'store'],
    'GET api/v1/transactions' => [$api, 'transactions'],
    'GET api/v1/transaction' => [$api, 'transaction'],
    'GET api/v1/rejected-rows' => [$api, 'rejectedRows'],
    'GET api/v1/imports' => [$api, 'imports'],
    'GET api/v1/reports/daily' => [$api, 'dailyReport'],
    'POST api/v1/imports' => [$api, 'import'],
];

$handler = $routes["{$method} {$route}"] ?? null;
if (!$handler) {
    if (str_starts_with($route, 'api/')) {
        JsonResponse::error('route_not_found', 'The API endpoint does not exist.', 404);
    }
    View::render('error', ['title' => 'Page not found', 'message' => 'The page you requested does not exist.'], 404);
    exit;
}

try {
    $handler();
} catch (Throwable $exception) {
    $container['logger']->error('Unhandled request exception', ['message' => $exception->getMessage()]);
    if (str_starts_with($route, 'api/')) {
        JsonResponse::error('internal_error', 'The request could not be completed.', 500);
    }
    View::render('error', ['title' => 'Something went wrong', 'message' => 'The request could not be completed.'], 500);
}
