<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderBalanceSnapshot extends Model
{
    protected $fillable = [
        'provider_id', 'balance', 'currency', 'user_wallet_total',
        'coverage_lkr', 'shortfall', 'shortfall_lkr', 'status',
        'error', 'alerted', 'meta',
    ];

    protected $casts = [
        'balance'           => 'decimal:2',
        'user_wallet_total' => 'decimal:2',
        'coverage_lkr'      => 'decimal:2',
        'shortfall'         => 'decimal:2',
        'shortfall_lkr'     => 'decimal:2',
        'alerted'           => 'boolean',
        'meta'              => 'array',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function isLow(): bool
    {
        return $this->status === 'low';
    }

    public function money(string $field, ?string $currency = null): string
    {
        $cur = $currency ?: (string) $this->currency;
        $val = (float) ($this->{$field} ?? 0);

        return $cur . ' ' . number_format($val, 2);
    }
}
