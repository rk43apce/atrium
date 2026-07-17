<?php if (!$transaction): ?>
<div class="empty panel"><h1>Transaction not found</h1><p>The requested ledger record does not exist.</p><a class="button" href="<?= e(url('transactions')) ?>">Back to transactions</a></div>
<?php else: ?>
<section class="page-heading"><div><p class="eyebrow">Transaction detail</p><h1 class="mono"><?= e($transaction['transaction_id']) ?></h1><p>Immutable transaction and processing metadata.</p></div><span class="badge large status-<?= e($transaction['status']) ?>"><?= e($transaction['status']) ?></span></section>
<section class="panel"><dl class="details-grid detail-wide">
<?php foreach ([
    'Occurred at' => $transaction['occurred_at'] . ' UTC', 'Amount' => money((int) $transaction['amount_cents'], $transaction['currency']),
    'Merchant' => $transaction['merchant_name'], 'Merchant ID' => $transaction['merchant_id'],
    'Type' => ucfirst($transaction['transaction_type']), 'Account' => $transaction['account'],
    'Terminal' => $transaction['terminal_id'], 'Card token' => $transaction['card_number'],
    'External reference' => $transaction['external_reference'], 'Import ID' => '#' . $transaction['import_id'],
] as $label => $value): ?><div><dt><?= e($label) ?></dt><dd class="<?= $label === 'External reference' ? 'break-anywhere mono' : '' ?>"><?= e($value) ?></dd></div><?php endforeach; ?>
</dl></section>
<?php endif; ?>
