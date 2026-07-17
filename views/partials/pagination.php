<?php if ($result['pages'] > 1): ?><nav class="pagination" aria-label="Pagination">
<span>Page <?= number_format($page) ?> of <?= number_format($result['pages']) ?></span><div>
<?php if ($page > 1): ?><a class="button compact" href="<?= e(url($routeName, array_merge($_GET, ['page' => $page - 1]))) ?>">Previous</a><?php endif; ?>
<?php if ($page < $result['pages']): ?><a class="button compact" href="<?= e(url($routeName, array_merge($_GET, ['page' => $page + 1]))) ?>">Next</a><?php endif; ?>
</div></nav><?php endif; ?>
