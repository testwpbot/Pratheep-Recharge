<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Alert extends Model
{
    public const THEME_NAVY = 'navy';
    public const THEME_GOLD = 'gold';

    public const AUDIENCE_ALL = 'all';
    public const AUDIENCE_CUSTOMERS = 'customers';
    public const AUDIENCE_RETAILERS = 'retailers';

    protected $fillable = [
        'title', 'eyebrow', 'heading', 'body', 'image_path',
        'button_label', 'button_url', 'button2_label', 'button2_url',
        'theme', 'audience', 'is_active', 'is_dismissible',
        'starts_at', 'ends_at', 'sort_order', 'created_by',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'is_dismissible' => 'boolean',
        'starts_at'      => 'datetime',
        'ends_at'        => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dismissals(): HasMany
    {
        return $this->hasMany(AlertDismissal::class);
    }

    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return asset(ltrim($this->image_path, '/'));
    }

    public function themeLabel(): string
    {
        return $this->theme === self::THEME_GOLD ? 'Gold' : 'Navy';
    }

    public function audienceLabel(): string
    {
        return match ($this->audience) {
            self::AUDIENCE_CUSTOMERS => 'Customers',
            self::AUDIENCE_RETAILERS => 'Retailers',
            default => 'Everyone',
        };
    }

    public function statusLabel(): string
    {
        if (! $this->is_active) {
            return 'Off';
        }
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'Scheduled';
        }
        if ($this->ends_at && $this->ends_at->isPast()) {
            return 'Ended';
        }

        return 'On';
    }

    public function isLive(): bool
    {
        if (! $this->is_active) {
            return false;
        }
        $now = now();
        if ($this->starts_at && $this->starts_at->gt($now)) {
            return false;
        }
        if ($this->ends_at && $this->ends_at->lt($now)) {
            return false;
        }

        return true;
    }

    public function visibleTo(?User $user): bool
    {
        if (! $user || ! $this->isLive()) {
            return false;
        }

        return match ($this->audience) {
            self::AUDIENCE_CUSTOMERS => ! $user->isAdmin(),
            self::AUDIENCE_RETAILERS => (bool) $user->is_retailer && ! $user->isAdmin(),
            default => true,
        };
    }

    public function scopeLive(Builder $query): Builder
    {
        $now = Carbon::now();

        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });
    }

    /** @return \Illuminate\Support\Collection<int, self> */
    public static function forDashboard(User $user, int $limit = 5)
    {
        $dismissed = AlertDismissal::query()
            ->where('user_id', $user->id)
            ->pluck('alert_id');

        return static::query()
            ->live()
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (self $alert) => $alert->visibleTo($user))
            ->reject(fn (self $alert) => $alert->is_dismissible && $dismissed->contains($alert->id))
            ->take($limit)
            ->values();
    }

    public function dismissFor(User $user): void
    {
        if (! $this->is_dismissible) {
            return;
        }

        AlertDismissal::query()->firstOrCreate(
            ['alert_id' => $this->id, 'user_id' => $user->id],
            ['dismissed_at' => now()]
        );
    }

    public static function safeUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }
        if (preg_match('#^(javascript|data):#i', $url)) {
            return null;
        }
        if (preg_match('#^https?://#i', $url) || str_starts_with($url, '/')) {
            return $url;
        }

        return '/' . ltrim($url, '/');
    }
}
