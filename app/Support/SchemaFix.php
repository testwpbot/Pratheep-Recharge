<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * DirectAdmin has no SSH, so we cannot rely on `php artisan migrate`.
 * The clock widens orders.provider_status once if it is still VARCHAR(16).
 */
class SchemaFix
{
    public static function widenOrderProviderStatus(): void
    {
        try {
            if (DB::getDriverName() !== 'mysql') {
                return;
            }

            if (Cache::get('hpr-schema-provider-status-64')) {
                return;
            }

            $col = DB::selectOne("SHOW COLUMNS FROM orders LIKE 'provider_status'");
            $type = strtolower((string) ($col->Type ?? $col->type ?? ''));
            if (preg_match('/varchar\((\d+)\)/', $type, $m) && (int) $m[1] < 64) {
                DB::statement('ALTER TABLE orders MODIFY provider_status VARCHAR(64) NULL');
                Log::info('Widened orders.provider_status to VARCHAR(64)');
            }

            Cache::put('hpr-schema-provider-status-64', 1, 86400 * 30);
        } catch (Throwable $e) {
            Log::warning('Could not widen orders.provider_status: '.$e->getMessage());
        }
    }
}
