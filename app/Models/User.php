<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'phone', 'avatar', 'password',
        'is_admin', 'admin_role', 'is_retailer', 'last_login_ip', 'last_login_at', 'last_login_user_agent',
    ];
    protected $hidden   = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'password'          => 'hashed',
            'is_admin'          => 'boolean',
            'is_retailer'       => 'boolean',
        ];
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(WalletDeposit::class)->latest();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest();
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class)->latest();
    }

    public function specialPrices(): HasMany
    {
        return $this->hasMany(SpecialPrice::class);
    }

    public const ADMIN_ROLE_MAIN = 'main';
    public const ADMIN_ROLE_ADMIN = 'admin';

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function isMainAdmin(): bool
    {
        return $this->is_admin && $this->admin_role === self::ADMIN_ROLE_MAIN;
    }

    public function adminRoleLabel(): string
    {
        if (! $this->is_admin) {
            return 'Customer';
        }

        return $this->admin_role === self::ADMIN_ROLE_MAIN ? 'Main admin' : 'Admin';
    }

    /** Avatar URL (uploaded or generated initials) */
    public function avatarUrl(int $size = 44): string
    {
        if ($this->avatar) {
            return asset('storage/' . ltrim($this->avatar, '/'));
        }
        $initials = collect(explode(' ', trim($this->name)))
            ->map(fn ($w) => strtoupper(substr($w, 0, 1)))
            ->take(2)
            ->implode('');
        return 'https://ui-avatars.com/api/?' . http_build_query([
            'name'       => $initials ?: 'U',
            'size'       => $size,
            'bold'       => 'true',
            'color'      => 'fff',
            'background' => '0b2a5b',
        ]);
    }
}
