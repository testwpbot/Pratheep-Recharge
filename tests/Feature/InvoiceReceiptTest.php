<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use App\Models\Wallet;
use App\Services\InvoiceService;
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

    public function test_bundled_receipt_fonts_live_inside_the_project(): void
    {
        $svc = app(InvoiceService::class);
        $ref = new \ReflectionClass($svc);
        $m = $ref->getMethod('findFont');
        $m->setAccessible(true);

        $regular = $m->invoke($svc, false);
        $bold = $m->invoke($svc, true);

        $this->assertNotNull($regular);
        $this->assertNotNull($bold);
        $this->assertFileExists($regular);
        $this->assertFileExists($bold);
        $this->assertStringContainsString('resources/fonts', str_replace('\\', '/', $regular));
        $this->assertStringContainsString('resources/fonts', str_replace('\\', '/', $bold));
        $this->assertStringNotContainsString('/usr/share/fonts', $regular);
    }

    public function test_old_success_order_builds_picture_receipt_when_gd_is_available(): void
    {
        if (! function_exists('imagepng') || ! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('PHP GD is not enabled.');
        }

        $svc = $this->seedService();
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance' => 500]);

        $order = Order::create([
            'reference' => 'HPR-20260823-62F95F',
            'user_id' => $user->id,
            'service_id' => $svc->id,
            'provider_id' => $svc->provider_id,
            'account_number' => '0777919042',
            'amount' => 1249,
            'profit' => 6.25,
            'status' => 'success',
            'completed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('recharge.invoice', $order))
            ->assertOk()
            ->assertSee('Download PNG', false)
            ->assertDontSee('picture receipt could not be drawn', false);

        $order->refresh();
        $this->assertTrue(app(InvoiceService::class)->fileIsReady($order));
        $this->assertNotEmpty($order->invoice_path);

        $this->actingAs($user)
            ->get(route('recharge.invoice.file', $order))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
    }
}
