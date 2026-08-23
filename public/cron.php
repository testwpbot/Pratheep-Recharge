<?php

/**
 * DirectAdmin clock file.
 *
 * Use ONE of these as the cron command (copy from DirectAdmin's own samples):
 *
 *   php /home/hpprath9/domains/hp-pratheep.online/public_html/cron.php
 *   curl --silent http://www.hp-pratheep.online/cron.php > /dev/null
 *   wget -O /dev/null http://www.hp-pratheep.online/cron.php
 *
 * Minute / Hour / Day / Month / Weekday: * * * * *
 */

define('LARAVEL_START', microtime(true));

$publicDir = __DIR__;
$hostedAutoload = $publicDir.'/laravel/vendor/autoload.php';
$parentAutoload = $publicDir.'/../vendor/autoload.php';

if (is_file($hostedAutoload)) {
    require $hostedAutoload;
    $app = require $publicDir.'/laravel/bootstrap/app.php';
} elseif (is_file($parentAutoload)) {
    require $parentAutoload;
    $app = require $publicDir.'/../bootstrap/app.php';
} else {
    $cli = in_array(PHP_SAPI, ['cli', 'phpdbg'], true);
    if (! $cli) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
    }
    echo "Happy Pratheep cron: app files not found.\n";
    exit(1);
}

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$result = App\Support\WebCron::run();

if (! App\Support\WebCron::isCli()) {
    http_response_code($result['status']);
    header('Content-Type: text/plain; charset=UTF-8');
    header('Cache-Control: no-store');
}

echo $result['body'], PHP_EOL;

exit($result['ok'] ? 0 : 1);
