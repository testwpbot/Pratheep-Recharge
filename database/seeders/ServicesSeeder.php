<?php

namespace Database\Seeders;

use App\Models\Provider;
use App\Services\ServiceImporter;
use Illuminate\Database\Seeder;

/**
 * Seed the services (op_code) catalog from each active provider.
 *
 * TopupMart's fetchServices() returns a hardcoded list of Sri Lankan / Indian
 * operators (no HTTP call), so running the importer during seed is safe and
 * offline-friendly. This ensures PlansSeeder (which looks up services by
 * op_code) has rows to attach plans to, and means `migrate:fresh --seed`
 * produces a fully-working site without needing to click "Import Services"
 * in the admin panel first.
 */
class ServicesSeeder extends Seeder
{
    public function run(): void
    {
        $importer = new ServiceImporter();

        $topup = Provider::where('slug', 'topup-mart')->first();
        if ($topup) {
            $res = $importer->importFromProvider($topup);
            $this->command?->info(sprintf(
                '[ServicesSeeder] Topup Mart: %d imported, %d updated.',
                $res['imported'],
                $res['skipped']
            ));
        }

        $hrc = Provider::where('slug', 'happy-recharge-center')->first();
        if ($hrc) {
            $res = $importer->importFromProvider($hrc);
            $this->command?->info(sprintf(
                '[ServicesSeeder] Happy Recharge Center (DTH only): %d imported, %d updated.',
                $res['imported'],
                $res['skipped']
            ));
        }
    }
}
