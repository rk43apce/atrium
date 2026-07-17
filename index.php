<?php

declare(strict_types=1);

/*
 * Compatibility entry point for hosting environments whose document root is
 * fixed to this project directory. Prefer public/ as the document root when
 * server configuration is available.
 */
define('PUBLIC_ASSET_PREFIX', 'public/');

require __DIR__ . '/public/index.php';
