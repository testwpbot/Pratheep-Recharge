<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\OrderService;
use App\Support\PreferredRoute;
use App\Support\ProviderErrors;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderProviderMessagesTest extends TestCase
{
    use RefreshDatabase;

    protected function seedDialog(): array
    {
        $cat = Category::create([
            'name' => 'Mobile', 'slug' => 'mobile', 'sort_order' => 1, 'is_active' => true,
        ]);
        $p = Provider::create([
            'name' => 'Topup Mart', 'slug' => 'topup-mart', 'country' => 'LK',
            'api_class' => 'topup_mart', 'base_url' => 'https://topupmart.online/api/v2',
            'api_key' => 'k', 'is_active' => true,
        ]);
        $prepaid = Service::create([
            'provider_id' => $p->id, 'category_id' => $cat->id,
            'op_code' => '181', 'name' => 'Dialog Prepaid', 'type' => 'prepaid',
            'profit' => 5, 'profit_type' => 'FLAT', 'is_active' => true,
        ]);
        $api = Service::create([
            'provider_id' => $p->id, 'category_id' => $cat->id,
            'op_code' => '921', 'name' => 'Dialog', 'type' => 'api',
            'profit' => 0, 'profit_type' => 'FLAT', 'is_active' => true,
        ]);

        return compact('cat', 'p', 'prepaid', 'api');
    }

    public function test_funds_words_are_detected(): void
    {
        $this->assertTrue(ProviderErrors::isFundsIssue('Insufficient balance'));
        $this->assertTrue(ProviderErrors::isFundsIssue('No provider money'));
        $this->assertFalse(ProviderErrors::isFundsIssue('Invalid mobile number'));
    }

    public function test_provider_out_of_money_stays_processing_and_hides_error_from_customer(): void
    {
        $ctx = $this->seedDialog();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 500]);
        $admin = User::factory()->create(['is_admin' => true]);
        $admin->forceFill(['admin_role' => User::ADMIN_ROLE_ADMIN])->save();

        Http::fake([
            '*topupmart.online/api/v2/recharge.php' => Http::response([
                'status' => 'failed', 'message' => 'Insufficient balance',
            ], 200),
        ]);

        $this->actingAs($user)
            ->postJson(route('recharge.confirm'), [
                'service_id' => $ctx['prepaid']->id,
                'account_number' => '0777919042',
                'amount' => 100,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('status', 'processing')
            ->assertJsonMissing(['message' => 'Insufficient balance']);

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertSame(Order::STATUS_PROCESSING, $order->status);
        $this->assertTrue($order->isAwaitingProviderFunds());
        $this->assertSame('Insufficient balance', $order->message);
        $this->assertEquals(400, (float) Wallet::where('user_id', $user->id)->value('balance'));
        $this->assertEquals(0, WalletTransaction::where('transactable_id', $order->id)->where('type', 'refund')->count());

        $this->actingAs($user)
            ->get(route('recharge.show', $order))
            ->assertOk()
            ->assertSee('Processing', false)
            ->assertSee('being processed', false)
            ->assertDontSee('Insufficient balance', false)
            ->assertSee('Dialog Prepaid', false);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Insufficient balance', false)
            ->assertSee('does not have enough money', false)
            ->assertSee('Processing', false)
            ->assertSee('Automatic Dialog Prepaid', false)
            ->assertSee('will not switch this to Dialog Prepaid', false);
    }

    public function test_cron_resends_when_provider_has_money_again(): void
    {
        $ctx = $this->seedDialog();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 500]);

        Http::fake([
            '*topupmart.online/api/v2/recharge.php' => Http::sequence()
                ->push(['status' => 'failed', 'message' => 'Insufficient balance'], 200)
                ->push(['status' => 'success', 'transaction_id' => 'TM-OK', 'message' => 'ok'], 200),
            '*topupmart.online/api/v2/balance.php' => Http::response([
                'status' => 'success', 'balance' => 8000,
            ], 200),
            '*topupmart.online/api/v2/status.php' => Http::response([
                'status' => 'pending', 'message' => 'wait',
            ], 200),
        ]);

        $orders = app(OrderService::class);
        $order = $orders->placeOrder($user, $ctx['prepaid']->id, '0771234567', 100);
        $this->assertSame(Order::STATUS_PROCESSING, $order->status);
        $this->assertTrue($order->isAwaitingProviderFunds());

        $this->assertGreaterThanOrEqual(1, $orders->syncPending());
        $order->refresh();
        $this->assertSame('success', $order->status);
        $this->assertSame('TM-OK', $order->provider_txn_id);
        $this->assertEquals(405, (float) Wallet::where('user_id', $user->id)->value('balance'));
    }

    public function test_dialog_api_pending_five_minutes_moves_to_prepaid(): void
    {
        $ctx = $this->seedDialog();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 500]);

        Http::fake([
            '*topupmart.online/api/v2/recharge.php' => Http::sequence()
                ->push(['status' => 'pending', 'transaction_id' => 'API-1', 'message' => 'queued'], 200)
                ->push(['status' => 'success', 'transaction_id' => 'PRE-1', 'message' => 'ok'], 200),
            '*topupmart.online/api/v2/status.php' => Http::response([
                'status' => 'pending', 'message' => 'still waiting',
            ], 200),
        ]);

        $orders = app(OrderService::class);
        $order = $orders->placeOrder($user, $ctx['prepaid']->id, '0771234567', 100);
        $this->assertSame('921', $order->sendOpCode());
        $this->assertSame('pending', $order->status);

        $this->travel(PreferredRoute::AUTO_FALLBACK_MINUTES + 1)->minutes();
        $this->assertGreaterThanOrEqual(1, $orders->syncPending());

        $order->refresh();
        $this->assertSame('181', $order->sendOpCode());
        $this->assertSame('success', $order->status);
        $this->assertSame('Dialog Prepaid', $order->customerServiceName());
        $this->assertNotEmpty($order->provider_response['_auto_fallback_at'] ?? null);
    }

    public function test_dialog_api_fail_retries_dialog_prepaid_same_order(): void
    {
        $ctx = $this->seedDialog();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 500]);

        Http::fake([
            '*topupmart.online/api/v2/recharge.php' => Http::sequence()
                ->push(['status' => 'failed', 'message' => 'Recharge failed.'], 200)
                ->push(['status' => 'success', 'transaction_id' => 'PRE-OK', 'message' => 'ok'], 200),
        ]);

        $order = app(OrderService::class)->placeOrder($user, $ctx['prepaid']->id, '0767286364', 50);

        $this->assertSame($order->id, Order::first()->id);
        $this->assertSame('success', $order->status);
        $this->assertSame('181', $order->sendOpCode());
        $this->assertSame('PRE-OK', $order->provider_txn_id);
        $this->assertSame('Dialog Prepaid', $order->customerServiceName());
        $this->assertNotEmpty($order->provider_response['_auto_fallback_at'] ?? null);
        $this->assertEquals(455, (float) Wallet::where('user_id', $user->id)->value('balance'));
        $this->assertEquals(0, WalletTransaction::where('transactable_id', $order->id)->where('type', 'refund')->count());
        $this->assertEquals(1, Order::count());
    }

    public function test_dialog_api_then_prepaid_both_fail_refunds_once(): void
    {
        $ctx = $this->seedDialog();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 500]);

        Http::fake([
            '*topupmart.online/api/v2/recharge.php' => Http::sequence()
                ->push(['status' => 'failed', 'message' => 'Recharge failed.'], 200)
                ->push(['status' => 'failed', 'message' => 'Recharge failed.'], 200),
        ]);

        $order = app(OrderService::class)->placeOrder($user, $ctx['prepaid']->id, '0767286364', 50);

        $this->assertSame(Order::STATUS_REFUNDED, $order->status);
        $this->assertSame('181', $order->sendOpCode());
        $this->assertEquals(500, (float) Wallet::where('user_id', $user->id)->value('balance'));
        $this->assertEquals(1, WalletTransaction::where('transactable_id', $order->id)->where('type', 'refund')->count());
        $this->assertEquals(1, Order::count());
    }

    public function test_cron_sends_stuck_processing_dialog_api_fail_to_prepaid(): void
    {
        $ctx = $this->seedDialog();
        $user = User::factory()->create();
        $wallet = Wallet::create(['user_id' => $user->id, 'balance' => 400]);

        $order = Order::create([
            'reference' => 'HPR-20260823-STUCK1',
            'user_id' => $user->id,
            'service_id' => $ctx['prepaid']->id,
            'provider_id' => $ctx['p']->id,
            'account_number' => '0767286364',
            'amount' => 50,
            'profit' => 0.25,
            'status' => 'processing',
            'provider_status' => 'processing',
            'message' => 'Recharge failed.',
            'processed_at' => null,
            'provider_response' => [
                '_catalog_service_id' => $ctx['prepaid']->id,
                '_catalog_service_name' => 'Dialog Prepaid',
                '_route_service_id' => $ctx['api']->id,
                '_route_op_code' => '921',
                '_route_started_at' => now()->subMinutes(10)->toDateTimeString(),
                'status' => 'failed',
                'message' => 'Recharge failed.',
            ],
        ]);

        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'transactable_type' => Order::class,
            'transactable_id' => $order->id,
            'type' => 'debit',
            'amount' => 50,
            'balance_before' => 450,
            'balance_after' => 400,
            'description' => 'Recharge: Dialog Prepaid 0767286364',
        ]);

        Http::fake([
            '*topupmart.online/api/v2/recharge.php' => Http::response([
                'status' => 'success', 'transaction_id' => 'PRE-STUCK', 'message' => 'ok',
            ], 200),
            '*topupmart.online/api/v2/status.php' => Http::response([
                'status' => 'pending', 'message' => 'not found',
            ], 200),
        ]);

        $this->assertGreaterThanOrEqual(1, app(OrderService::class)->syncPending());

        $order->refresh();
        $this->assertSame('181', $order->sendOpCode());
        $this->assertSame('success', $order->status);
        $this->assertSame('PRE-STUCK', $order->provider_txn_id);
        $this->assertNotEmpty($order->provider_response['_auto_fallback_at'] ?? null);
    }

    public function test_cron_does_not_send_funds_wait_to_prepaid_after_five_minutes(): void
    {
        $ctx = $this->seedDialog();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 500]);

        Http::fake([
            '*topupmart.online/api/v2/recharge.php' => Http::response([
                'status' => 'failed', 'message' => 'Insufficient balance',
            ], 200),
            '*topupmart.online/api/v2/balance.php' => Http::response([
                'status' => 'success', 'balance' => 0,
            ], 200),
            '*topupmart.online/api/v2/status.php' => Http::response([
                'status' => 'failed', 'message' => 'Insufficient balance',
            ], 200),
        ]);

        $orders = app(OrderService::class);
        $order = $orders->placeOrder($user, $ctx['prepaid']->id, '0771234567', 100);
        $this->assertTrue($order->isAwaitingProviderFunds());
        $this->assertSame('921', $order->sendOpCode());

        $this->travel(PreferredRoute::AUTO_FALLBACK_MINUTES + 5)->minutes();
        $orders->syncPending();

        $order->refresh();
        $this->assertSame('921', $order->sendOpCode());
        $this->assertSame(Order::STATUS_PROCESSING, $order->status);
        $this->assertTrue($order->isAwaitingProviderFunds());
        $this->assertEmpty($order->provider_response['_auto_fallback_at'] ?? null);
    }

    public function test_quick_recharge_hides_dialog_api_card(): void
    {
        $ctx = $this->seedDialog();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-op-name="Dialog Prepaid"', false)
            ->assertDontSee('data-service-id="'.$ctx['api']->id.'"', false);
    }
}
