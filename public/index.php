<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$publicDir = __DIR__;
$appDir = is_file($publicDir.'/laravel/bootstrap/app.php')
    ? $publicDir.'/laravel'
    : $publicDir.'/..';

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $appDir.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $appDir.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once $appDir.'/bootstrap/app.php')
    ->handleRequest(Request::capture());
