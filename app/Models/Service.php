<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        'provider_id', 'category_id', 'op_code', 'name', 'short_name',
        'logo', 'type', 'profit', 'profit_type', 'is_active', 'meta',
    ];

    protected $casts = [
        'profit'    => 'decimal:2',
        'is_active' => 'boolean',
        'meta'      => 'array',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class)->where('is_active', true)->orderBy('sort_order')->orderBy('amount');
    }

    public function specialPrices(): HasMany
    {
        return $this->hasMany(SpecialPrice::class);
    }

    /**
     * Default catalog profit vs this user's special commission.
     *
     * @return array{profit: float, type: string, special: bool}
     */
    public function effectivePricing(?User $user = null): array
    {
        $attrs  = $this->getAttributes();
        $profit = (float) ($attrs['profit'] ?? 0);
        $type   = (string) ($attrs['profit_type'] ?? 'FLAT');
        $special = false;

        if ($user) {
            $row = $this->relationLoaded('specialPrices')
                ? $this->specialPrices->firstWhere('user_id', $user->id)
                : SpecialPrice::where('user_id', $user->id)->where('service_id', $this->id)->first();
            if ($row) {
                $profit  = (float) $row->profit;
                $type    = (string) $row->profit_type;
                $special = true;
            }
        }

        return compact('profit', 'type', 'special');
    }

    /** Mutate this instance's profit fields for display (does not persist). */
    public function applyEffectivePricing(?User $user): self
    {
        $eff = $this->effectivePricing($user);
        $this->setAttribute('profit', $eff['profit']);
        $this->setAttribute('profit_type', $eff['type']);
        $this->setAttribute('has_special_price', $eff['special']);
        return $this;
    }

    /**
     * Default logo map by op_code so services that were imported before logos
     * were added to the catalog still get a proper brand logo without re-seeding.
     * @var array<string, string>
     */
    protected static array $logoMap = [
        // Dialog
        '921' => 'assets/logos/dialog.png', '922' => 'assets/logos/dialog.png', '923' => 'assets/logos/dialog.png',
        '101' => 'assets/logos/dialog.png', '102' => 'assets/logos/dialog.png',
        '181' => 'assets/logos/dialog.png', '171' => 'assets/logos/dialog.png',
        '192' => 'assets/logos/dialog.png',  '191' => 'assets/logos/dialog.png',
        // SLT-Mobitel
        '103' => 'assets/logos/sltmobitel.png',
        '183' => 'assets/logos/sltmobitel.png', '173' => 'assets/logos/sltmobitel.png',
        '198' => 'assets/logos/sltmobitel.png',
        // Airtel
        '180' => 'assets/logos/airtel.png', '170' => 'assets/logos/airtel.png',
        '120' => 'assets/logos/airtel.png', // Airtel DTH (TopupMart failover)
        '20'  => 'assets/logos/airtel.png', // Airtel DTH (HRC)
        // Hutch
        '182' => 'assets/logos/hutch.png', '172' => 'assets/logos/hutch.png',
        // Utilities
        '195' => 'assets/logos/ceb.png',
        '196' => 'assets/logos/leco.png',
        '197' => 'assets/logos/nwsdb.png',
        // Cable TV (SL)
        '193' => 'assets/logos/tvlanka.png', '194' => 'assets/logos/tvlanka.png',
        '190' => 'assets/logos/askcable.png',
        // Insurance
        '130' => 'assets/logos/aia.png',
        '131' => 'assets/logos/arpico.png',
        '132' => 'assets/logos/ceylinco.png',
        '133' => 'assets/logos/hnbassu.png',
        '134' => 'assets/logos/srilankains.png',
        // India DTH — TopupMart failover placeholders
        '121' => 'assets/logos/dishtv.png',
        '122' => 'assets/logos/sundirect.png',
        '123' => 'assets/logos/tataplay.png',
        '124' => 'assets/logos/d2h.png',
        // India DTH — Happy Recharge Center operator codes
        '16'  => 'assets/logos/dishtv.png',
        '17'  => 'assets/logos/tataplay.png',
        '18'  => 'assets/logos/d2h.png',
        '19'  => 'assets/logos/sundirect.png',
        // Wallet / Driver payments
        '104' => 'assets/logos/pickme.png',
        '105' => 'assets/logos/ubereats.png',
    ];

    /** Public URL for the operator logo */
    public function getLogoUrlAttribute(): string
    {
        $logo = $this->logo;
        if (! $logo && $this->op_code) {
            $logo = static::$logoMap[(string) $this->op_code] ?? null;
        }
        if ($logo) return asset($logo);
        return asset('assets/logo-mark.png');
    }

    /** Sync any missing logo from the default map (called from admin import/re-seed) */
    public function syncDefaultLogo(): bool
    {
        if ($this->logo) return false;
        if (! $this->op_code) return false;
        $mapped = static::$logoMap[(string) $this->op_code] ?? null;
        if (! $mapped) return false;
        $this->logo = $mapped;
        return true;
    }

    /**
     * Calculate cashback for a given amount based on profit setting.
     * profit_type = FLAT => fixed LKR.
     * profit_type = PCT  => percentage of amount.
     */
    public function calculateCashback(float $amount, ?User $user = null): float
    {
        $eff    = $this->effectivePricing($user);
        $profit = $eff['profit'];
        if ($profit <= 0) return 0;

        if ($eff['type'] === 'PCT') {
            return round(($amount * $profit) / 100, 2);
        }
        return round($profit, 2);
    }
}
