<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUsersTest extends TestCase
{
    use RefreshDatabase;

    protected function mainAdmin(): User
    {
        $user = User::factory()->create([
            'email'    => 'owner@example.com',
            'is_admin' => true,
        ]);
        $user->forceFill(['admin_role' => User::ADMIN_ROLE_MAIN])->save();

        return $user;
    }

    protected function staffAdmin(): User
    {
        $user = User::factory()->create([
            'email'    => 'staff@example.com',
            'is_admin' => true,
        ]);
        $user->forceFill(['admin_role' => User::ADMIN_ROLE_ADMIN])->save();

        return $user;
    }

    public function test_main_admin_can_add_admin(): void
    {
        $owner = $this->mainAdmin();

        $this->actingAs($owner)
            ->post(route('admin.settings.admins.store'), [
                'name'     => 'Kamal',
                'email'    => 'kamal@example.com',
                'phone'    => '0772223333',
                'password' => 'password12',
                'role'     => 'admin',
            ])
            ->assertRedirect();

        $kamal = User::where('email', 'kamal@example.com')->first();
        $this->assertNotNull($kamal);
        $this->assertTrue($kamal->is_admin);
        $this->assertSame(User::ADMIN_ROLE_ADMIN, $kamal->admin_role);
        $this->assertNotNull($kamal->email_verified_at);
    }

    public function test_main_admin_can_add_another_main_admin(): void
    {
        $owner = $this->mainAdmin();

        $this->actingAs($owner)
            ->post(route('admin.settings.admins.store'), [
                'name'     => 'Nisha',
                'email'    => 'nisha@example.com',
                'phone'    => '0774445555',
                'password' => 'password12',
                'role'     => 'main',
            ])
            ->assertRedirect();

        $this->assertTrue(User::where('email', 'nisha@example.com')->first()->isMainAdmin());
    }

    public function test_regular_admin_cannot_add_admins(): void
    {
        $staff = $this->staffAdmin();

        $this->actingAs($staff)
            ->post(route('admin.settings.admins.store'), [
                'name'     => 'Kamal',
                'email'    => 'kamal@example.com',
                'phone'    => '0772223333',
                'password' => 'password12',
                'role'     => 'admin',
            ])
            ->assertForbidden();
    }

    public function test_cannot_remove_last_main_admin_role(): void
    {
        $owner = $this->mainAdmin();

        $this->actingAs($owner)
            ->patch(route('admin.settings.admins.update', $owner), [
                'role' => 'admin',
            ])
            ->assertRedirect();

        $this->assertTrue($owner->fresh()->isMainAdmin());
    }

    public function test_regular_admin_can_open_admin_panel(): void
    {
        $staff = $this->staffAdmin();

        $this->actingAs($staff)
            ->get(route('admin.dashboard'))
            ->assertOk();

        $this->actingAs($staff)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertDontSee('Add admin');
    }

    public function test_promote_customer_to_admin(): void
    {
        $owner = $this->mainAdmin();
        $customer = User::factory()->create([
            'email' => 'shop@example.com',
            'phone' => '+94776665555',
        ]);

        $this->actingAs($owner)
            ->post(route('admin.settings.admins.store'), [
                'name'  => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'role'  => 'admin',
            ])
            ->assertRedirect();

        $this->assertTrue($customer->fresh()->is_admin);
        $this->assertSame(User::ADMIN_ROLE_ADMIN, $customer->fresh()->admin_role);
    }
}
