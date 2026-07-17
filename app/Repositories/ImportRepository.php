<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ImportRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByChecksum(string $checksum): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM imports WHERE checksum = :checksum');
        $statement->execute(['checksum' => $checksum]);
        return $statement->fetch() ?: null;
    }

    public function create(string $filename, string $checksum): int
    {
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
            $statement = $this->pdo->prepare(
                'INSERT INTO imports (filename, checksum) VALUES (:filename, :checksum) RETURNING id'
            );
            $statement->execute(['filename' => $filename, 'checksum' => $checksum]);
            return (int) $statement->fetchColumn();
        }

        $statement = $this->pdo->prepare('INSERT INTO imports (filename, checksum) VALUES (:filename, :checksum)');
        $statement->execute(['filename' => $filename, 'checksum' => $checksum]);
        return (int) $this->pdo->lastInsertId();
    }

    public function complete(int $id, array $counts, int $duration): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE imports SET status = :status, imported_count = :imported, rejected_count = :rejected,
             duplicate_count = :duplicates, skipped_count = :skipped, duration_ms = :duration,
             completed_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $statement->execute([
            'status' => 'completed', 'imported' => $counts['imported'], 'rejected' => $counts['rejected'],
            'duplicates' => $counts['duplicates'], 'skipped' => $counts['skipped'],
            'duration' => $duration, 'id' => $id,
        ]);
    }

    public function fail(int $id): void
    {
        $this->pdo->prepare("UPDATE imports SET status = 'failed', completed_at = CURRENT_TIMESTAMP WHERE id = :id")
            ->execute(['id' => $id]);
    }

    public function recent(int $limit): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM imports ORDER BY id DESC LIMIT :limit');
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }
}
