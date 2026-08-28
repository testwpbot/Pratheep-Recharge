<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Default providers
        Provider::firstOrCreate(['slug' => 'topup-mart'], [
            'name'       => 'Topup Mart',
            'country'    => 'LK',
            'api_class'  => 'topup_mart',
            'base_url'   => 'https://topupmart.online/api/v2',
            'api_key'    => '',
            'is_active'  => true,
        ]);

        Provider::firstOrCreate(['slug' => 'tmobiling'], [
            'name'      => 'TMobiling',
            'country'   => 'LK',
            'api_class' => 'tmobiling',
            'base_url'  => env('TMOBILING_BASE_URL', 'https://www.tmobiling.lk/livenew/apis/api_request'),
            'api_key'   => env('TMOBILING_API_KEY', ''),
            'is_active' => true,
        ]);

        // Retired. Keep the row so old DTH orders can still be checked.
        $hrc = Provider::where('slug', 'happy-recharge-center')->first();
        if ($hrc) {
            $hrc->is_active = false;
            $hrc->save();
        }

        // Categories
        foreach ([
            ['slug' => 'mobile',        'name' => 'Mobile Reload',         'icon' => 'phone-menu', 'sort' => 1],
            ['slug' => 'broadband',     'name' => 'Broadband / ISP',       'icon' => 'router',     'sort' => 2],
            ['slug' => 'utility',       'name' => 'Utility Bills',         'icon' => 'bolt',       'sort' => 3],
            ['slug' => 'tv',            'name' => 'TV & Streaming',        'icon' => 'tv-card',    'sort' => 4],
            ['slug' => 'insurance',     'name' => 'Insurance',             'icon' => 'shield',     'sort' => 5],
            ['slug' => 'dth',           'name' => 'DTH Recharge',          'icon' => 'tv-card',    'sort' => 6],
            ['slug' => 'wallet-topup',  'name' => 'Wallet / Driver Topup', 'icon' => 'bolt-nav',   'sort' => 7],
        ] as $c) {
            Category::firstOrCreate(['slug' => $c['slug']], [
                'name'       => $c['name'],
                'icon'       => $c['icon'],
                'sort_order' => $c['sort'],
                'is_active'  => true,
            ]);
        }

        // Default owner account
        $owner = User::firstOrCreate(['email' => 'admin@happypratheep.lk'], [
            'name'              => 'Admin',
            'phone'             => '+94770000000',
            'password'          => Hash::make('admin123'),
            'is_admin'          => true,
            'admin_role'        => User::ADMIN_ROLE_MAIN,
            'email_verified_at' => now(),
        ]);
        if ($owner->is_admin && $owner->admin_role !== User::ADMIN_ROLE_MAIN) {
            $owner->forceFill(['admin_role' => User::ADMIN_ROLE_MAIN])->save();
        }

        // Seed the services catalog (op_codes) from providers BEFORE plans,
        // because PlansSeeder attaches plans to existing Service rows.
        $this->call(ServicesSeeder::class);

        // Seed default real-world plans/packets for each imported service
        $this->call(PlansSeeder::class);
    }
}
