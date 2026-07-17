<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class TransactionData
{
    public function __construct(
        public string $transactionId,
        public string $occurredAt,
        public string $terminalId,
        public string $cardNumber,
        public string $account,
        public int $amountCents,
        public string $transactionType,
        public string $status,
        public string $merchantId,
        public string $merchantName,
        public string $currency,
        public string $externalReference,
    ) {
    }
}
