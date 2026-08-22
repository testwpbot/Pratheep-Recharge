<?php

use App\Http\Controllers\Admin\AdminAlertController;
use App\Http\Controllers\Admin\AdminComplaintController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminDepositController;
use App\Http\Controllers\Admin\AdminFundsController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminPlanController;
use App\Http\Controllers\Admin\AdminProviderController;
use App\Http\Controllers\Admin\AdminServiceController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Admin\AdminSpecialPricingController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Dashboard\ComplaintController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\DepositController;
use App\Http\Controllers\Dashboard\EarningsController;
use App\Http\Controllers\Dashboard\RefundsController;
use App\Http\Controllers\Dashboard\WalletController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\RechargeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public pages
|--------------------------------------------------------------------------
*/
Route::get('/', [PageController::class, 'home'])->name('home');

Route::view('/support',       'pages.placeholder', ['section' => 'Support'])->name('support');
Route::view('/privacy',       'pages.placeholder', ['section' => 'Privacy Policy'])->name('privacy');
Route::view('/terms',         'pages.placeholder', ['section' => 'Terms of Service'])->name('terms');
Route::view('/refund',        'pages.placeholder', ['section' => 'Refund Policy'])->name('refund');
Route::view('/gift-cards',    'pages.placeholder', ['section' => 'Gift Cards'])->name('gift-cards');

/*
|--------------------------------------------------------------------------
| Auth (guest only)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login',    [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login',   [AuthenticatedSessionController::class, 'store']);
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register',[RegisteredUserController::class, 'store']);

    Route::get('/verify-email',          [EmailVerificationController::class, 'show'])->name('otp.show');
    Route::post('/verify-email',         [EmailVerificationController::class, 'store'])->middleware('throttle:10,1')->name('otp.verify');
    Route::post('/verify-email/resend',  [EmailVerificationController::class, 'resend'])->middleware('throttle:5,1')->name('otp.resend');
});
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Public (guest-facing) recharge catalog
|--------------------------------------------------------------------------
*/
Route::get('/services',                [RechargeController::class, 'index'])->name('recharge.index');
Route::get('/services/{categorySlug}', [RechargeController::class, 'index'])->name('recharge.category');

Route::redirect('/mobile-reload', '/services/mobile');
Route::redirect('/postpaid',      '/services/mobile');
Route::redirect('/data-packages', '/services/mobile');
Route::redirect('/broadband',     '/services/broadband');
Route::redirect('/electricity',   '/services/utility');
Route::redirect('/water',         '/services/utility');
Route::redirect('/tv',            '/services/tv');
Route::view('/sign-in',           'auth.login')->name('sign-in');

