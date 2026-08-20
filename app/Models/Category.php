<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'icon', 'logo', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /** Public URL for the category logo */
    public function getLogoUrlAttribute(): string
    {
        if ($this->logo) return asset($this->logo);
        return asset('assets/logo-mark.png');
    }
}
