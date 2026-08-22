<?php

namespace App\Services;

use App\Mail\ProviderFundsLow;
use App\Models\Provider;
use App\Models\ProviderBalanceSnapshot;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Compare API-provider float vs customer wallet liability.
 *
 * Rule: a same-currency provider (Topup Mart / LKR) must hold at least as
 * much as every customer wallet combined. If it doesn't, admin must top the
 * API up by the shortfall. Foreign-currency providers (HRC / INR) are
 * checked against a minimum INR float — they are DTH-only and not 1:1 with
 * LKR wallets unless an FX rate is saved.
 */
class FundHealthService
{
    public function customerWalletTotal(): float
    {
        $sum = Wallet::query()
            ->whereHas('user', fn ($q) => $q->where('is_admin', false))
            ->sum('balance');

        return round((float) $sum, 2);
    }

    public function customerWalletCount(): int
    {
        return (int) Wallet::query()
            ->whereHas('user', fn ($q) => $q->where('is_admin', false))
            ->where('balance', '>', 0)
            ->count();
    }

    public function settings(): array
    {
        $s = Setting::forGroup('funds');

        return [
            'alerts_enabled'  => ($s['alerts_enabled'] ?? '1') !== '0',
            'alert_email'     => trim((string) ($s['alert_email'] ?? '')),
            'cooldown_hours'  => max(1, min(48, (int) ($s['cooldown_hours'] ?? 6))),
            'min_inr'         => max(0, (float) ($s['min_inr'] ?? 500)),
            'inr_to_lkr'      => max(0, (float) ($s['inr_to_lkr'] ?? 0)),
        ];
    }

    /**
     * Live (or cached) picture of every provider vs customer wallets.
     *
     * @return array{
     *   user_total: float,
     *   user_count: int,
     *   combined_lkr: ?float,
     *   combined_shortfall: float,
     *   overall: string,
     *   pay: list<array{provider:string,provider_id:int,amount:float,currency:string,reason:string}>,
     *   providers: list<array<string,mixed>>
     * }
     */
    public function overview(bool $fresh = false): array
    {
        $userTotal = $this->customerWalletTotal();
        $userCount = $this->customerWalletCount();
        $cfg = $this->settings();

        $rows = [];
        $lkrFloat = 0.0;
        $lkrKnown = false;

        foreach (Provider::query()->orderBy('id')->get() as $provider) {
            $row = $this->inspectProvider($provider, $userTotal, $cfg, $fresh);
            $rows[] = $row;
            if ($row['is_lkr'] && $row['balance'] !== null) {
                $lkrFloat += (float) $row['balance'];
                $lkrKnown = true;
            }
        }

        $combinedLkr = $lkrKnown ? round($lkrFloat, 2) : null;
        $combinedShort = $lkrKnown ? max(0, round($userTotal - $lkrFloat, 2)) : 0.0;

        $pay = [];
        foreach ($rows as $row) {
            if ($row['status'] !== 'low' || $row['shortfall'] <= 0.009) {
                continue;
            }
            $pay[] = [
                'provider'    => $row['name'],
                'provider_id' => $row['id'],
                'amount'      => $row['shortfall'],
                'currency'    => $row['pay_currency'],
                'reason'      => $row['recommendation'],
            ];
        }

        $anyLow = collect($rows)->contains(fn ($r) => $r['status'] === 'low');
        $anyKnown = collect($rows)->contains(fn ($r) => $r['status'] !== 'unknown');
        $overall = $anyLow ? 'low' : ($anyKnown ? 'healthy' : 'unknown');

        return [
            'user_total'         => $userTotal,
            'user_count'         => $userCount,
            'combined_lkr'       => $combinedLkr,
            'combined_shortfall' => $combinedShort,
            'overall'            => $overall,
            'pay'                => $pay,
            'providers'          => $rows,
            'settings'           => $cfg,
        ];
    }

