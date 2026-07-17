<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Response;
use App\Http\View;
use App\Services\CsvImportService;
use Throwable;

final class ImportController
{
    public function __construct(private readonly CsvImportService $service)
    {
    }

    public function form(): void
    {
        View::render('import', ['title' => 'Import transactions']);
    }

    public function store(): void
    {
        if (!\verifyCsrfToken($_POST['csrf_token'] ?? null)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Your session expired. Refresh the page and try again.'];
            Response::redirect(url('import'));
        }

        $file = $_FILES['csv'] ?? null;
        $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($uploadError !== UPLOAD_ERR_OK) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => $this->uploadErrorMessage($uploadError)];
            Response::redirect(url('import'));
        }
        if (($file['size'] ?? 0) > 50 * 1024 * 1024 || strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'csv') {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Only CSV files up to 50 MB are accepted.'];
            Response::redirect(url('import'));
        }
        try {
            $result = $this->service->import($file['tmp_name'], $file['name']);
            $_SESSION['flash'] = $result['duplicate_file']
                ? ['type' => 'info', 'message' => 'This exact file was already processed. No rows were added.']
                : ['type' => 'success', 'message' => sprintf(
                    'Import complete: %d imported, %d rejected, %d duplicates.',
                    $result['counts']['imported'],
                    $result['counts']['rejected'],
                    $result['counts']['duplicates'],
                )];
            Response::redirect(url());
        } catch (Throwable $exception) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Import failed: ' . $exception->getMessage(),
            ];
            Response::redirect(url('import'));
        }
    }

    private function uploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE => sprintf(
                'The selected file exceeds the server upload limit of %s. Restart the development server using the command in README.md.',
                ini_get('upload_max_filesize') ?: '2 MB',
            ),
            UPLOAD_ERR_FORM_SIZE => 'The selected file exceeds the application upload limit.',
            UPLOAD_ERR_PARTIAL => 'The file upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_FILE => 'Select a CSV file to continue.',
            UPLOAD_ERR_NO_TMP_DIR => 'The server upload directory is unavailable.',
            UPLOAD_ERR_CANT_WRITE => 'The server could not save the uploaded file.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
            default => 'The selected CSV could not be uploaded.',
        };
    }
}
