<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsBanksSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function owner(): User
    {
        $u = User::factory()->create(['is_admin' => true]);
        $u->forceFill(['admin_role' => User::ADMIN_ROLE_MAIN])->save();

        return $u;
    }

    public function test_can_add_bank_account(): void
    {
        $this->actingAs($this->owner())
            ->post(route('admin.settings.banks.store'), [
                'bank_slug'    => 'sampath-bank',
                'bank_name'    => 'Sampath Bank',
                'account_name' => 'Happy Pratheep',
                'account_no'   => '1234567890',
                'branch'       => 'Kandy',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('bank_accounts', [
            'bank_slug'  => 'sampath-bank',
            'account_no' => '1234567890',
        ]);
    }

    public function test_can_save_seo(): void
    {
        $this->actingAs($this->owner())
            ->post(route('admin.settings.seo'), [
                'meta_title'       => 'Happy Pratheep Reload',
                'meta_description' => 'Fast reloads in Sri Lanka',
                'robots'           => 'index',
            ])
            ->assertRedirect();

        $this->get('/')->assertSee('Happy Pratheep Reload', false);
    }

    public function test_wallet_shows_all_active_banks(): void
    {
        BankAccount::create([
            'bank_slug' => 'bank-of-ceylon', 'bank_name' => 'Bank of Ceylon',
            'account_name' => 'HPR', 'account_no' => '111', 'is_active' => true,
        ]);
        BankAccount::create([
            'bank_slug' => 'sampath-bank', 'bank_name' => 'Sampath Bank',
            'account_name' => 'HPR', 'account_no' => '222', 'is_active' => true,
        ]);

        $user = User::factory()->create();
        $this->actingAs($user)->get(route('wallet'))
            ->assertOk()
            ->assertSee('Bank of Ceylon')
            ->assertSee('Sampath Bank');
    }
}
