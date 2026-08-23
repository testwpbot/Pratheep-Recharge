<?php

namespace Tests\Feature;

use App\Mail\LowWalletBalance;
use App\Models\Category;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletDeposit;
use App\Models\WalletTransaction;
use App\Services\OrderService;
use App\Services\WalletBalanceNotifier;
use App\Services\WalletService;
use App\Support\WalletLimits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WalletMinimumTest extends TestCase
{
    use RefreshDatabase;

    protected function seedService(): Service
    {
        $cat = Category::create(['name' => 'Mobile', 'slug' => 'mobile', 'sort_order' => 1, 'is_active' => true]);
        $p = Provider::create([
            'name' => 'Topup Mart', 'slug' => 'topup-mart', 'country' => 'LK',
            'api_class' => 'topup_mart', 'base_url' => 'https://topupmart.online/api/v2',
            'api_key' => 'k', 'is_active' => true,
        ]);

        return Service::create([
            'provider_id' => $p->id, 'category_id' => $cat->id,
            'op_code' => '181', 'name' => 'Dialog Prepaid', 'type' => 'prepaid',
            'profit' => 0, 'profit_type' => 'FLAT', 'is_active' => true,
        ]);
    }

    protected function fakeProviderOk(): void
    {
        Http::fake(['*topupmart.online/*' => Http::response([
            'status' => 'success', 'transaction_id' => 'T1', 'message' => 'ok',
        ], 200)]);
    }

    public function test_new_customer_with_empty_wallet_cannot_recharge(): void
    {
        $svc = $this->seedService();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 0]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Add at least LKR 100.00');

        app(OrderService::class)->placeOrder($user, $svc->id, '0771234567', 50);
    }

    public function test_wallet_below_minimum_cannot_recharge_even_if_amount_fits(): void
    {
        $svc = $this->seedService();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 80]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Your wallet is below LKR 100.00');

        app(OrderService::class)->placeOrder($user, $svc->id, '0771234567', 50);
    }

    public function test_wallet_at_minimum_can_recharge_and_sends_low_email_once(): void
    {
        Mail::fake();
        $this->fakeProviderOk();

        $svc = $this->seedService();
        $user = User::factory()->create();
        $wallet = Wallet::create(['user_id' => $user->id, 'balance' => 150]);
        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'deposit',
            'amount' => 150,
            'balance_before' => 0,
            'balance_after' => 150,
            'description' => 'seed',
            'transactable_type' => WalletDeposit::class,
            'transactable_id' => 1,
        ]);

        $order = app(OrderService::class)->placeOrder($user, $svc->id, '0771234567', 80);

        $this->assertSame('success', $order->status);
        $this->assertEquals(70, (float) $wallet->fresh()->balance);

        Mail::assertSent(LowWalletBalance::class, function (LowWalletBalance $mail) use ($user) {
            return $mail->hasTo($user->email) && $mail->minBalance === 100.0;
        });

        $this->assertNotNull($wallet->fresh()->low_balance_notified_at);

        Mail::fake();
        $this->expectException(\RuntimeException::class);
        try {
            app(OrderService::class)->placeOrder($user, $svc->id, '0771234567', 50);
        } finally {
            Mail::assertNothingSent();
        }
    }

    public function test_deposit_above_minimum_clears_flag_so_next_drop_emails_again(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $wallet = Wallet::create([
            'user_id' => $user->id,
            'balance' => 40,
            'low_balance_notified_at' => now(),
        ]);
        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'deposit',
            'amount' => 40,
            'balance_before' => 0,
            'balance_after' => 40,
            'description' => 'old',
            'transactable_type' => WalletDeposit::class,
            'transactable_id' => 1,
        ]);

        $deposit = WalletDeposit::create([
            'user_id' => $user->id,
            'amount' => 200,
            'bank_name' => 'BOC',
            'depositor_name' => $user->name,
            'status' => 'pending',
        ]);

        $admin = User::factory()->create(['is_admin' => true]);
        app(WalletService::class)->approve($deposit, $admin->id);

        $wallet->refresh();
        $this->assertEquals(240, (float) $wallet->balance);
        $this->assertNull($wallet->low_balance_notified_at);
        Mail::assertNothingSent();
    }

    public function test_unused_empty_wallet_does_not_get_low_email(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $wallet = Wallet::create(['user_id' => $user->id, 'balance' => 0]);

        $sent = app(WalletBalanceNotifier::class)->sync($wallet, $user);

        $this->assertFalse($sent);
        Mail::assertNothingSent();
        $this->assertNull($wallet->fresh()->low_balance_notified_at);
    }

    public function test_admin_can_recharge_below_minimum(): void
    {
        $this->fakeProviderOk();
        $svc = $this->seedService();
        $admin = User::factory()->create(['is_admin' => true]);
        Wallet::create(['user_id' => $admin->id, 'balance' => 50]);

        $order = app(OrderService::class)->placeOrder($admin, $svc->id, '0771234567', 50);

        $this->assertSame('success', $order->status);
        $this->assertEquals(0, (float) Wallet::where('user_id', $admin->id)->value('balance'));
    }

    public function test_deposit_below_minimum_is_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Wallet::firstOrCreate(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('wallet.deposit'), [
                'amount' => 50,
                'bank_name' => 'Bank of Ceylon',
                'depositor_name' => $user->name,
                'slip' => UploadedFile::fake()->image('slip.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('amount');
    }

    public function test_deposit_of_minimum_is_accepted(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Wallet::firstOrCreate(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('wallet.deposit'), [
                'amount' => 100,
                'bank_name' => 'Bank of Ceylon',
                'depositor_name' => $user->name,
                'slip' => UploadedFile::fake()->image('slip.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('wallet_deposits', [
            'user_id' => $user->id,
            'amount' => 100,
            'status' => 'pending',
        ]);
    }

    public function test_dashboard_asks_new_customer_to_deposit(): void
    {
        $user = User::factory()->create();
        Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Add money to start')
            ->assertSee('New accounts need a first deposit', false);
    }

    public function test_register_page_mentions_minimum_deposit(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('add at least LKR 100.00', false);
    }

    public function test_admin_can_save_minimum_wallet_amount(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $admin->forceFill(['admin_role' => User::ADMIN_ROLE_MAIN])->save();

        $this->actingAs($admin)
            ->post(route('admin.settings.general'), [
                'site_name' => 'Happy Pratheep Recharge',
                'min_wallet_balance' => 250,
            ])
            ->assertRedirect();

        $this->assertEquals(250.0, WalletLimits::minBalance());
        $this->assertEquals(250.0, WalletLimits::minDeposit());
    }

    public function test_http_recharge_returns_json_when_wallet_is_empty(): void
    {
        $svc = $this->seedService();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 0]);

        $this->actingAs($user)
            ->postJson(route('recharge.confirm'), [
                'service_id' => $svc->id,
                'account_number' => '0771234567',
                'amount' => 100,
            ])
            ->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertSee('Add at least LKR 100.00', false);
    }

    public function test_http_recharge_blocked_when_leftover_would_break_reserve(): void
    {
        $svc = $this->seedService();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 100]);

        $this->actingAs($user)
            ->postJson(route('recharge.confirm'), [
                'service_id' => $svc->id,
                'account_number' => '0771234567',
                'amount' => 50,
            ])
            ->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertSee('You must keep LKR 100.00 in your wallet', false)
            ->assertSee('needs LKR 150.00', false);
    }
}
