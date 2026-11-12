<?php

/*
 * Wasmer Edge mounts a persistent volume at /app/storage. A brand-new volume
 * is empty, so Laravel's runtime directories must exist before boot.
 */
$storagePath = dirname(__DIR__).'/storage';

foreach ([
    'app/private',
    'app/public',
    'framework/cache/data',
    'framework/sessions',
    'framework/views',
    'logs',
] as $directory) {
    $path = $storagePath.'/'.$directory;

    if (! is_dir($path)) {
        @mkdir($path, 0775, true);
    }
}

unset($storagePath, $directory, $path);
