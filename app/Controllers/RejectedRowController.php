<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\View;
use App\Repositories\RejectedRowRepository;

final class RejectedRowController
{
    public function __construct(private readonly RejectedRowRepository $rejections)
    {
    }

    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        View::render('rejected', [
            'title' => 'Rejected rows',
            'result' => $this->rejections->paginate($page, 25),
            'page' => $page,
        ]);
    }
}
