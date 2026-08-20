<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Provider;
use App\Models\Service;
use Illuminate\Support\Facades\DB;

class ServiceImporter
{
    /**
     * Import services from a provider's fetchServices() catalog into the DB.
     * Creates missing categories, inserts missing services with default profit=0
     * so the admin can then customise each product's profit individually.
     *
     * Returns ['imported' => n, 'skipped' => n, 'categories' => n]
     */
    public function importFromProvider(Provider $provider): array
    {
        $client = ProviderFactory::make($provider);
        $items = $client->fetchServices();

        $imported = 0;
        $skipped  = 0;
        $catCount = 0;

        DB::transaction(function () use ($items, $provider, &$imported, &$skipped, &$catCount) {
            // Ensure default categories exist
            $defaults = [
                'mobile'       => ['name' => 'Mobile Reload',  'icon' => 'phone-menu', 'logo' => null],
                'broadband'    => ['name' => 'Broadband / ISP','icon' => 'router',     'logo' => null],
                'utility'      => ['name' => 'Utility Bills',  'icon' => 'bolt',       'logo' => null],
                'tv'           => ['name' => 'TV & Streaming', 'icon' => 'tv-card',    'logo' => null],
                'insurance'    => ['name' => 'Insurance',      'icon' => 'shield',     'logo' => null],
                'dth'          => ['name' => 'DTH Recharge',   'icon' => 'tv-card',    'logo' => null],
                'wallet-topup' => ['name' => 'Wallet / Driver Payments', 'icon' => 'bolt-nav', 'logo' => null],
            ];
            foreach ($defaults as $slug => $data) {
                $cat = Category::firstOrCreate(['slug' => $slug], [
                    'name'       => $data['name'],
                    'icon'       => $data['icon'],
                    'logo'       => $data['logo'],
                    'sort_order' => 0,
                    'is_active'  => true,
                ]);
                if ($cat->wasRecentlyCreated) $catCount++;
            }

            foreach ($items as $item) {
                $catSlug = $item['category_slug'] ?? 'mobile';
                $category = Category::where('slug', $catSlug)->first();

                $existing = Service::where('provider_id', $provider->id)
                    ->where('op_code', $item['op_code'])
                    ->first();

                if ($existing) {
                    // Update name/logo/category if we have newer info but DON'T overwrite admin profit settings
                    $existing->fill([
                        'name'       => $item['name'],
                        'short_name' => $item['name'],
                        'type'       => $item['type'] ?? $existing->type,
                        'category_id'=> $category?->id ?? $existing->category_id,
                        'logo'       => $item['logo'] ?? $existing->logo,
                    ]);
                    $existing->save();
                    $skipped++;
                    continue;
                }

                Service::create([
                    'provider_id' => $provider->id,
                    'category_id' => $category?->id,
                    'op_code'     => (string) $item['op_code'],
                    'name'        => $item['name'],
                    'short_name'  => $item['name'],
                    'logo'        => $item['logo'] ?? null,
                    'type'        => $item['type'] ?? 'prepaid',
                    'profit'      => 0,
                    'profit_type' => 'FLAT',
                    'is_active'   => true,
                ]);
                $imported++;
            }
        });

        return compact('imported', 'skipped', 'catCount');
    }
}
