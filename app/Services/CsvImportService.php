<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ImportRepository;
use App\Repositories\RejectedRowRepository;
use App\Repositories\TransactionRepository;
use App\Support\Logger;
use App\Validation\TransactionValidator;
use PDO;
use RuntimeException;
use SplFileObject;
use Throwable;

final class CsvImportService
{
    private const MYSQL_BATCH_SIZE = 500;

    public function __construct(
        private readonly PDO $pdo,
        private readonly ImportRepository $imports,
        private readonly TransactionRepository $transactions,
        private readonly RejectedRowRepository $rejections,
        private readonly TransactionValidator $validator,
        private readonly Logger $logger,
    ) {
    }

    public function import(string $path, string $filename): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('The uploaded file could not be read.');
        }
        $checksum = hash_file('sha256', $path);
        if ($checksum === false) {
            throw new RuntimeException('Unable to calculate the file checksum.');
        }
        if ($existing = $this->imports->findByChecksum($checksum)) {
            return ['duplicate_file' => true, 'import' => $existing];
        }

        $started = hrtime(true);
        $importId = $this->imports->create(mb_substr(basename($filename), 0, 255), $checksum);
        $counts = ['imported' => 0, 'rejected' => 0, 'duplicates' => 0, 'skipped' => 0];
        $this->logger->info('Import started', ['import_id' => $importId, 'filename' => $filename]);

        try {
            $file = new SplFileObject($path, 'rb');
            $file->setFlags(SplFileObject::READ_CSV | SplFileObject::DROP_NEW_LINE);
            $file->setCsvControl(',', '"', '\\');
            $header = $file->fgetcsv();
            $normalizedHeader = array_map(static fn ($v): string => strtolower(trim((string) $v)), $header ?: []);
            if ($normalizedHeader !== TransactionValidator::HEADERS) {
                throw new RuntimeException('CSV headers do not match the required transaction schema.');
            }

            $this->pdo->beginTransaction();
            $rowNumber = 1;
            $batch = [];
            $useBatching = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql';
            while (!$file->eof()) {
                $rowNumber++;
                $row = $file->fgetcsv();
                if ($row === false || $row === [null] || $row === []) {
                    $counts['skipped']++;
                    continue;
                }
                [$transaction, $errors] = $this->validator->validate($row);
                if ($errors !== []) {
                    $this->rejections->insert($importId, $rowNumber, $row, $errors);
                    $counts['rejected']++;
                    $this->logger->info('Row rejected', ['import_id' => $importId, 'row' => $rowNumber, 'errors' => $errors]);
                    continue;
                }
                if ($useBatching) {
                    $batch[] = $transaction;
                    if (count($batch) >= self::MYSQL_BATCH_SIZE) {
                        $this->flushBatch($importId, $batch, $counts);
                    }
                    continue;
                }
                if ($this->transactions->insert($importId, $transaction)) {
                    $counts['imported']++;
                } else {
                    $counts['duplicates']++;
                }
            }
            $this->flushBatch($importId, $batch, $counts);
            $duration = (int) round((hrtime(true) - $started) / 1_000_000);
            $this->imports->complete($importId, $counts, $duration);
            $this->pdo->commit();
            $this->logger->info('Import completed', ['import_id' => $importId, 'counts' => $counts, 'duration_ms' => $duration]);
            return ['duplicate_file' => false, 'import_id' => $importId, 'counts' => $counts, 'duration_ms' => $duration];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $this->imports->fail($importId);
            $this->logger->error('Import failed', ['import_id' => $importId, 'exception' => $exception->getMessage()]);
            throw $exception;
        }
    }

    private function flushBatch(int $importId, array &$batch, array &$counts): void
    {
        if ($batch === []) {
            return;
        }

        $attempted = count($batch);
        $inserted = $this->transactions->insertBatch($importId, $batch);
        $counts['imported'] += $inserted;
        $counts['duplicates'] += $attempted - $inserted;
        $batch = [];
    }
}
