<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Runs Laravel's minute clock (pending orders, provider money, low-wallet emails).
 * Used by public/cron.php and GET /cron.php so DirectAdmin wget/curl samples work.
 */
class WebCron
{
    public static function run(): array
    {
        if (! static::allowed()) {
            return ['ok' => false, 'status' => 404, 'body' => 'Not found.'];
        }

        if (! static::rateOk()) {
            return ['ok' => false, 'status' => 429, 'body' => 'Slow down.'];
        }

        try {
            SchemaFix::widenOrderProviderStatus();
            Artisan::call('schedule:run');
            $out = trim((string) Artisan::output());
            static::rememberRun($out);

            return [
                'ok'     => true,
                'status' => 200,
                'body'   => $out !== '' ? $out : 'ok',
            ];
        } catch (Throwable $e) {
            report($e);

            return ['ok' => false, 'status' => 500, 'body' => 'Cron failed.'];
        }
    }

    public static function allowed(): bool
    {
        if (in_array(PHP_SAPI, ['cli', 'phpdbg'], true)) {
            return true;
        }

        $expected = (string) config('app.cron_key', '');
        if ($expected !== '') {
            $given = (string) request()->query('key', '');

            return $given !== '' && hash_equals($expected, $given);
        }

        // No key in .env — allow so the DirectAdmin wget/curl sample works.
        return true;
    }

    public static function isCli(): bool
    {
        return in_array(PHP_SAPI, ['cli', 'phpdbg'], true);
    }

    private static function rememberRun(string $output): void
    {
        try {
            \App\Models\Setting::set(
                'cron',
                'last_run_at',
                now()->timezone('Asia/Colombo')->toDateTimeString()
            );
            $note = trim((string) \App\Models\Setting::get('cron', 'last_sync_note', ''));
            if ($output !== '' && $note === '') {
                \App\Models\Setting::set('cron', 'last_sync_note', mb_substr($output, 0, 2000));
            }
        } catch (Throwable) {
            // Settings table may not be ready.
        }
    }

    private static function rateOk(): bool
    {
        if (static::isCli()) {
            return true;
        }

        try {
            $key = 'hpr-web-cron-hits';
            if (! Cache::has($key)) {
                Cache::put($key, 1, 60);

                return true;
            }

            return Cache::increment($key) <= 8;
        } catch (Throwable) {
            return true;
        }
    }
}
