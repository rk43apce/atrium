<section class="page-heading">
    <div>
        <p class="eyebrow">Reconciliation</p>
        <h1>Daily settlement</h1>
        <p>Daily ledger totals grouped by settlement date and currency.</p>
    </div>
</section>

<?php if ($filterError): ?>
    <div class="alert error" role="alert"><?= e($filterError) ?></div>
<?php endif; ?>

<form class="filters report-filters panel" method="get">
    <input type="hidden" name="route" value="reports/daily">
    <label><span>From</span><input type="date" name="date_from" value="<?= e($dateFrom ?? '') ?>"></label>
    <label><span>To</span><input type="date" name="date_to" value="<?= e($dateTo ?? '') ?>"></label>
    <button class="button" type="submit">Apply range</button>
    <a class="button" href="<?= e(url('reports/daily')) ?>">Reset</a>
</form>

<section class="table-panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Date</th><th>Currency</th><th>Transactions</th><th>Approved</th><th>Declined</th><th>Reversed</th><th>Gross amount</th><th>Approved amount</th><th>Net settlement</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><strong><?= e($row['settlement_date']) ?></strong></td>
                    <td><span class="badge"><?= e($row['currency']) ?></span></td>
                    <td><?= number_format((int) $row['transaction_count']) ?></td>
                    <td class="positive"><?= number_format((int) $row['approved_count']) ?></td>
                    <td><?= number_format((int) $row['declined_count']) ?></td>
                    <td><?= number_format((int) $row['reversed_count']) ?></td>
                    <td class="amount"><?= e(money((int) $row['gross_amount_cents'], $row['currency'])) ?></td>
                    <td class="amount"><?= e(money((int) $row['approved_amount_cents'], $row['currency'])) ?></td>
                    <td class="amount <?= (int) $row['net_settlement_cents'] < 0 ? 'negative' : 'positive' ?>"><?= e(money((int) $row['net_settlement_cents'], $row['currency'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($rows === []): ?><tr><td colspan="9"><div class="empty"><h3>No settlement data</h3><p>Adjust the date range or import transactions.</p></div></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
