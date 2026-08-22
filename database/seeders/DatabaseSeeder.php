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

        $hrc = Provider::firstOrNew(['slug' => 'happy-recharge-center']);
        $missingKey = ! filled($hrc->api_key);
        $hrc->fill([
            'name'      => $hrc->name ?: 'Happy Recharge Center',
            'country'   => $hrc->country ?: 'IN',
            'api_class' => 'happy_recharge_center',
        ]);
        if (! filled($hrc->base_url)) {
            $hrc->base_url = env('HRC_BASE_URL', 'http://happyrechargecenter.com/RechargeApi');
        }
        if ($missingKey) {
            $hrc->api_key   = env('HRC_API_KEY', '334d7b447e9459fcbafe9441a');
            $hrc->is_active = true;
        }
        $hrc->save();

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

        // Default admin account
        User::firstOrCreate(['email' => 'admin@happypratheep.lk'], [
            'name'       => 'Admin',
            'phone'      => '+94770000000',
            'password'   => Hash::make('admin123'),
            'is_admin'   => true,
        ]);

        // Seed the services catalog (op_codes) from providers BEFORE plans,
        // because PlansSeeder attaches plans to existing Service rows.
        $this->call(ServicesSeeder::class);

        // Seed default real-world plans/packets for each imported service
        $this->call(PlansSeeder::class);
    }
}
