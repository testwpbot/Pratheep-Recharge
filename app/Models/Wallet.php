<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    // cashback_balance column is kept for backward compatibility with
    // older migrations but is no longer actively used — all earnings go
    // straight into `balance`.
    protected $fillable = ['user_id', 'balance', 'pin', 'low_balance_notified_at'];

    protected $casts = [
        'balance'                 => 'decimal:2',
        'low_balance_notified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }
}