    /**
     * Inspect, persist a snapshot when something moved, and email if low.
     *
     * @return array<string,mixed>
     */
    public function check(bool $fresh = true, bool $persist = true, bool $alert = true): array
    {
        $health = $this->overview($fresh);

        if ($persist) {
            foreach ($health['providers'] as $i => $row) {
                $snap = $this->persistIfNeeded($row, $health['user_total']);
                $health['providers'][$i]['snapshot_id'] = $snap?->id;
            }
        }

        if ($alert) {
            $this->maybeAlert($health);
        }

        Cache::put('funds:overall_status', $health['overall'], 600);

        return $health;
    }

    /**
     * @param  array<string,mixed>  $cfg
     * @return array<string,mixed>
     */
    public function inspectProvider(Provider $provider, float $userTotal, array $cfg, bool $fresh = false): array
    {
        $currency = $provider->currency();
        $isLkr = $currency === 'LKR';

        $info = $provider->fetchBalanceInfo($fresh);
        $balance = $info['balance'];
        $error = $info['error'];

        $shortfall = 0.0;
        $shortfallLkr = 0.0;
        $coverageLkr = null;
        $coveragePct = null;
        $status = 'unknown';
        $payCurrency = $currency;
        $recommendation = $provider->name . ' balance is unavailable — cannot compare to customer wallets.';

        if ($balance !== null) {
            if ($isLkr) {
                $coverageLkr = $balance;
                $shortfall = max(0, round($userTotal - $balance, 2));
                $shortfallLkr = $shortfall;
                $coveragePct = $userTotal <= 0 ? 100.0 : min(100.0, round(($balance / $userTotal) * 100, 1));
                $status = $shortfall > 0.009 ? 'low' : 'healthy';
                $recommendation = $status === 'low'
                    ? "Pay {$currency} " . number_format($shortfall, 2) . " to {$provider->name} so API funds cover every customer wallet (LKR " . number_format($userTotal, 2) . ")."
                    : "{$provider->name} covers all customer wallets.";
            } else {
                $min = (float) $cfg['min_inr'];
                $rate = (float) $cfg['inr_to_lkr'];
                if ($rate > 0) {
                    $coverageLkr = round($balance * $rate, 2);
                }
                $shortVsMin = max(0, round($min - $balance, 2));
                $coveragePct = $min <= 0 ? 100.0 : min(100.0, round(($balance / $min) * 100, 1));
                if ($shortVsMin > 0.009) {
                    $status = 'low';
                    $shortfall = $shortVsMin;
                    $shortfallLkr = $coverageLkr !== null ? max(0, round(($min * $rate) - $coverageLkr, 2)) : 0.0;
                    $recommendation = "Pay {$currency} " . number_format($shortfall, 2) . " to {$provider->name} (keep at least {$currency} " . number_format($min, 2) . " for DTH).";
                } else {
                    $status = 'healthy';
                    $recommendation = "{$provider->name} DTH float is above the INR " . number_format($min, 2) . " minimum.";
                }
            }
        }

        return [
            'id'             => $provider->id,
            'name'           => $provider->name,
            'slug'           => $provider->slug,
            'country'        => $provider->country,
            'currency'       => $currency,
            'is_lkr'         => $isLkr,
            'is_active'      => (bool) $provider->is_active,
            'balance'        => $balance,
            'error'          => $error,
            'error_label'    => $balance === null ? Provider::balanceErrorLabel($error) : null,
            'user_total'     => $userTotal,
            'shortfall'      => $shortfall,
            'shortfall_lkr'  => $shortfallLkr,
            'coverage_lkr'   => $coverageLkr,
            'coverage_pct'   => $coveragePct,
            'status'         => $status,
            'pay_currency'   => $payCurrency,
            'recommendation' => $recommendation,
        ];
    }

