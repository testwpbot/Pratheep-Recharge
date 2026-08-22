<?php

namespace App\Providers;

use App\Models\Wallet;
use App\Support\WalletLimits;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        date_default_timezone_set(config('app.timezone', 'Asia/Colombo'));

        View::composer('layouts.dashboard', function ($view) {
            $user = auth()->user();
            if (! $user || $user->isAdmin()) {
                $view->with('walletNotice', null);
                return;
            }

            $wallet = $user->wallet ?: Wallet::firstOrCreate(['user_id' => $user->id]);
            $view->with('walletNotice', WalletLimits::notice($user, $wallet));
        });
    }
}
