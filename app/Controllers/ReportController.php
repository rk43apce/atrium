<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\View;
use App\Repositories\ReportRepository;
use DateTimeImmutable;

final class ReportController
{
    public function __construct(private readonly ReportRepository $reports)
    {
    }

    public function daily(): void
    {
        [$dateFrom, $dateTo, $error] = $this->dates($_GET);
        View::render('reports/daily', [
            'title' => 'Daily settlement',
            'rows' => $error === null ? $this->reports->daily($dateFrom, $dateTo) : [],
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'filterError' => $error,
        ], $error === null ? 200 : 422);
    }

    private function dates(array $input): array
    {
        $values = [];
        foreach (['date_from', 'date_to'] as $field) {
            $value = trim((string) ($input[$field] ?? ''));
            if ($value === '') {
                $values[] = null;
                continue;
            }
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
            if (!$date || $date->format('Y-m-d') !== $value) {
                return [null, null, 'Dates must use YYYY-MM-DD format.'];
            }
            $values[] = $value;
        }
        if ($values[0] !== null && $values[1] !== null && $values[0] > $values[1]) {
            return [$values[0], $values[1], 'The start date must not be after the end date.'];
        }
        return [$values[0], $values[1], null];
    }
}
