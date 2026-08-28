<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailOtp extends Model
{
    public const PURPOSE_SIGNUP = 'signup';
    public const PURPOSE_LOGIN  = 'login';

    public const TTL_MINUTES      = 10;
    public const MAX_ATTEMPTS     = 5;
    public const RESEND_SECONDS   = 60;

    protected $fillable = [
        'user_id', 'email', 'code_hash', 'purpose',
        'ip_address', 'user_agent', 'attempts',
        'expires_at', 'consumed_at',
    ];

    protected $casts = [
        'expires_at'  => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at === null || $this->expires_at->isPast();
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function isLocked(): bool
    {
        return (int) $this->attempts >= self::MAX_ATTEMPTS;
    }
}
