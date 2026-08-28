<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Plan extends Model
{
    protected $fillable = [
        'service_id', 'name', 'plan_code', 'amount', 'validity',
        'description', 'type', 'sort_order', 'is_active', 'meta',
    ];

    protected $casts = [
        'amount'     => 'decimal:2',
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
        'meta'       => 'array',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /** Cashback earned on this plan (delegates to service) */
    public function cashback(?User $user = null): float
    {
        return $this->service->calculateCashback((float) $this->amount, $user ?? auth()->user());
    }
}
