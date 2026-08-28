<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Support\HistoryPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class HistoryPeriodTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_period_is_today(): void
    {
        $period = HistoryPeriod::fromRequest(Request::create('/wallet', 'GET'));
        $this->assertSame('today', $period->period);
        $this->assertTrue($period->from->isToday());
        $this->assertTrue($period->to->isToday());
    }

    public function test_old_order_is_hidden_until_all_days_is_picked(): void
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 500]);
        $svc = $this->seedService();

        $old = Order::create([
            'reference' => 'HPR-OLD-1',
            'user_id' => $user->id,
            'service_id' => $svc['service']->id,
            'provider_id' => $svc['provider']->id,
            'account_number' => '0771234567',
            'amount' => 50,
            'profit' => 0,
            'status' => 'success',
        ]);
        $old->created_at = now()->subDay();
        $old->save();

        $today = Order::create([
            'reference' => 'HPR-TODAY-1',
            'user_id' => $user->id,
            'service_id' => $svc['service']->id,
            'provider_id' => $svc['provider']->id,
            'account_number' => '0771234567',
            'amount' => 80,
            'profit' => 0,
            'status' => 'success',
        ]);

        $this->actingAs($user)
            ->get(route('recharge.history'))
            ->assertOk()
            ->assertSee('HPR-TODAY-1', false)
            ->assertDontSee('HPR-OLD-1', false)
            ->assertSee('Today', false)
            ->assertSee('data-hpr-dd', false)
            ->assertDontSee('Show these dates', false)
            ->assertDontSee('From date', false);

        $this->actingAs($user)
            ->get(route('recharge.history', ['period' => 'all']))
            ->assertOk()
            ->assertSee('HPR-OLD-1', false)
            ->assertSee('HPR-TODAY-1', false);

        $this->assertSame(500.0, (float) Wallet::where('user_id', $user->id)->value('balance'));
        $this->assertTrue(Order::whereKey($old->id)->exists());
        $this->assertTrue(Order::whereKey($today->id)->exists());
    }

    public function test_pending_order_from_yesterday_still_shows_today(): void
    {
        $user = User::factory()->create();
        $svc = $this->seedService();

        $pending = Order::create([
            'reference' => 'HPR-PEND-1',
            'user_id' => $user->id,
            'service_id' => $svc['service']->id,
            'provider_id' => $svc['provider']->id,
            'account_number' => '0771234567',
            'amount' => 50,
            'profit' => 0,
            'status' => 'processing',
            'provider_status' => 'processing',
        ]);
        $pending->created_at = now()->subDay();
        $pending->save();

        $this->actingAs($user)
            ->get(route('recharge.history'))
            ->assertOk()
            ->assertSee('HPR-PEND-1', false);
    }

    public function test_wallet_page_hides_old_transactions_but_keeps_balance(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::create(['user_id' => $user->id, 'balance' => 900]);

        $old = WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'deposit',
            'amount' => 400,
            'balance_before' => 0,
            'balance_after' => 400,
            'description' => 'Old deposit yesterday',
        ]);
        $old->created_at = now()->subDay();
        $old->save();

        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'deposit',
            'amount' => 500,
            'balance_before' => 400,
            'balance_after' => 900,
            'description' => 'Today deposit',
        ]);

        $this->actingAs($user)
            ->get(route('wallet'))
            ->assertOk()
            ->assertSee('LKR 900.00', false)
            ->assertSee('Today deposit', false)
            ->assertDontSee('Old deposit yesterday', false);
    }

    protected function seedService(): void
    {
        $cat = Category::create(['name' => 'Mobile', 'slug' => 'mobile', 'sort_order' => 1, 'is_active' => true]);
        Provider::create([
            'id' => 1, 'name' => 'Topup Mart', 'slug' => 'topup-mart', 'country' => 'LK',
            'api_class' => 'topup_mart', 'is_active' => true,
        ]);
        Service::create([
            'id' => 1, 'provider_id' => 1, 'category_id' => $cat->id,
            'op_code' => '181', 'name' => 'Dialog Prepaid', 'type' => 'prepaid',
            'profit' => 0, 'profit_type' => 'FLAT', 'is_active' => true,
        ]);
    }
}
