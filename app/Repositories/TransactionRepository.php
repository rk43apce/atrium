<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTO\TransactionData;
use PDO;
use PDOException;

final class TransactionRepository
{
    private const INSERT_COLUMNS = [
        'import_id',
        'transaction_id',
        'occurred_at',
        'terminal_id',
        'card_number',
        'account',
        'amount_cents',
        'transaction_type',
        'status',
        'merchant_id',
        'merchant_name',
        'currency',
        'external_reference',
    ];

    private ?\PDOStatement $insertStatement = null;

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function insert(int $importId, TransactionData $data): bool
    {
        $this->insertStatement ??= $this->pdo->prepare(
            'INSERT INTO transactions (' . implode(', ', self::INSERT_COLUMNS) . ')
             VALUES (:import_id, :transaction_id, :occurred_at, :terminal_id, :card_number, :account, :amount_cents,
             :transaction_type, :status, :merchant_id, :merchant_name, :currency, :external_reference)'
        );
        try {
            return $this->insertStatement->execute($this->parameters($importId, $data));
        } catch (PDOException $exception) {
            if (in_array($exception->getCode(), ['23000', '23505', '19'], true)) {
                return false;
            }
            throw $exception;
        }
    }

    /**
     * Inserts a bounded batch and returns the number of rows actually created.
     * MySQL reports no-op unique-key updates through rowCount() while other
     * database errors remain exceptions instead of being silently coerced.
     *
     * @param list<TransactionData> $transactions
     */
    public function insertBatch(int $importId, array $transactions): int
    {
        if ($transactions === []) {
            return 0;
        }

        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') {
            $inserted = 0;
            foreach ($transactions as $transaction) {
                $inserted += $this->insert($importId, $transaction) ? 1 : 0;
            }
            return $inserted;
        }

        $values = [];
        $parameters = [];
        foreach ($transactions as $index => $transaction) {
            $rowPlaceholders = [];
            foreach ($this->parameters($importId, $transaction) as $column => $value) {
                $placeholder = ':' . $column . '_' . $index;
                $rowPlaceholders[] = $placeholder;
                $parameters[$placeholder] = $value;
            }
            $values[] = '(' . implode(', ', $rowPlaceholders) . ')';
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO transactions (' . implode(', ', self::INSERT_COLUMNS) . ')
             VALUES ' . implode(', ', $values) . '
             ON DUPLICATE KEY UPDATE transaction_id = VALUES(transaction_id)'
        );
        $statement->execute($parameters);
        return $statement->rowCount();
    }

    public function dashboard(): array
    {
        $totals = $this->pdo->query(
            'SELECT COUNT(*) total_transactions, COALESCE(SUM(amount_cents), 0) total_amount FROM transactions'
        )->fetch();
        $latest = $this->pdo->query('SELECT * FROM imports ORDER BY id DESC LIMIT 1')->fetch() ?: [];
        return array_merge($totals, $latest);
    }

    public function search(array $filters, int $page, int $perPage, string $sort, string $direction): array
    {
        [$where, $params] = $this->conditions($filters);
        $allowedSort = ['occurred_at', 'amount_cents', 'merchant_name', 'status', 'transaction_id'];
        $sort = in_array($sort, $allowedSort, true) ? $sort : 'occurred_at';
        $direction = $direction === 'asc' ? 'ASC' : 'DESC';
        $count = $this->pdo->prepare("SELECT COUNT(*) FROM transactions {$where}");
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $query = $this->pdo->prepare(
            "SELECT * FROM transactions {$where} ORDER BY {$sort} {$direction}, id DESC LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $query->bindValue($key, $value);
        }
        $query->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $query->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $query->execute();
        return ['rows' => $query->fetchAll(), 'total' => $total, 'pages' => max(1, (int) ceil($total / $perPage))];
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM transactions WHERE id = :id');
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }

    public function filterOptions(): array
    {
        $options = [];
        foreach (['merchant_name', 'currency', 'status', 'transaction_type'] as $column) {
            $options[$column] = $this->pdo->query(
                "SELECT DISTINCT {$column} FROM transactions ORDER BY {$column}"
            )->fetchAll(PDO::FETCH_COLUMN);
        }
        return $options;
    }

    private function conditions(array $filters): array
    {
        $clauses = [];
        $params = [];
        foreach (['merchant_name', 'currency', 'status', 'transaction_type'] as $field) {
            if (($filters[$field] ?? '') !== '') {
                $clauses[] = "{$field} = :{$field}";
                $params[":{$field}"] = $filters[$field];
            }
        }
        if (($filters['search'] ?? '') !== '') {
            $clauses[] = '(transaction_id LIKE :search OR external_reference LIKE :search OR merchant_name LIKE :search)';
            $params[':search'] = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $filters['search']) . '%';
        }
        if (($filters['date_from'] ?? '') !== '') {
            $clauses[] = 'occurred_at >= :date_from';
            $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (($filters['date_to'] ?? '') !== '') {
            $clauses[] = 'occurred_at <= :date_to';
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }
        return [$clauses ? 'WHERE ' . implode(' AND ', $clauses) : '', $params];
    }

    private function parameters(int $importId, TransactionData $data): array
    {
        return [
            'import_id' => $importId,
            'transaction_id' => $data->transactionId,
            'occurred_at' => $data->occurredAt,
            'terminal_id' => $data->terminalId,
            'card_number' => $data->cardNumber,
            'account' => $data->account,
            'amount_cents' => $data->amountCents,
            'transaction_type' => $data->transactionType,
            'status' => $data->status,
            'merchant_id' => $data->merchantId,
            'merchant_name' => $data->merchantName,
            'currency' => $data->currency,
            'external_reference' => $data->externalReference,
        ];
    }
}
