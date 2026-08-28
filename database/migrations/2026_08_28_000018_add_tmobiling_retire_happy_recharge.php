<?php

use App\Models\Provider;
use App\Models\Service;
use App\Services\Providers\TMobiling;
use Illuminate\Database\Migrations\Migration;

/**
 * Add TMobiling as a Sri Lanka provider and turn Happy Recharge Center off.
 * Old HRC orders stay in the database so status checks still work.
 *
 * phpMyAdmin (if you cannot run artisan migrate):
 *
 * INSERT INTO providers (name, slug, country, api_class, base_url, api_key, is_active, created_at, updated_at)
 * SELECT 'TMobiling', 'tmobiling', 'LK', 'tmobiling',
 *        'https://www.tmobiling.lk/livenew/apis/api_request', NULL, 1, NOW(), NOW()
 * FROM DUAL
 * WHERE NOT EXISTS (SELECT 1 FROM providers WHERE slug = 'tmobiling');
 *
 * UPDATE providers SET is_active = 0 WHERE slug = 'happy-recharge-center';
 * UPDATE services s JOIN providers p ON p.id = s.provider_id
 *    SET s.is_active = 0
 *  WHERE p.slug = 'happy-recharge-center';
 */
return new class extends Migration
{
    public function up(): void
    {
        $tmobi = Provider::firstOrNew(['slug' => 'tmobiling']);
        $tmobi->fill([
            'name' => $tmobi->name ?: 'TMobiling',
            'country' => $tmobi->country ?: 'LK',
            'api_class' => 'tmobiling',
        ]);
        if (! filled($tmobi->base_url)) {
            $tmobi->base_url = TMobiling::DEFAULT_BASE_URL;
        }
        if (! $tmobi->exists) {
            $tmobi->is_active = true;
        }
        $tmobi->save();

        Provider::query()
            ->where('slug', 'happy-recharge-center')
            ->orWhere('api_class', 'happy_recharge_center')
            ->update(['is_active' => false]);

        $hrcIds = Provider::query()
            ->where(function ($q) {
                $q->where('slug', 'happy-recharge-center')
                    ->orWhere('api_class', 'happy_recharge_center');
            })
            ->pluck('id');
        if ($hrcIds->isNotEmpty()) {
            Service::whereIn('provider_id', $hrcIds)->update(['is_active' => false]);
        }
    }

    public function down(): void
    {
        Provider::query()
            ->where('slug', 'happy-recharge-center')
            ->update(['is_active' => true]);
    }
};
