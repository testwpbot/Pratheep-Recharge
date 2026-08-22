<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpecialPrice extends Model
{
    protected $fillable = ['user_id', 'service_id', 'profit', 'profit_type'];

    protected $casts = [
        'profit' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function label(): string
    {
        return $this->profit_type === 'PCT'
            ? number_format((float) $this->profit, 2) . '%'
            : 'LKR ' . number_format((float) $this->profit, 2);
    }
}