    /**
     * @param  array<string,mixed>  $row
     */
    protected function persistIfNeeded(array $row, float $userTotal): ?ProviderBalanceSnapshot
    {
        $last = ProviderBalanceSnapshot::query()
            ->where('provider_id', $row['id'])
            ->latest('id')
            ->first();

        $changed = ! $last
            || (string) $last->balance !== (string) ($row['balance'] ?? '')
            || $last->status !== $row['status']
            || abs((float) $last->user_wallet_total - $userTotal) > 0.009
            || $last->created_at->lt(now()->subMinutes(10));

        if (! $changed) {
            return $last;
        }

        return ProviderBalanceSnapshot::create([
            'provider_id'       => $row['id'],
            'balance'           => $row['balance'],
            'currency'          => $row['currency'],
            'user_wallet_total' => $userTotal,
            'coverage_lkr'      => $row['coverage_lkr'],
            'shortfall'         => $row['shortfall'],
            'shortfall_lkr'     => $row['shortfall_lkr'],
            'status'            => $row['status'],
            'error'             => $row['error'] ? mb_substr((string) $row['error'], 0, 255) : null,
            'alerted'           => false,
            'meta'              => [
                'recommendation' => $row['recommendation'],
                'coverage_pct'   => $row['coverage_pct'],
            ],
        ]);
    }

    /**
     * @param  array<string,mixed>  $health
     */
    public function maybeAlert(array $health): bool
    {
        $cfg = $health['settings'] ?? $this->settings();
        if (! ($cfg['alerts_enabled'] ?? true)) {
            return false;
        }

        $lows = array_values(array_filter(
            $health['providers'] ?? [],
            fn ($r) => ($r['status'] ?? '') === 'low' && (float) ($r['shortfall'] ?? 0) > 0.009
        ));

        if ($lows === []) {
            Setting::set('funds', 'alert_fp', '');
            Setting::set('funds', 'alert_status', 'healthy');
            return false;
        }

        $fp = collect($lows)
            ->map(fn ($r) => $r['id'] . ':' . number_format((float) $r['shortfall'], 2, '.', ''))
            ->implode('|');

        $lastFp = (string) Setting::get('funds', 'alert_fp', '');
        $lastAt = Setting::get('funds', 'alert_at');
        $cooldownMin = ((int) ($cfg['cooldown_hours'] ?? 6)) * 60;

        $expired = ! $lastAt || now()->gte(Carbon::parse($lastAt)->addMinutes($cooldownMin));
        if ($fp === $lastFp && ! $expired) {
            return false;
        }

        $emails = $this->adminEmails($cfg['alert_email'] ?? '');
        if ($emails === []) {
            Log::warning('Provider funds are low but no admin email is configured.');
            return false;
        }

        try {
            Mail::to($emails)->send(new ProviderFundsLow($health));
        } catch (\Throwable $e) {
            Log::warning('Provider funds-low email failed: ' . $e->getMessage());
            return false;
        }

        Setting::set('funds', 'alert_fp', $fp);
        Setting::set('funds', 'alert_at', now()->toIso8601String());
        Setting::set('funds', 'alert_status', 'low');

        if (! empty($health['providers'])) {
            $ids = array_filter(array_column($health['providers'], 'snapshot_id'));
            if ($ids) {
                ProviderBalanceSnapshot::whereIn('id', $ids)
                    ->where('status', 'low')
                    ->update(['alerted' => true]);
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    public function adminEmails(string $extra = ''): array
    {
        $emails = [];
        $extra = trim($extra);
        if ($extra !== '' && filter_var($extra, FILTER_VALIDATE_EMAIL)) {
            $emails[] = $extra;
        }

        $support = trim((string) Setting::get('general', 'support_email', ''));
        if ($support !== '' && filter_var($support, FILTER_VALIDATE_EMAIL)) {
            $emails[] = $support;
        }

        foreach (User::where('is_admin', true)->pluck('email') as $email) {
            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emails[] = $email;
            }
        }

        return array_values(array_unique($emails));
    }
}
