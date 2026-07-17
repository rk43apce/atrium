<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Response;
use App\Services\DataResetService;

final class DataResetController
{
    public function __construct(private readonly DataResetService $service)
    {
    }

    public function store(): never
    {
        if (!\verifyCsrfToken($_POST['csrf_token'] ?? null)) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Your session expired. Refresh the page and try again.',
            ];
            Response::redirect(url());
        }

        $counts = $this->service->reset();
        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => sprintf(
                'Data reset complete: %d transactions, %d rejected rows, and %d imports removed.',
                $counts['transactions'],
                $counts['rejected_rows'],
                $counts['imports'],
            ),
        ];
        Response::redirect(url());
    }
}
