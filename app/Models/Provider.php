<?php

namespace App\Models;

use App\Services\ProviderFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Provider extends Model
{
    protected $fillable = ['name', 'slug', 'country', 'api_class', 'base_url', 'api_key', 'api_secret', 'is_active', 'meta'];

    protected $casts = [
        'is_active' => 'boolean',
        'meta'      => 'array',
    ];

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function isHappyRechargeCenter(): bool
    {
        $class = (string) $this->api_class;

        return $this->slug === 'happy-recharge-center'
            || $class === 'happy_recharge_center'
            || str_contains($class, 'HappyRechargeCenter');
    }

    public function isTopupMart(): bool
    {
        $class = (string) $this->api_class;

        return $this->slug === 'topup-mart'
            || $class === 'topup_mart'
            || str_contains($class, 'TopupMart');
    }

    public function hasCredentials(): bool
    {
        return filled($this->base_url) && filled($this->api_key);
    }

    /**
     * Live wallet balance + error string (cached 60s).
     * HRC often fails with "IP ADDRESS NOT CORRECT" until they whitelist us.
     *
     * @return array{balance:?float,error:?string}
     */
    public function fetchBalanceInfo(): array
    {
        if (! $this->is_active) {
            return ['balance' => null, 'error' => 'Provider is disabled'];
        }
        if (! $this->hasCredentials()) {
            return ['balance' => null, 'error' => 'No API key'];
        }

        return Cache::remember("provider:{$this->id}:balance_info", 60, function () {
            try {
                $client = ProviderFactory::make($this);
                $bal = $client->balance();
                $err = method_exists($client, 'lastError') ? $client->lastError() : null;
                return [
                    'balance' => $bal === null ? null : round($bal, 2),
                    'error'   => $bal === null ? ($err ?: 'Could not reach provider') : null,
                ];
            } catch (\Throwable $e) {
                Log::warning("Provider balance fetch failed for {$this->name}: " . $e->getMessage());
                return ['balance' => null, 'error' => $e->getMessage()];
            }
        });
    }

    public function fetchBalance(): ?float
    {
        return $this->fetchBalanceInfo()['balance'] ?? null;
    }

    /** Short label for the admin table when balance is unavailable. */
    public static function balanceErrorLabel(?string $error): string
    {
        $e = (string) $error;
        if ($e === '') return 'Unavailable';
        if (stripos($e, 'IP') !== false) return 'IP not whitelisted';
        if (stripos($e, 'LOGIN') !== false || stripos($e, 'TOKEN') !== false) return 'Auth failed';
        if (stripos($e, 'No API') !== false) return 'No API key';
        return 'Unavailable';
    }
}
