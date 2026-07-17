<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\View;
use App\Repositories\TransactionRepository;

final class DashboardController
{
    public function __construct(private readonly TransactionRepository $transactions)
    {
    }

    public function index(): void
    {
        View::render('dashboard', ['title' => 'Dashboard', 'metrics' => $this->transactions->dashboard()]);
    }
}
