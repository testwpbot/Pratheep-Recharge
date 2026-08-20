<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * Boot SMTP/bank/general settings from the DB on every request.
     * Falls back silently if the settings table doesn't exist yet (fresh installs).
     */
    public function boot(): void
    {
        try {
            Setting::bootMailConfig();
        } catch (\Throwable $e) {
            // Table not yet migrated — ignore.
        }
    }
}
