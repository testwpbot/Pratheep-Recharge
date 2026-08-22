<?php

namespace Tests\Feature;

use App\Console\Commands\CheckProviderFunds;
use App\Mail\ProviderFundsLow;
use App\Models\Provider;
use App\Models\ProviderBalanceSnapshot;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Services\FundHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FundHealthTest extends TestCase
{
    use RefreshDatabase;

    protected function seedProviders(): array
    {
        $tm = Provider::create([
            'name' => 'Topup Mart', 'slug' => 'topup-mart', 'country' => 'LK',
            'api_class' => 'topup_mart', 'base_url' => 'https://topupmart.online/api/v2',
            'api_key' => 'tm-key', 'is_active' => true,
        ]);
        $hrc = Provider::create([
            'name' => 'Happy Recharge Center', 'slug' => 'happy-recharge-center', 'country' => 'IN',
            'api_class' => 'happy_recharge_center',
            'base_url' => 'http://happyrechargecenter.com/RechargeApi',
            'api_key' => 'hrc-key', 'is_active' => true,
        ]);

        return compact('tm', 'hrc');
    }

    protected function fakeBalances(float $tm, float $hrc = 2000): void
    {
        Http::fake([
            '*topupmart.online/api/v2/balance.php*' => Http::response([
                'status' => 'success', 'balance' => $tm,
            ], 200),
            '*happyrechargecenter.com/RechargeApi/Balance.aspx*' => Http::response([
                'STATUS' => 'SUCCESS', 'MESSAGE' => number_format($hrc, 2),
            ], 200),
        ]);
    }

    public function test_lkr_provider_below_wallets_is_low_and_emails_admin(): void
    {
        Mail::fake();
        $this->seedProviders();
        $admin = User::factory()->create(['email' => 'admin@happypratheep.lk']);
        $admin->forceFill(['is_admin' => true])->save();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 10000]);

        $this->fakeBalances(4000, 2500);
        Setting::set('general', 'support_email', 'admin@happypratheep.lk');

        $health = app(FundHealthService::class)->check(fresh: true, persist: true, alert: true);

        $this->assertSame('low', $health['overall']);
        $tm = collect($health['providers'])->firstWhere('slug', 'topup-mart');
        $this->assertSame('low', $tm['status']);
        $this->assertEquals(6000, $tm['shortfall']);
        $this->assertNotEmpty($health['pay']);
        $this->assertEquals(6000, $health['pay'][0]['amount']);
        $this->assertSame('LKR', $health['pay'][0]['currency']);

        Mail::assertSent(ProviderFundsLow::class, function (ProviderFundsLow $mail) {
            return str_contains($mail->envelope()->subject, '6000.00')
                || str_contains($mail->envelope()->subject, '6,000.00')
                || str_contains($mail->envelope()->subject, 'Topup Mart');
        });

        $this->assertDatabaseHas('provider_balance_snapshots', [
            'provider_id' => $tm['id'],
            'status'      => 'low',
        ]);
    }

    public function test_provider_above_wallets_is_healthy_and_no_email(): void
    {
        Mail::fake();
        $this->seedProviders();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 1000]);
        $this->fakeBalances(5000, 2500);

        $health = app(FundHealthService::class)->check(fresh: true, persist: true, alert: true);

        $this->assertSame('healthy', $health['overall']);
        $this->assertSame([], $health['pay']);
        Mail::assertNothingSent();
    }

    public function test_unknown_balance_does_not_email(): void
    {
        Mail::fake();
        Provider::create([
            'name' => 'Topup Mart', 'slug' => 'topup-mart', 'country' => 'LK',
            'api_class' => 'topup_mart', 'base_url' => 'https://topupmart.online/api/v2',
            'api_key' => 'tm-key', 'is_active' => true,
        ]);
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 8000]);

        Http::fake([
            '*topupmart.online/*' => Http::response(['status' => 'failed', 'message' => 'IP ADDRESS NOT CORRECT'], 200),
        ]);

        $health = app(FundHealthService::class)->check(fresh: true, persist: true, alert: true);

        $this->assertSame('unknown', $health['providers'][0]['status']);
        Mail::assertNothingSent();
    }

    public function test_hrc_below_min_inr_is_low(): void
    {
        Mail::fake();
        $this->seedProviders();
        User::factory()->create(['email' => 'a@b.com', 'is_admin' => true]);
        $this->fakeBalances(50000, 80);
        Setting::set('funds', 'min_inr', '500');
        Setting::set('general', 'support_email', 'a@b.com');

        $health = app(FundHealthService::class)->check(fresh: true, persist: true, alert: true);
        $hrc = collect($health['providers'])->firstWhere('slug', 'happy-recharge-center');

        $this->assertSame('low', $hrc['status']);
        $this->assertEquals(420, $hrc['shortfall']);
        $this->assertSame('INR', $hrc['pay_currency']);
        Mail::assertSent(ProviderFundsLow::class);
    }

    public function test_cooldown_prevents_duplicate_email(): void
    {
        Mail::fake();
        $this->seedProviders();
        User::factory()->create(['email' => 'a@b.com', 'is_admin' => true]);
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 9000]);
        $this->fakeBalances(1000, 2500);
        Setting::set('general', 'support_email', 'a@b.com');

        $svc = app(FundHealthService::class);
        $svc->check(fresh: true, persist: true, alert: true);
        $svc->check(fresh: true, persist: true, alert: true);

        Mail::assertSent(ProviderFundsLow::class, 1);
    }

    public function test_admin_pages_render(): void
    {
        $this->seedProviders();
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->fakeBalances(12000, 2500);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Funds Health');

        $this->actingAs($admin)->get(route('admin.funds.index'))
            ->assertOk()
            ->assertSee('Full history')
            ->assertSee('Alert settings');
    }

    public function test_artisan_command_records_snapshot(): void
    {
        Mail::fake();
        $this->seedProviders();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 250]);
        $this->fakeBalances(1000, 2500);

        $this->artisan(CheckProviderFunds::class, ['--fresh' => true])
            ->assertSuccessful();

        $this->assertGreaterThan(0, ProviderBalanceSnapshot::count());
    }
}
