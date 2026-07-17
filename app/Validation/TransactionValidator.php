<?php

declare(strict_types=1);

namespace App\Validation;

use App\DTO\TransactionData;
use DateTimeImmutable;

final class TransactionValidator
{
    public const HEADERS = [
        'transaction_id', 'occurred_at', 'terminal_id', 'card_number', 'account', 'amount',
        'transaction_type', 'status', 'merchant_id', 'merchant_name', 'currency', 'external_reference',
    ];

    public function validate(array $row): array
    {
        if (count($row) !== count(self::HEADERS)) {
            return [null, ['row' => 'Expected 12 columns; received ' . count($row) . '.']];
        }

        $data = array_combine(self::HEADERS, array_map(static fn ($v): string => trim((string) $v), $row));
        $errors = [];
        foreach (self::HEADERS as $field) {
            if ($data[$field] === '') {
                $errors[$field] = 'This field is required.';
            }
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $data['occurred_at']);
        if ($data['occurred_at'] !== '' && (!$date || $date->format('Y-m-d H:i:s') !== $data['occurred_at'])) {
            $errors['occurred_at'] = 'Use YYYY-MM-DD HH:MM:SS.';
        }
        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{1,63}$/', $data['transaction_id'])) {
            $errors['transaction_id'] = 'Invalid transaction identifier.';
        }
        if (!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $data['amount']) || (float) $data['amount'] <= 0) {
            $errors['amount'] = 'Amount must be a positive number with at most 2 decimals.';
        }
        if (!in_array(strtolower($data['transaction_type']), ['debit', 'credit', 'reversal', 'adjustment'], true)) {
            $errors['transaction_type'] = 'Allowed: debit, credit, reversal, adjustment.';
        }
        if (!in_array(strtolower($data['status']), ['approved', 'declined', 'reversed', 'pending'], true)) {
            $errors['status'] = 'Allowed: approved, declined, reversed, pending.';
        }
        if (!preg_match('/^[A-Z]{3}$/', strtoupper($data['currency']))) {
            $errors['currency'] = 'Currency must be a 3-letter ISO code.';
        }
        foreach ([
            'terminal_id' => 64,
            'card_number' => 64,
            'account' => 64,
            'merchant_id' => 64,
            'merchant_name' => 160,
            'external_reference' => 2048,
        ] as $field => $maximumLength) {
            if (mb_strlen($data[$field]) > $maximumLength) {
                $errors[$field] = "Must not exceed {$maximumLength} characters.";
            }
        }
        if ($errors !== []) {
            return [null, $errors];
        }

        return [new TransactionData(
            $data['transaction_id'],
            $data['occurred_at'],
            $data['terminal_id'],
            $data['card_number'],
            $data['account'],
            (int) round((float) $data['amount'] * 100),
            strtolower($data['transaction_type']),
            strtolower($data['status']),
            $data['merchant_id'],
            $data['merchant_name'],
            strtoupper($data['currency']),
            $data['external_reference'],
        ), []];
    }
}
