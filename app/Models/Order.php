<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'reference', 'user_id', 'service_id', 'provider_id',
        'account_number', 'notify_number', 'amount', 'profit',
        'provider_txn_id', 'status', 'provider_status', 'message',
        'provider_response', 'invoice_path', 'processed_at', 'completed_at',
    ];

    protected $casts = [
        'amount'            => 'decimal:2',
        'profit'            => 'decimal:2',
        'provider_response' => 'array',
        'processed_at'      => 'datetime',
        'completed_at'      => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function cashback(): HasOne
    {
        return $this->hasOne(Cashback::class);
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class)->latest();
    }

    /**
     * Reference we send to the provider. After an admin switches Dialog ↔ Dialog API
     * this becomes the original ref plus -T1 / -T2 so status checks follow the new request.
     */
    public function providerClientRef(): string
    {
        $resp = is_array($this->provider_response) ? $this->provider_response : [];
        $ref = trim((string) ($resp['_client_ref'] ?? ''));

        return $ref !== '' ? $ref : (string) $this->reference;
    }

    /** Generate a unique order reference */
    public static function generateReference(): string
    {
        do {
            $ref = 'HPR-' . now()->format('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        } while (static::where('reference', $ref)->exists());
        return $ref;
    }
}
