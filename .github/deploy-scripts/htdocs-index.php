<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/_core/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/_core/vendor/autoload.php';

$app = require_once __DIR__.'/_core/bootstrap/app.php';

$app->handleRequest(Request::capture());
