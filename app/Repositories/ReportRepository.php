<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ReportRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function daily(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $clauses = [];
        $parameters = [];
        if ($dateFrom !== null) {
            $clauses[] = 'occurred_at >= :date_from';
            $parameters['date_from'] = $dateFrom . ' 00:00:00';
        }
        if ($dateTo !== null) {
            $clauses[] = 'occurred_at <= :date_to';
            $parameters['date_to'] = $dateTo . ' 23:59:59';
        }
        $where = $clauses === [] ? '' : 'WHERE ' . implode(' AND ', $clauses);

        $statement = $this->pdo->prepare(
            "SELECT DATE(occurred_at) AS settlement_date,
                    currency,
                    COUNT(*) AS transaction_count,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved_count,
                    SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) AS declined_count,
                    SUM(CASE WHEN status = 'reversed' THEN 1 ELSE 0 END) AS reversed_count,
                    SUM(amount_cents) AS gross_amount_cents,
                    SUM(CASE WHEN status = 'approved' THEN amount_cents ELSE 0 END) AS approved_amount_cents,
                    SUM(CASE
                        WHEN status = 'approved' AND transaction_type IN ('debit', 'adjustment') THEN amount_cents
                        WHEN status = 'approved' AND transaction_type IN ('credit', 'reversal') THEN -amount_cents
                        ELSE 0
                    END) AS net_settlement_cents
             FROM transactions
             {$where}
             GROUP BY DATE(occurred_at), currency
             ORDER BY settlement_date DESC, currency ASC"
        );
        $statement->execute($parameters);
        return $statement->fetchAll();
    }
}
