<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $user = User::factory()->create(['is_admin' => true, 'email' => 'boss@example.com']);
        $user->forceFill(['admin_role' => User::ADMIN_ROLE_ADMIN])->save();

        return $user;
    }

    public function test_guest_cannot_open_users_page(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    }

    public function test_customer_cannot_open_users_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_admin_can_list_users_and_see_wallet(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create(['name' => 'Nuwan Shop', 'email' => 'nuwan@example.com']);
        Wallet::create(['user_id' => $customer->id, 'balance' => 350]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Nuwan Shop')
            ->assertSee('nuwan@example.com')
            ->assertSee('LKR 350.00', false);
    }

    public function test_admin_can_create_customer_with_opening_wallet(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name'            => 'Asha Perera',
                'email'           => 'asha@example.com',
                'phone'           => '0771234567',
                'password'        => 'password12',
                'opening_balance' => 250,
            ])
            ->assertRedirect();

        $user = User::where('email', 'asha@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('+94771234567', $user->phone);
        $this->assertNotNull($user->email_verified_at);
        $this->assertFalse($user->is_admin);
        $this->assertEquals(250, (float) $user->wallet->balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $user->wallet->id,
            'type'      => WalletTransaction::TYPE_ADJUST,
        ]);
    }

    public function test_admin_can_update_customer_details(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create([
            'name'  => 'Old Name',
            'email' => 'old@example.com',
            'phone' => '+94771112222',
        ]);
        Wallet::create(['user_id' => $customer->id, 'balance' => 0]);

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $customer), [
                'name'           => 'New Name',
                'email'          => 'new@example.com',
                'phone'          => '0779988776',
                'is_retailer'    => '1',
                'email_verified' => '1',
            ])
            ->assertRedirect();

        $customer->refresh();
        $this->assertSame('New Name', $customer->name);
        $this->assertSame('new@example.com', $customer->email);
        $this->assertSame('+94779988776', $customer->phone);
        $this->assertTrue($customer->is_retailer);
    }

    public function test_admin_cannot_edit_another_admin_profile_here(): void
    {
        $admin = $this->admin();
        $other = User::factory()->create(['is_admin' => true, 'email' => 'otheradmin@example.com']);
        $other->forceFill(['admin_role' => User::ADMIN_ROLE_ADMIN])->save();

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $other), [
                'name'  => 'Hacked',
                'email' => 'hacked@example.com',
                'phone' => '0771111111',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('otheradmin@example.com', $other->fresh()->email);
    }

    public function test_admin_can_add_and_remove_wallet_money(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create();
        $wallet = Wallet::create(['user_id' => $customer->id, 'balance' => 200]);

        $this->actingAs($admin)
            ->post(route('admin.users.wallet', $customer), [
                'mode'   => 'add',
                'amount' => 100,
                'note'   => 'Cash received at the shop',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals(300, (float) $wallet->fresh()->balance);

        $this->actingAs($admin)
            ->post(route('admin.users.wallet', $customer), [
                'mode'   => 'remove',
                'amount' => 50,
                'note'   => 'Correction',
            ])
            ->assertRedirect();

        $this->assertEquals(250, (float) $wallet->fresh()->balance);
        $this->assertSame(2, $wallet->transactions()->where('type', 'adjustment')->count());

        $this->actingAs($admin)
            ->get(route('admin.users.show', $customer))
            ->assertOk()
            ->assertSee('Manual fund add', false)
            ->assertSee('Manual funds remove', false)
            ->assertDontSee('>Adjustment<', false);

        $this->actingAs($customer)
            ->get(route('wallet'))
            ->assertOk()
            ->assertSee('Manual fund add', false)
            ->assertSee('Manual funds remove', false)
            ->assertDontSee('>Adjustment<', false);
    }

    public function test_admin_can_set_exact_wallet_amount(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create();
        $wallet = Wallet::create(['user_id' => $customer->id, 'balance' => 80]);

        $this->actingAs($admin)
            ->post(route('admin.users.wallet', $customer), [
                'mode'   => 'set',
                'amount' => 500,
                'note'   => 'Opening float for retailer',
            ])
            ->assertRedirect();

        $this->assertEquals(500, (float) $wallet->fresh()->balance);
    }

    public function test_cannot_take_out_more_than_wallet_has(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create();
        Wallet::create(['user_id' => $customer->id, 'balance' => 40]);

        $this->actingAs($admin)
            ->post(route('admin.users.wallet', $customer), [
                'mode'   => 'remove',
                'amount' => 100,
                'note'   => 'Too much',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertEquals(40, (float) Wallet::where('user_id', $customer->id)->value('balance'));
    }

    public function test_search_finds_customer(): void
    {
        $admin = $this->admin();
        User::factory()->create(['name' => 'Kamal Silva', 'email' => 'kamal@example.com']);
        User::factory()->create(['name' => 'Other Person', 'email' => 'other@example.com']);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => 'Kamal']))
            ->assertOk()
            ->assertSee('Kamal Silva')
            ->assertDontSee('Other Person');
    }

    public function test_admin_can_delete_customer(): void
    {
        $admin = $this->admin();
        $customer = User::factory()->create(['name' => 'Gone User', 'email' => 'gone@example.com']);
        Wallet::create(['user_id' => $customer->id, 'balance' => 20]);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $customer))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', ['id' => $customer->id]);
        $this->assertDatabaseMissing('wallets', ['user_id' => $customer->id]);
    }

    public function test_admin_cannot_delete_another_admin_here(): void
    {
        $admin = $this->admin();
        $other = User::factory()->create(['is_admin' => true, 'email' => 'keepadmin@example.com']);
        $other->forceFill(['admin_role' => User::ADMIN_ROLE_ADMIN])->save();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $other))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $other->id]);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
