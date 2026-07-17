<section class="page-heading"><div><p class="eyebrow">Import quality</p><h1>Rejected rows</h1><p>Inspect malformed data without losing valid transactions.</p></div><span class="count-pill"><?= number_format($result['total']) ?> total</span></section>
<section class="table-panel"><div class="table-wrap"><table><thead><tr><th>File / row</th><th>Original row</th><th>Validation errors</th><th>Recorded</th></tr></thead><tbody>
<?php foreach ($result['rows'] as $row): $errors = json_decode($row['validation_errors'], true) ?: []; ?>
<tr><td><?= e($row['filename']) ?><small>Row <?= number_format((int) $row['row_number']) ?></small></td><td><code class="raw"><?= e($row['original_row']) ?></code></td><td><ul class="error-list"><?php foreach ($errors as $field => $error): ?><li><b><?= e($field) ?></b> <?= e($error) ?></li><?php endforeach; ?></ul></td><td><?= e($row['created_at']) ?></td></tr>
<?php endforeach; ?>
<?php if (!$result['rows']): ?><tr><td colspan="4"><div class="empty"><div class="empty-icon">✓</div><h3>No rejected rows</h3><p>Validation issues will appear here with precise reasons.</p></div></td></tr><?php endif; ?>
</tbody></table></div><?php require BASE_PATH . '/views/partials/pagination.php'; ?></section>
