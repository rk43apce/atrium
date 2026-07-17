<?php

declare(strict_types=1);

use App\Validation\TransactionValidator;

require dirname(__DIR__) . '/app/DTO/TransactionData.php';
require dirname(__DIR__) . '/app/Validation/TransactionValidator.php';

$validator = new TransactionValidator();
$valid = ['TX-1', '2026-07-11 18:18:32', 'T-1', 'CARD-1', 'MEAL', '12.34', 'debit', 'approved', 'M1', 'Cafe', 'usd', 'EXT-1'];
[$transaction, $errors] = $validator->validate($valid);
assert($errors === []);
assert($transaction?->amountCents === 1234);
assert($transaction?->currency === 'USD');

$invalid = $valid;
$invalid[1] = 'tomorrow';
$invalid[5] = '-4';
$invalid[7] = 'unknown';
[$transaction, $errors] = $validator->validate($invalid);
assert($transaction === null);
assert(isset($errors['occurred_at'], $errors['amount'], $errors['status']));

[$transaction, $errors] = $validator->validate(['too', 'short']);
assert($transaction === null);
assert(isset($errors['row']));

$invalid = $valid;
$invalid[9] = str_repeat('M', 161);
[$transaction, $errors] = $validator->validate($invalid);
assert($transaction === null);
assert(isset($errors['merchant_name']));

fwrite(STDOUT, "TransactionValidatorTest: OK\n");
