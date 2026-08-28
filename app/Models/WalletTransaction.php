<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WalletTransaction extends Model
{
    protected $fillable = [
        'wallet_id', 'type', 'amount', 'balance_before', 'balance_after',
        'description', 'transactable_id', 'transactable_type',
    ];

    protected $casts = [
        'amount'         => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after'  => 'decimal:2',
    ];

    const TYPE_DEPOSIT  = 'deposit';
    const TYPE_CASHBACK = 'cashback';
    const TYPE_DEBIT    = 'debit';
    const TYPE_REFUND   = 'refund';
    const TYPE_ADJUST   = 'adjustment';

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function transactable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Signed delta (positive = credit, negative = debit).
     * Admin adjustments can go either way — use before/after when present.
     */
    public function signedAmount(): float
    {
        $a = abs((float) $this->amount);
        if ($this->balance_before !== null && $this->balance_after !== null) {
            return ((float) $this->balance_after) >= ((float) $this->balance_before) ? $a : -$a;
        }

        return in_array($this->type, [self::TYPE_DEBIT], true) ? -$a : $a;
    }

    public function isCredit(): bool
    {
        return $this->signedAmount() >= 0;
    }

    /** Short label for wallet history on every customer + admin page. */
    public function typeLabel(): string
    {
        if ($this->type === self::TYPE_ADJUST) {
            return $this->isCredit() ? 'Manual fund add' : 'Manual funds remove';
        }

        return match ($this->type) {
            self::TYPE_DEBIT    => 'Recharge / Order',
            self::TYPE_DEPOSIT  => 'Deposit',
            self::TYPE_CASHBACK => 'Cashback',
            self::TYPE_REFUND   => 'Refund',
            default             => ucfirst((string) $this->type),
        };
    }

    public function typePillClass(): string
    {
        if ($this->type === self::TYPE_DEBIT) {
            return 'failed';
        }
        if ($this->type === self::TYPE_REFUND) {
            return 'refunded';
        }
        if ($this->type === self::TYPE_ADJUST) {
            return $this->isCredit() ? 'success' : 'failed';
        }

        return 'success';
    }
}
