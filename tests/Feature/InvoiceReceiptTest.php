<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InvoiceReceiptTest extends TestCase
{
    use RefreshDatabase;

    protected function seedService(): Service
    {
        $cat = Category::create([
            'name' => 'Mobile', 'slug' => 'mobile', 'sort_order' => 1, 'is_active' => true,
        ]);
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

    public function test_pending_order_json_points_to_order_page_not_receipt(): void
    {
        $svc = $this->seedService();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 500]);

        Http::fake([
            '*topupmart.online/*' => Http::response([
                'status' => 'pending', 'transaction_id' => 'TM-WAIT', 'message' => 'queued',
            ], 200),
        ]);

        $this->actingAs($user)
            ->postJson(route('recharge.confirm'), [
                'service_id' => $svc->id,
                'account_number' => '0771234567',
                'amount' => 100,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('has_invoice', false)
            ->assertJsonPath('order.redirect', route('recharge.show', Order::first()));
    }

    public function test_pending_receipt_page_has_no_download_button(): void
    {
        $svc = $this->seedService();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 500]);

        $order = Order::create([
            'reference' => 'HPR-TEST-PEND',
            'user_id' => $user->id,
            'service_id' => $svc->id,
            'provider_id' => $svc->provider_id,
            'account_number' => '0771234567',
            'amount' => 100,
            'profit' => 0,
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->get(route('recharge.invoice', $order))
            ->assertOk()
            ->assertSee('Receipt not ready yet', false)
            ->assertDontSee('Download PNG', false);

        $this->actingAs($user)
            ->getJson(route('recharge.invoice.download', $order))
            ->assertStatus(409)
            ->assertJsonPath('ok', false);
    }
}
