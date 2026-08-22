<?php

namespace App\Providers;

use App\Models\Alert;
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
        \App\Support\HostingLayout::ensurePublicStorage();

        View::composer('layouts.dashboard', function ($view) {
            $user = auth()->user();
            if (! $user) {
                $view->with('walletNotice', null);
                $view->with('dashboardAlerts', collect());
                return;
            }

            if ($user->isAdmin()) {
                $view->with('walletNotice', null);
            } else {
                $wallet = $user->wallet ?: Wallet::firstOrCreate(['user_id' => $user->id]);
                $view->with('walletNotice', WalletLimits::notice($user, $wallet));
            }

            $view->with('dashboardAlerts', Alert::forDashboard($user));
        });
    }
}
