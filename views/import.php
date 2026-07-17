<section class="page-heading"><div><p class="eyebrow">Data ingestion</p><h1>Import transactions</h1><p>Stream and validate a CSV without interrupting on malformed rows.</p></div></section>
<section class="upload-layout">
    <form class="panel upload-card" method="post" enctype="multipart/form-data" action="<?= e(url('import')) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <div class="upload-zone">
            <div class="empty-icon">CSV</div>
            <h2>Select a transaction file</h2>
            <p>Required columns are validated before processing. Maximum file size: 50 MB.</p>
            <label class="button primary">Choose CSV<input type="file" name="csv" accept=".csv,text/csv" required></label>
        </div>
        <button class="button full" type="submit">Process file</button>
    </form>
    <aside class="panel checklist"><h2>Import safeguards</h2><ul><li>Constant-memory CSV streaming</li><li>Independent row validation</li><li>Checksum-based file idempotency</li><li>Unique transaction protection</li><li>Rejected row audit trail</li><li>Atomic database commit</li></ul></aside>
</section>
