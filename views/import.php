<section class="page-heading"><div><p class="eyebrow">Data ingestion</p><h1>Import transactions</h1><p>Stream and validate a CSV without interrupting on malformed rows.</p></div></section>
<section class="upload-layout">
    <form class="panel upload-card" id="csv-import-form" method="post" enctype="multipart/form-data" action="<?= e(url('import')) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <div class="upload-zone">
            <div class="empty-icon">CSV</div>
            <h2>Select a transaction file</h2>
            <p>Required columns are validated before processing. Maximum file size: 50 MB.</p>
            <label class="button primary">Choose CSV<input id="csv-file" type="file" name="csv" accept=".csv,text/csv" required></label>
            <p class="selected-file" id="selected-file" aria-live="polite"></p>
        </div>
        <div class="import-progress" id="import-progress" hidden>
            <div class="progress-meta"><strong id="progress-title">Uploading CSV</strong><span id="progress-percent">0%</span></div>
            <div class="progress-track" role="progressbar" aria-label="CSV import progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                <span id="progress-bar"></span>
            </div>
            <p id="progress-message" aria-live="polite">Preparing upload…</p>
        </div>
        <button class="button full" id="import-submit" type="submit">Process file</button>
    </form>
    <aside class="panel checklist"><h2>Import safeguards</h2><ul><li>Constant-memory CSV streaming</li><li>Independent row validation</li><li>Checksum-based file idempotency</li><li>Unique transaction protection</li><li>Rejected row audit trail</li><li>Atomic database commit</li></ul></aside>
</section>
<script>
(() => {
    const form = document.getElementById('csv-import-form');
    const fileInput = document.getElementById('csv-file');
    const fileLabel = document.getElementById('selected-file');
    const progress = document.getElementById('import-progress');
    const track = progress.querySelector('[role="progressbar"]');
    const bar = document.getElementById('progress-bar');
    const title = document.getElementById('progress-title');
    const percent = document.getElementById('progress-percent');
    const message = document.getElementById('progress-message');
    const submit = document.getElementById('import-submit');

    fileInput.addEventListener('change', () => {
        const file = fileInput.files[0];
        fileLabel.textContent = file ? `${file.name} · ${(file.size / 1024 / 1024).toFixed(2)} MB` : '';
    });

    form.addEventListener('submit', (event) => {
        if (!fileInput.files.length || !window.XMLHttpRequest) return;
        event.preventDefault();
        const formData = new FormData(form);
        submit.disabled = true;
        fileInput.disabled = true;
        progress.hidden = false;
        progress.classList.remove('processing', 'complete', 'failed');

        const request = new XMLHttpRequest();
        request.open('POST', form.action);
        request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        request.setRequestHeader('Accept', 'application/json');

        request.upload.addEventListener('progress', (upload) => {
            if (!upload.lengthComputable) return;
            const value = Math.min(80, Math.round((upload.loaded / upload.total) * 80));
            bar.style.width = `${value}%`;
            percent.textContent = `${Math.round((upload.loaded / upload.total) * 100)}%`;
            track.setAttribute('aria-valuenow', String(value));
            message.textContent = 'Securely transferring your file…';
        });

        request.upload.addEventListener('load', () => {
            progress.classList.add('processing');
            bar.style.width = '88%';
            title.textContent = 'Validating and importing';
            percent.textContent = '';
            message.textContent = 'Streaming rows, checking duplicates, and recording validation results…';
            track.setAttribute('aria-valuenow', '88');
        });

        request.addEventListener('load', () => {
            let response;
            try { response = JSON.parse(request.responseText); } catch (_) { response = null; }
            if (request.status >= 200 && request.status < 300 && response?.data) {
                progress.classList.remove('processing');
                progress.classList.add('complete');
                bar.style.width = '100%';
                title.textContent = 'Import complete';
                percent.textContent = '100%';
                message.textContent = response.data.message;
                track.setAttribute('aria-valuenow', '100');
                window.setTimeout(() => window.location.assign(response.data.redirect), 900);
                return;
            }
            progress.classList.remove('processing');
            progress.classList.add('failed');
            bar.style.width = '100%';
            title.textContent = 'Import failed';
            percent.textContent = '';
            message.textContent = response?.error?.message || 'The import could not be completed. Please try again.';
            submit.disabled = false;
            fileInput.disabled = false;
        });

        request.addEventListener('error', () => {
            progress.classList.remove('processing');
            progress.classList.add('failed');
            title.textContent = 'Connection interrupted';
            percent.textContent = '';
            message.textContent = 'Check your connection and try again.';
            submit.disabled = false;
            fileInput.disabled = false;
        });

        request.send(formData);
    });
})();
</script>
