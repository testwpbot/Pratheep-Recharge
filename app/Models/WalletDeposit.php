<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletDeposit extends Model
{
    protected $fillable = [
        'user_id', 'amount', 'bank_name', 'depositor_name',
        'reference_number', 'slip_path', 'status',
        'admin_note', 'approved_by', 'approved_at', 'rejected_at',
    ];

    protected $casts = [
        'amount'      => 'decimal:2',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** Reference code like HPD-20260819-1234 */
    public function reference(): string
    {
        return 'HPD-' . $this->created_at->format('Ymd') . '-' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }
}
