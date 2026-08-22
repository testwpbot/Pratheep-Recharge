<?php

use App\Console\Commands\CheckProviderFunds;
use App\Console\Commands\SyncPendingOrders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Poll providers for pending order statuses every minute
Schedule::command(SyncPendingOrders::class)->everyMinute();

// Compare API float vs customer wallets and email admin when short
Schedule::command(CheckProviderFunds::class)->everyMinute()->withoutOverlapping();

// Email customers whose wallet dropped below the minimum
Schedule::command(\App\Console\Commands\NotifyLowWallets::class)->hourly()->withoutOverlapping();
