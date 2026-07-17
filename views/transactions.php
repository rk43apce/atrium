<section class="page-heading"><div><p class="eyebrow">Ledger</p><h1>Transactions</h1><p><?= number_format($result['total']) ?> records match the current view.</p></div></section>
<form class="filters panel" method="get">
    <input type="hidden" name="route" value="transactions">
    <label class="search-field"><span>Search</span><input name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="ID, reference, merchant"></label>
    <?php foreach (['merchant_name' => 'Merchant', 'currency' => 'Currency', 'status' => 'Status', 'transaction_type' => 'Type'] as $field => $label): ?>
        <label><span><?= e($label) ?></span><select name="<?= e($field) ?>"><option value="">All</option>
        <?php foreach ($options[$field] as $option): ?><option value="<?= e($option) ?>" <?= ($filters[$field] ?? '') === $option ? 'selected' : '' ?>><?= e(ucwords($option)) ?></option><?php endforeach; ?>
        </select></label>
    <?php endforeach; ?>
    <label><span>From</span><input type="date" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>"></label>
    <label><span>To</span><input type="date" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>"></label>
    <button class="button" type="submit">Apply filters</button>
</form>
<section class="table-panel">
<div class="table-wrap"><table><thead><tr>
<?php
$columns = ['transaction_id' => 'Transaction', 'occurred_at' => 'Occurred', 'merchant_name' => 'Merchant', 'amount_cents' => 'Amount', 'status' => 'Status'];
foreach ($columns as $column => $label):
    $nextDirection = $sort === $column && $direction === 'asc' ? 'desc' : 'asc';
    $query = array_merge($filters, ['sort' => $column, 'direction' => $nextDirection]);
?>
<th><a href="<?= e(url('transactions', $query)) ?>"><?= e($label) ?><?= $sort === $column ? ($direction === 'asc' ? ' ↑' : ' ↓') : '' ?></a></th>
<?php endforeach; ?><th>Type</th><th aria-label="Actions"></th></tr></thead>
<tbody>
<?php foreach ($result['rows'] as $row): ?><tr>
<td class="transaction-cell">
    <a class="mono link" href="<?= e(url('transaction', ['id' => $row['id']])) ?>"><?= e($row['transaction_id']) ?></a>
    <small class="reference mono" title="<?= e($row['external_reference']) ?>"><?= e(compactIdentifier($row['external_reference'])) ?></small>
</td>
<td><?= e($row['occurred_at']) ?><small><?= e($row['terminal_id']) ?></small></td>
<td><?= e($row['merchant_name']) ?><small><?= e($row['merchant_id']) ?></small></td>
<td class="amount"><?= e(money((int) $row['amount_cents'], $row['currency'])) ?></td>
<td><span class="badge status-<?= e($row['status']) ?>"><?= e($row['status']) ?></span></td>
<td><?= e(ucfirst($row['transaction_type'])) ?></td><td><a class="icon-link" href="<?= e(url('transaction', ['id' => $row['id']])) ?>" aria-label="View transaction">→</a></td>
</tr><?php endforeach; ?>
<?php if (!$result['rows']): ?><tr><td colspan="7"><div class="empty"><h3>No matching transactions</h3><p>Adjust your filters or import another file.</p></div></td></tr><?php endif; ?>
</tbody></table></div>
<?php require BASE_PATH . '/views/partials/pagination.php'; ?>
</section>
