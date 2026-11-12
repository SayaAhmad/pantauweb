<?php

/*
 * Console entrypoint for Wasmer Edge.
 *
 * Wasmer only mounts directories, so the repository's `artisan` file cannot be
 * mapped into the package. This file is mounted as /app/console.php and boots
 * the same console kernel.
 */

use Illuminate\Foundation\Application;
use Symfony\Component\Console\Input\ArgvInput;

define('LARAVEL_START', microtime(true));

// Inside the Wasmer package this file lives at /app/console.php. When run from
// the repository it lives in wasmer/ and the base path is one level up.
$basePath = is_file(__DIR__.'/vendor/autoload.php') ? __DIR__ : dirname(__DIR__);

require $basePath.'/bootstrap/wasmer-storage.php';

require $basePath.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $basePath.'/bootstrap/app.php';

$status = $app->handleCommand(new ArgvInput);

exit($status);
