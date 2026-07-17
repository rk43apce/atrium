<section class="page-heading">
    <div><p class="eyebrow">Operations overview</p><h1>Transaction activity</h1><p>Monitor processing volume, import health, and ledger value.</p></div>
    <div class="heading-actions">
        <?php if ((int) ($metrics['total_transactions'] ?? 0) > 0 || !empty($metrics['filename'])): ?>
            <form method="post" action="<?= e(url('reset-data')) ?>" onsubmit="return confirm('Delete all transactions, rejected rows, and import history? This cannot be undone.');">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <button class="button danger" type="submit">Reset all data</button>
            </form>
        <?php endif; ?>
        <a class="button primary" href="<?= e(url('import')) ?>">Import transactions</a>
    </div>
</section>

<section class="metrics" aria-label="Summary">
    <article class="metric featured"><span>Total transactions</span><strong><?= number_format((int) ($metrics['total_transactions'] ?? 0)) ?></strong><small>Unique ledger records</small></article>
    <article class="metric"><span>Total amount</span><strong><?= e(money((int) ($metrics['total_amount'] ?? 0))) ?></strong><small>Across all statuses</small></article>
    <article class="metric"><span>Imported rows</span><strong><?= number_format((int) ($metrics['imported_count'] ?? 0)) ?></strong><small>Latest import</small></article>
    <article class="metric"><span>Rejected rows</span><strong><?= number_format((int) ($metrics['rejected_count'] ?? 0)) ?></strong><small>Latest import</small></article>
    <article class="metric"><span>Duplicates</span><strong><?= number_format((int) ($metrics['duplicate_count'] ?? 0)) ?></strong><small>Safely ignored</small></article>
    <article class="metric"><span>Import duration</span><strong><?= number_format((int) ($metrics['duration_ms'] ?? 0)) ?> ms</strong><small>End-to-end processing</small></article>
</section>

<section class="panel">
    <div class="panel-heading"><div><h2>Latest import</h2><p>Most recent processing run and its audit metadata.</p></div></div>
    <?php if (empty($metrics['filename'])): ?>
        <div class="empty"><div class="empty-icon">↥</div><h3>No data imported yet</h3><p>Upload the provided CSV to build your transaction ledger.</p><a class="button" href="<?= e(url('import')) ?>">Start first import</a></div>
    <?php else: ?>
        <dl class="details-grid">
            <div><dt>Filename</dt><dd><?= e($metrics['filename']) ?></dd></div>
            <div><dt>Status</dt><dd><span class="badge status-<?= e($metrics['status']) ?>"><?= e($metrics['status']) ?></span></dd></div>
            <div><dt>Imported at</dt><dd><?= e($metrics['created_at']) ?> UTC</dd></div>
            <div><dt>Checksum</dt><dd class="mono"><?= e(substr($metrics['checksum'], 0, 16)) ?>…</dd></div>
        </dl>
    <?php endif; ?>
</section>
