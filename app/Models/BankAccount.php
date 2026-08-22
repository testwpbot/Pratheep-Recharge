<?php

namespace App\Models;

use App\Support\SriLankanBanks;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class BankAccount extends Model
{
    protected $fillable = [
        'bank_slug', 'bank_name', 'account_name', 'account_no', 'branch',
        'instructions', 'logo_path', 'logo_url', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    public function logoUrl(): string
    {
        if ($this->logo_path) {
            $path = ltrim($this->logo_path, '/');
            if (str_starts_with($path, 'http')) {
                return $path;
            }
            if (is_file(public_path($path))) {
                return asset($path);
            }
            if (is_file(storage_path('app/public/' . $path))) {
                return asset('storage/' . $path);
            }
        }
        if ($this->logo_url) {
            return $this->logo_url;
        }
        $cat = $this->bank_slug ? SriLankanBanks::find($this->bank_slug) : null;
        if ($cat && ! empty($cat['logo']) && is_file(public_path($cat['logo']))) {
            return asset($cat['logo']);
        }

        return asset('assets/logo-mark.png');
    }
}
