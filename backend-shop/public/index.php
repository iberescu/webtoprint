<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Reuse the print backend's installed vendor (Laravel + Vanilo + ...) so
// this shop app doesn't need its own composer install during dev.
$autoload = require __DIR__ . '/../../backend/vendor/autoload.php';

// Register our Shop\ namespace on top of the shared autoloader. We don't
// run `composer install` for backend-shop in dev — see backend-shop/composer.json.
// We use a distinct namespace (not `App\`) because backend's optimized
// classmap statically points App\* at backend/app/* and can't be overridden
// by adding PSR-4 paths at runtime.
$autoload->addPsr4('Shop\\', dirname(__DIR__) . '/app/');

/** @var Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

$app->handleRequest(Request::capture());
