<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Logger;
use PDO;
use Throwable;

final class DataResetService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly Logger $logger,
    ) {
    }

    public function reset(): array
    {
        $this->pdo->beginTransaction();

        try {
            $counts = [
                'rejected_rows' => $this->delete('rejected_rows'),
                'transactions' => $this->delete('transactions'),
                'imports' => $this->delete('imports'),
            ];
            $this->pdo->commit();
            $this->logger->info('Application data reset', ['deleted' => $counts]);
            return $counts;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $this->logger->error('Application data reset failed', [
                'exception' => $exception->getMessage(),
            ]);
            throw $exception;
        }
    }

    private function delete(string $table): int
    {
        // Table names are internal constants and never originate from request data.
        return $this->pdo->exec("DELETE FROM {$table}");
    }
}
