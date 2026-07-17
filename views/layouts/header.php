<?php
$routeName = trim((string) ($_GET['route'] ?? ''), '/');
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Transaction operations console">
    <title><?= e($title ?? 'Ledger') ?> · Atrium Ledger</title>
    <link rel="stylesheet" href="<?= e((defined('PUBLIC_ASSET_PREFIX') ? PUBLIC_ASSET_PREFIX : '') . 'assets/app.css') ?>">
</head>
<body>
<header class="topbar">
    <a class="brand" href="<?= e(url()) ?>"><span class="brand-mark">A</span><span>Atrium <b>Ledger</b></span></a>
    <nav aria-label="Primary navigation">
        <a class="<?= $routeName === '' ? 'active' : '' ?>" href="<?= e(url()) ?>">Overview</a>
        <a class="<?= str_starts_with($routeName, 'transaction') ? 'active' : '' ?>" href="<?= e(url('transactions')) ?>">Transactions</a>
        <a class="<?= $routeName === 'rejected' ? 'active' : '' ?>" href="<?= e(url('rejected')) ?>">Rejected rows</a>
        <a class="<?= str_starts_with($routeName, 'reports') ? 'active' : '' ?>" href="<?= e(url('reports/daily')) ?>">Reports</a>
    </nav>
    <a class="button primary compact" href="<?= e(url('import')) ?>">Import CSV</a>
</header>
<main class="shell">
<?php if ($flash): ?>
    <div class="alert <?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
<?php endif; ?>