/*
|--------------------------------------------------------------------------
| Customer dashboard (auth)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified.otp'])->group(function () {
    Route::get('/dashboard',                              [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/alerts/{alert}/dismiss',      [DashboardController::class, 'dismissAlert'])->name('dashboard.alerts.dismiss');
    Route::get('/plans',                                  [DashboardController::class, 'plans'])->name('dashboard.plans');
    Route::get('/wallet',                                 [WalletController::class, 'index'])->name('wallet');
    Route::get('/earnings',                               [EarningsController::class, 'index'])->name('earnings');
    Route::get('/refunds',                                [RefundsController::class, 'index'])->name('refunds');
    Route::post('/wallet/deposit',                        [DepositController::class, 'store'])->name('wallet.deposit');

    // Complaints (customer side)
    Route::get('/complaints',                             [ComplaintController::class, 'index'])->name('complaints');
    Route::get('/complaints/{complaint}',                 [ComplaintController::class, 'show'])->name('complaints.show');
    Route::post('/complaints',                            [ComplaintController::class, 'store'])->name('complaints.store');

    Route::get('/service/{service}',                      [RechargeController::class, 'form'])->name('recharge.form');
    Route::post('/recharge',                              [RechargeController::class, 'confirm'])->name('recharge.confirm');
    Route::get('/orders/{order}',                         [RechargeController::class, 'show'])->name('recharge.show');
    Route::get('/orders/{order}/invoice',                 [RechargeController::class, 'invoice'])->name('recharge.invoice');
    Route::get('/orders/{order}/invoice/download',        [RechargeController::class, 'invoiceDownload'])->name('recharge.invoice.download');
    Route::get('/my-orders',                              [RechargeController::class, 'history'])->name('recharge.history');
});

/*
|--------------------------------------------------------------------------
| Admin panel (auth + admin)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified.otp', 'admin'])->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Users + wallets
    Route::get('/users',                                  [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/create',                           [AdminUserController::class, 'create'])->name('users.create');
    Route::post('/users',                                 [AdminUserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}',                           [AdminUserController::class, 'show'])->name('users.show');
    Route::patch('/users/{user}',                         [AdminUserController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/wallet',                   [AdminUserController::class, 'adjustWallet'])->name('users.wallet');

    // Dashboard alerts
    Route::get('/alerts',                                 [AdminAlertController::class, 'index'])->name('alerts.index');
    Route::get('/alerts/create',                          [AdminAlertController::class, 'create'])->name('alerts.create');
    Route::post('/alerts',                                [AdminAlertController::class, 'store'])->name('alerts.store');
    Route::get('/alerts/{alert}/edit',                    [AdminAlertController::class, 'edit'])->name('alerts.edit');
    Route::patch('/alerts/{alert}',                       [AdminAlertController::class, 'update'])->name('alerts.update');
    Route::delete('/alerts/{alert}',                      [AdminAlertController::class, 'destroy'])->name('alerts.destroy');
    Route::post('/alerts/{alert}/toggle',                 [AdminAlertController::class, 'toggle'])->name('alerts.toggle');

    // Funds health (provider float vs customer wallets)
    Route::get('/funds',                                  [AdminFundsController::class, 'index'])->name('funds.index');
    Route::post('/funds/refresh',                         [AdminFundsController::class, 'refresh'])->name('funds.refresh');
    Route::post('/funds/settings',                        [AdminFundsController::class, 'saveSettings'])->name('funds.settings');

    // Providers
    Route::get('/providers',                              [AdminProviderController::class, 'index'])->name('providers.index');
    Route::get('/providers/{provider}/edit',              [AdminProviderController::class, 'edit'])->name('providers.edit');
    Route::patch('/providers/{provider}',                 [AdminProviderController::class, 'update'])->name('providers.update');
    Route::post('/providers/{provider}/toggle',           [AdminProviderController::class, 'toggle'])->name('providers.toggle');
    Route::post('/providers/{provider}/import',           [AdminProviderController::class, 'import'])->name('providers.import');

    // Services
    Route::get('/services',                               [AdminServiceController::class, 'index'])->name('services.index');
    Route::get('/services/{service}/edit',                [AdminServiceController::class, 'edit'])->name('services.edit');
    Route::patch('/services/{service}',                   [AdminServiceController::class, 'update'])->name('services.update');
    Route::post('/services/{service}/toggle',             [AdminServiceController::class, 'toggle'])->name('services.toggle');
    Route::post('/services/bulk-profit',                  [AdminServiceController::class, 'bulkProfit'])->name('services.bulk');

    // Special pricing (per-retailer commission)
    Route::get('/special-pricing',                        [AdminSpecialPricingController::class, 'index'])->name('special-pricing.index');
    Route::get('/special-pricing/{user}',                 [AdminSpecialPricingController::class, 'edit'])->name('special-pricing.edit');
    Route::patch('/special-pricing/{user}',               [AdminSpecialPricingController::class, 'update'])->name('special-pricing.update');
    Route::post('/special-pricing/{user}/bulk',           [AdminSpecialPricingController::class, 'bulk'])->name('special-pricing.bulk');
    Route::post('/special-pricing/{user}/clear',          [AdminSpecialPricingController::class, 'clear'])->name('special-pricing.clear');
    Route::post('/special-pricing/{user}/retailer',       [AdminSpecialPricingController::class, 'toggleRetailer'])->name('special-pricing.retailer');

    // Orders
    Route::get('/orders',                                 [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}',                         [AdminOrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/sync',                   [AdminOrderController::class, 'sync'])->name('orders.sync');
    Route::post('/orders/{order}/failover',               [AdminOrderController::class, 'failover'])->name('orders.failover');

    // Plans
    Route::get('/plans',                                  [AdminPlanController::class, 'index'])->name('plans.index');
    Route::get('/plans/create',                           [AdminPlanController::class, 'create'])->name('plans.create');
    Route::post('/plans',                                 [AdminPlanController::class, 'store'])->name('plans.store');
    Route::get('/plans/{plan}/edit',                      [AdminPlanController::class, 'edit'])->name('plans.edit');
    Route::patch('/plans/{plan}',                         [AdminPlanController::class, 'update'])->name('plans.update');
    Route::delete('/plans/{plan}',                        [AdminPlanController::class, 'destroy'])->name('plans.destroy');
    Route::post('/plans/{plan}/toggle',                   [AdminPlanController::class, 'toggle'])->name('plans.toggle');

    // Deposits
    Route::get('/deposits',                               [AdminDepositController::class, 'index'])->name('deposits.index');
    Route::get('/deposits/{deposit}',                     [AdminDepositController::class, 'show'])->name('deposits.show');
    Route::post('/deposits/{deposit}/approve',            [AdminDepositController::class, 'approve'])->name('deposits.approve');
    Route::post('/deposits/{deposit}/reject',             [AdminDepositController::class, 'reject'])->name('deposits.reject');

    // Complaints
    Route::get('/complaints',                             [AdminComplaintController::class, 'index'])->name('complaints.index');
    Route::get('/complaints/{complaint}',                 [AdminComplaintController::class, 'show'])->name('complaints.show');
    Route::post('/complaints/{complaint}/reply',          [AdminComplaintController::class, 'reply'])->name('complaints.reply');

    // Settings
    Route::get('/settings',                               [AdminSettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/smtp',                         [AdminSettingsController::class, 'saveSmtp'])->name('settings.smtp');
    Route::post('/settings/general',                      [AdminSettingsController::class, 'saveGeneral'])->name('settings.general');
    Route::post('/settings/whatsapp',                     [AdminSettingsController::class, 'saveWhatsapp'])->name('settings.whatsapp');
    Route::post('/settings/test-smtp',                    [AdminSettingsController::class, 'testSmtp'])->name('settings.test-smtp');
    Route::post('/settings/seo',                          [AdminSettingsController::class, 'saveSeo'])->name('settings.seo');
    Route::post('/settings/banks',                        [AdminSettingsController::class, 'storeBank'])->name('settings.banks.store');
    Route::patch('/settings/banks/{bankAccount}',         [AdminSettingsController::class, 'updateBank'])->name('settings.banks.update');
    Route::delete('/settings/banks/{bankAccount}',        [AdminSettingsController::class, 'destroyBank'])->name('settings.banks.destroy');
    Route::post('/settings/admins',                       [AdminSettingsController::class, 'storeAdmin'])->name('settings.admins.store');
    Route::patch('/settings/admins/{user}',               [AdminSettingsController::class, 'updateAdmin'])->name('settings.admins.update');
    Route::delete('/settings/admins/{user}',              [AdminSettingsController::class, 'destroyAdmin'])->name('settings.admins.destroy');
});
