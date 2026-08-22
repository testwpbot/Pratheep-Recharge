<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

    public function test_switching_custom_bank_to_catalog_fills_name_and_logo(): void
    {
        $acc = BankAccount::create([
            'bank_slug' => 'custom', 'bank_name' => 'My Bank',
            'account_name' => 'HPR', 'account_no' => '999', 'is_active' => true,
        ]);

        $this->actingAs($this->owner())
            ->patch(route('admin.settings.banks.update', $acc), [
                'bank_slug'    => 'sampath-bank',
                'account_name' => 'HPR',
                'account_no'   => '999',
            ])
            ->assertRedirect();

        $acc->refresh();
        $this->assertSame('sampath-bank', $acc->bank_slug);
        $this->assertSame('Sampath Bank', $acc->bank_name);
        $this->assertStringContainsString('sampath-bank', $acc->logoUrl());
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

    public function test_home_does_not_crash_without_seo_rows(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_seo_form_prefills_saved_values(): void
    {
        Setting::set('seo', 'meta_title', 'Saved Title Here');
        Setting::set('seo', 'og_title', 'Saved OG Title');

        $this->actingAs($this->owner())
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Saved Title Here', false)
            ->assertSee('Saved OG Title', false);
    }

    public function test_can_upload_favicon(): void
    {
        $file = UploadedFile::fake()->image('fav.png', 32, 32);

        $this->actingAs($this->owner())
            ->post(route('admin.settings.seo'), [
                'meta_title' => 'With favicon',
                'robots'     => 'index',
                'favicon'    => $file,
            ])
            ->assertRedirect();

        $path = Setting::get('seo', 'favicon_path');
        $this->assertNotEmpty($path);
        $this->assertFileExists(public_path($path));
        @unlink(public_path($path));
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
