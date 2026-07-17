<?php

declare(strict_types=1);

namespace App\DTO;

final class TransactionData
{
    public function __construct(
        public readonly string $transactionId,
        public readonly string $occurredAt,
        public readonly string $terminalId,
        public readonly string $cardNumber,
        public readonly string $account,
        public readonly int $amountCents,
        public readonly string $transactionType,
        public readonly string $status,
        public readonly string $merchantId,
        public readonly string $merchantName,
        public readonly string $currency,
        public readonly string $externalReference,
    ) {
    }
}
