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
        foreach ([$this->logo_path, $this->logo_url] as $raw) {
            $url = $this->resolveLogo($raw);
            if ($url) {
                return $url;
            }
        }

        $cat = $this->bank_slug ? SriLankanBanks::find($this->bank_slug) : null;
        if ($cat && ! empty($cat['logo'])) {
            return asset($cat['logo']);
        }

        return asset('assets/logo-mark.png');
    }

    protected function resolveLogo(?string $raw): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }
        if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
            return $raw;
        }
        $path = ltrim($raw, '/');
        if (is_file(public_path($path))) {
            return asset($path);
        }
        if (is_file(storage_path('app/public/' . $path))) {
            return asset('storage/' . $path);
        }

        return null;
    }
}
