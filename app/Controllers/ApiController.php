<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\JsonResponse;
use App\Repositories\ImportRepository;
use App\Repositories\RejectedRowRepository;
use App\Repositories\ReportRepository;
use App\Repositories\TransactionRepository;
use App\Services\CsvImportService;
use DateTimeImmutable;
use RuntimeException;

final class ApiController
{
    public function __construct(
        private readonly TransactionRepository $transactions,
        private readonly RejectedRowRepository $rejections,
        private readonly ImportRepository $imports,
        private readonly ReportRepository $reports,
        private readonly CsvImportService $importService,
    ) {
    }

    public function transactions(): never
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 25)));
        $filters = array_intersect_key($_GET, array_flip([
            'search', 'date_from', 'date_to', 'merchant_name', 'currency', 'status', 'transaction_type',
        ]));
        $result = $this->transactions->search(
            $filters,
            $page,
            $perPage,
            (string) ($_GET['sort'] ?? 'occurred_at'),
            strtolower((string) ($_GET['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc',
        );
        JsonResponse::success($result['rows'], [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $result['total'],
            'total_pages' => $result['pages'],
        ]);
    }

    public function transaction(): never
    {
        $transaction = $this->transactions->find((int) ($_GET['id'] ?? 0));
        if ($transaction === null) {
            JsonResponse::error('transaction_not_found', 'The transaction does not exist.', 404);
        }
        JsonResponse::success($transaction);
    }

    public function rejectedRows(): never
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 25)));
        $result = $this->rejections->paginate($page, $perPage);
        $rows = array_map(static function (array $row): array {
            $row['original_row'] = json_decode($row['original_row'], true);
            $row['validation_errors'] = json_decode($row['validation_errors'], true);
            return $row;
        }, $result['rows']);
        JsonResponse::success($rows, [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $result['total'],
            'total_pages' => $result['pages'],
        ]);
    }

    public function imports(): never
    {
        $limit = min(100, max(1, (int) ($_GET['limit'] ?? 25)));
        JsonResponse::success($this->imports->recent($limit), ['limit' => $limit]);
    }

    public function dailyReport(): never
    {
        $dateFrom = $this->date('date_from');
        $dateTo = $this->date('date_to');
        if ($dateFrom !== null && $dateTo !== null && $dateFrom > $dateTo) {
            JsonResponse::error(
                'invalid_date_range',
                'The start date must not be after the end date.',
                422,
            );
        }
        JsonResponse::success(
            $this->reports->daily($dateFrom, $dateTo),
            ['date_from' => $dateFrom, 'date_to' => $dateTo],
        );
    }

    public function import(): never
    {
        $file = $_FILES['csv'] ?? null;
        if (!$file || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            JsonResponse::error(
                'invalid_upload',
                'Attach a readable CSV file using the multipart field "csv".',
                422,
                ['upload_error' => (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE)],
            );
        }
        if (($file['size'] ?? 0) > 50 * 1024 * 1024 || strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'csv') {
            JsonResponse::error('invalid_file', 'Only CSV files up to 50 MB are accepted.', 422);
        }
        try {
            $result = $this->importService->import($file['tmp_name'], $file['name']);
            JsonResponse::success($result, [], $result['duplicate_file'] ? 200 : 201);
        } catch (RuntimeException $exception) {
            JsonResponse::error('import_failed', $exception->getMessage(), 422);
        }
    }

    private function date(string $field): ?string
    {
        $value = trim((string) ($_GET[$field] ?? ''));
        if ($value === '') {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            JsonResponse::error(
                'invalid_date',
                "{$field} must use YYYY-MM-DD format.",
                422,
                ['field' => $field],
            );
        }
        return $value;
    }
}
