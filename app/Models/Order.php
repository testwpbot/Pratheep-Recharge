<?php

namespace App\Models;

use App\Support\PreferredRoute;
use App\Support\ProviderErrors;
use App\Support\ServicePairs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
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

    public const STATUS_PENDING    = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SUCCESS    = 'success';
    public const STATUS_FAILED     = 'failed';
    public const STATUS_REFUNDED   = 'refunded';

    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    public function isFailedLike(): bool
    {
        return in_array($this->status, [self::STATUS_FAILED, self::STATUS_REFUNDED], true);
    }

    /** Simple English label for customer + admin pills. */
    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_REFUNDED   => 'Refunded',
            self::STATUS_SUCCESS    => 'Success',
            self::STATUS_PENDING    => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_FAILED     => 'Failed',
            default                 => ucfirst((string) $this->status),
        };
    }

    /**
     * Reference we send to the provider. After an admin switches Dialog ↔ Dialog API
     * this becomes the original ref plus -T1 / -T2 so status checks follow the new request.
     */
    public function providerClientRef(): string
    {
        $resp = $this->responseArray();
        $ref = trim((string) ($resp['_client_ref'] ?? ''));

        return $ref !== '' ? $ref : (string) $this->reference;
    }

    public function responseArray(): array
    {
        return is_array($this->provider_response) ? $this->provider_response : [];
    }

    public function sendOpCode(): string
    {
        $code = trim((string) ($this->responseArray()['_route_op_code'] ?? ''));

        return $code !== '' ? $code : (string) ($this->service?->op_code ?? '');
    }

    public function customerServiceName(): string
    {
        $stored = trim((string) ($this->responseArray()['_catalog_service_name'] ?? ''));
        if ($stored !== '') {
            return $stored;
        }

        $code = (string) ($this->service?->op_code ?? '');
        if ($code === PreferredRoute::DIALOG_API) {
            return 'Dialog Prepaid';
        }

        return (string) ($this->service?->name ?? 'Recharge');
    }

    public function isAwaitingProviderFunds(): bool
    {
        if (! in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING], true)) {
            return false;
        }

        if (in_array((string) $this->provider_status, [
            'awaiting_provider_funds', 'awaiting_funds', 'awaiting_provide',
        ], true)) {
            return true;
        }

        $resp = $this->responseArray();

        return ! empty($resp['_awaiting_funds'])
            || ProviderErrors::isFundsIssue($this->message, $resp);
    }

    public function hasRecordedHardFail(): bool
    {
        if ($this->isAwaitingProviderFunds()) {
            return false;
        }

        $resp = $this->responseArray();
        foreach ([$resp['status'] ?? null, $resp['STATUS'] ?? null, $this->provider_status] as $status) {
            if (in_array(strtolower((string) $status), ['failed', 'refund', 'cancelled', 'transfer_rejected', 'switch_fail'], true)) {
                return true;
            }
        }

        return str_contains(strtolower((string) $this->message), 'recharge failed');
    }

    /** Simple English for admin: will the clock send this to Dialog Prepaid? */
    public function clockNote(): ?string
    {
        if (! in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING], true)) {
            return null;
        }
        if ($this->sendOpCode() !== PreferredRoute::DIALOG_API) {
            return null;
        }
        if (! empty($this->responseArray()['_auto_fallback_at'])) {
            return 'The clock already sent this order through Dialog Prepaid.';
        }
        if (! ServicePairs::partnerFromOrder($this)) {
            return 'The clock cannot send this to Dialog Prepaid because that service is missing.';
        }
        if ($this->isAwaitingProviderFunds()) {
            return 'The clock will not switch this to Dialog Prepaid. The provider has no money, and Dialog Prepaid uses the same provider wallet. Add money at the provider, or click Send via Dialog Prepaid to try anyway.';
        }
        if ($this->hasRecordedHardFail()) {
            return 'The clock should send this through Dialog Prepaid on the next run. Dialog API already failed.';
        }

        $mins = (int) $this->routeStartedAt()->diffInMinutes(now());
        if ($mins >= PreferredRoute::AUTO_FALLBACK_MINUTES) {
            return 'The clock should send this through Dialog Prepaid on the next run. It has been waiting '.$mins.' minutes.';
        }

        $left = PreferredRoute::AUTO_FALLBACK_MINUTES - $mins;

        return 'The clock will send this through Dialog Prepaid in about '.$left.' minute(s) if Dialog API is still waiting.';
    }

    public function routeStartedAt(): Carbon
    {
        $raw = $this->responseArray()['_route_started_at'] ?? null;
        if ($raw) {
            try {
                return Carbon::parse($raw);
            } catch (\Throwable $e) {
                // fall through
            }
        }

        return $this->processed_at ?: $this->created_at ?: now();
    }

    /** Safe copy for customer pages. Never the raw provider text. */
    public function publicMessage(): string
    {
        if ($this->status === self::STATUS_SUCCESS) {
            $note = (float) $this->profit > 0
                ? ' You earned LKR '.number_format((float) $this->profit, 2).' cashback.'
                : '';

            return 'Recharge successful! Your '.$this->customerServiceName()
                .' of LKR '.number_format((float) $this->amount, 2)
                .' has been processed.'.$note;
        }

        if (in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING], true)) {
            return 'Your recharge request has been sent and is being processed. You can track its status on this page.';
        }

        if ($this->isRefunded()) {
            return 'This recharge did not go through. LKR '.number_format((float) $this->amount, 2)
                .' was put back in your wallet.';
        }

        return 'This recharge did not go through. Please try again or contact support.';
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
