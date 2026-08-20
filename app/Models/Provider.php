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

    /**
     * Fetch the live balance from the provider's API, cached for 60s
     * so repeated page loads within a minute don't hammer the upstream.
     * Returns null if the API call fails (we show an error badge).
     */
    public function fetchBalance(): ?float
    {
        if (! $this->is_active || ! $this->api_key || ! $this->base_url) {
            return null;
        }

        return Cache::remember("provider:{$this->id}:balance", 60, function () {
            try {
                $client = ProviderFactory::make($this);
                $bal = $client->balance();
                return $bal === null ? null : round($bal, 2);
            } catch (\Throwable $e) {
                Log::warning("Provider balance fetch failed for {$this->name}: " . $e->getMessage());
                return null;
            }
        });
    }
}
