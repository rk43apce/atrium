<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\View;
use App\Repositories\TransactionRepository;

final class TransactionController
{
    public function __construct(private readonly TransactionRepository $transactions)
    {
    }

    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $filters = array_intersect_key($_GET, array_flip([
            'search', 'date_from', 'date_to', 'merchant_name', 'currency', 'status', 'transaction_type',
        ]));
        $sort = (string) ($_GET['sort'] ?? 'occurred_at');
        $direction = strtolower((string) ($_GET['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        View::render('transactions', [
            'title' => 'Transactions',
            'result' => $this->transactions->search($filters, $page, 25, $sort, $direction),
            'filters' => $filters,
            'options' => $this->transactions->filterOptions(),
            'page' => $page,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function show(): void
    {
        $transaction = $this->transactions->find((int) ($_GET['id'] ?? 0));
        View::render('transaction-detail', [
            'title' => 'Transaction details',
            'transaction' => $transaction,
        ], $transaction ? 200 : 404);
    }
}
