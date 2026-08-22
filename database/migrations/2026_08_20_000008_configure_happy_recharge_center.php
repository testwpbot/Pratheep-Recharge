<?php

use App\Models\Provider;
use App\Services\ServiceImporter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Existing localhost DBs that already ran seeders will have an empty-key
 * Happy Recharge Center row (firstOrCreate does not update). Pulling this
 * migration fills credentials, activates HRC, and imports DTH-only services.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('providers')) {
            return;
        }

        $hrc = Provider::where('slug', 'happy-recharge-center')->first();
        if (! $hrc) {
            return;
        }

        $missingKey = ! filled($hrc->api_key);
        $hrc->name      = $hrc->name ?: 'Happy Recharge Center';
        $hrc->country   = $hrc->country ?: 'IN';
        $hrc->api_class = 'happy_recharge_center';

        if (! filled($hrc->base_url)) {
            $hrc->base_url = (string) config(
                'services.happy_recharge_center.base_url',
                'http://happyrechargecenter.com/RechargeApi'
            );
        }

        if ($missingKey) {
            $hrc->api_key = (string) config(
                'services.happy_recharge_center.api_key',
                '334d7b447e9459fcbafe9441a'
            );
            $hrc->is_active = true;
        }

        $hrc->save();

        try {
            app(ServiceImporter::class)->importFromProvider($hrc);
        } catch (\Throwable $e) {
            // Catalog import is best-effort during migrate (e.g. incomplete schema
            // in a partial test). Admin can still click "Import Services".
        }
    }

    public function down(): void
    {
        // Credentials stay — rolling this back should not wipe API keys.
    }
};
