<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cashback extends Model
{
    protected $fillable = ['user_id', 'order_id', 'amount', 'status', 'credited_at'];

    protected $casts = [
        'amount'      => 'decimal:2',
        'credited_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
