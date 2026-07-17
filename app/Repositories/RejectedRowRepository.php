<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class RejectedRowRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function insert(int $importId, int $rowNumber, array $row, array $errors): void
    {
        $rowNumberColumn = $this->rowNumberColumn();
        $statement = $this->pdo->prepare(
            "INSERT INTO rejected_rows (import_id, {$rowNumberColumn}, original_row, validation_errors, rejection_reason)
             VALUES (:import_id, :row_number, :original_row, :errors, :reason)"
        );
        $statement->execute([
            'import_id' => $importId,
            'row_number' => $rowNumber,
            'original_row' => json_encode($row, JSON_INVALID_UTF8_SUBSTITUTE),
            'errors' => json_encode($errors, JSON_INVALID_UTF8_SUBSTITUTE),
            'reason' => mb_substr(implode(' ', array_values($errors)), 0, 255),
        ]);
    }

    public function paginate(int $page, int $perPage): array
    {
        $total = (int) $this->pdo->query('SELECT COUNT(*) FROM rejected_rows')->fetchColumn();
        $statement = $this->pdo->prepare(
            "SELECT rejected_rows.*, imports.filename FROM rejected_rows
             JOIN imports ON imports.id = rejected_rows.import_id
             ORDER BY rejected_rows.id DESC LIMIT :limit OFFSET :offset"
        );
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $statement->execute();
        $rows = $statement->fetchAll();
        if ($this->rowNumberColumn() === 'csv_row_number') {
            foreach ($rows as &$row) {
                $row['row_number'] = $row['csv_row_number'];
                unset($row['csv_row_number']);
            }
            unset($row);
        }
        return ['rows' => $rows, 'total' => $total, 'pages' => max(1, (int) ceil($total / $perPage))];
    }

    private function rowNumberColumn(): string
    {
        return $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql'
            ? 'csv_row_number'
            : 'row_number';
    }
}
