<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Complaint extends Model
{
    protected $fillable = [
        'reference', 'user_id', 'order_id', 'subject',
        'mobile', 'reason', 'status', 'admin_note',
        'handled_by', 'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public static function generateReference(): string
    {
        do {
            $ref = 'CMP-' . now()->format('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        } while (static::where('reference', $ref)->exists());

        return $ref;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'open'        => 'pill--pending',
            'in_progress' => 'pill--pending',
            'resolved'    => 'pill--success',
            'rejected'    => 'pill--failed',
            default       => 'pill--pending',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'open'        => 'Open',
            'in_progress' => 'In Progress',
            'resolved'    => 'Resolved',
            'rejected'    => 'Rejected',
            default       => ucfirst($this->status),
        };
    }
}
