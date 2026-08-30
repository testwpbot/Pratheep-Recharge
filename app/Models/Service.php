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
     * Services customers can buy: service On, provider On, not a hidden API twin.
     */
    public function scopeForCustomers($query)
    {
        return $query->where('services.is_active', true)
            ->where('type', '!=', 'api')
            ->whereHas('provider', fn ($q) => $q->where('is_active', true));
    }

    public function isVisibleToCustomers(): bool
    {
        if (! $this->is_active) {
            return false;
        }
        if (strtolower((string) $this->type) === 'api') {
            return false;
        }

        $provider = $this->relationLoaded('provider') ? $this->provider : $this->provider()->first();

        return $provider && $provider->is_active;
    }

    /** Bill / utility operators that TMobiling sends with from_bbps=1. */
    public function usesBbps(): bool
    {
        return (bool) (($this->meta ?? [])['bbps'] ?? false);
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
        '120' => 'assets/logos/airtel.png', // Airtel DTH (Topup Mart)
        // TMobiling mobile / TV / router
        '1'   => 'assets/logos/dialog.png', '12' => 'assets/logos/dialog.png',
        '5'   => 'assets/logos/dialog.png', '16' => 'assets/logos/dialog.png',
        '6'   => 'assets/logos/dialog.png', '17' => 'assets/logos/dialog.png',
        '7'   => 'assets/logos/dialog.png', '18' => 'assets/logos/dialog.png',
        '2'   => 'assets/logos/airtel.png', '13' => 'assets/logos/airtel.png',
        '3'   => 'assets/logos/sltmobitel.png', '14' => 'assets/logos/sltmobitel.png',
        '28'  => 'assets/logos/sltmobitel.png', '19' => 'assets/logos/sltmobitel.png',
        '4'   => 'assets/logos/hutch.png', '15' => 'assets/logos/hutch.png',
        '9'   => 'assets/logos/lankabell.png',
        '10'  => 'assets/logos/pickme.png',
        '40'  => 'assets/logos/ubereats.png',
        '23'  => 'assets/logos/airtel.png',
        '29'  => 'assets/logos/ceb.png',
        '30'  => 'assets/logos/leco.png',
        '31'  => 'assets/logos/nwsdb.png',
        '32'  => 'assets/logos/aia.png',
        '36'  => 'assets/logos/srilankains.png',
        '33'  => 'assets/logos/hnbassu.png',
        '68'  => 'assets/logos/hnbassu.png',
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
        // TMobiling Indian DTH
        '20'  => 'assets/logos/sundirect.png',
        '21'  => 'assets/logos/d2h.png',
        '22'  => 'assets/logos/dishtv.png',
        '79'  => 'assets/logos/tataplay.png',
        // Wallet / Driver payments
        '104' => 'assets/logos/pickme.png',
        '105' => 'assets/logos/ubereats.png',
    ];

    public function isBillLike(): bool
    {
        $type = strtolower((string) $this->type);
        $slug = strtolower((string) ($this->category?->slug ?? ''));

        return in_array($type, ['utility', 'postpaid', 'bill', 'insurance', 'wallet'], true)
            || in_array($slug, ['utility', 'insurance', 'wallet-topup'], true);
    }

    /** Mobile reload and postpaid do not use a separate SMS notify number. */
    public function hidesNotifyNumber(): bool
    {
        $slug = strtolower((string) ($this->category?->slug ?? ''));
        $type = strtolower((string) $this->type);

        return $slug === 'mobile' || $type === 'postpaid';
    }

    /**
     * Human label for the account/identifier field, chosen by the real-world
     * kind of service. DTH needs a smart-card number, insurance a policy
     * number, electricity a CEB/LECO account number, etc. — never just
     * "Mobile Number" for everything.
     *
     * Relies on op_code + type (both always present) so it works even when the
     * category relation isn't eager-loaded.
     */
    public function accountFieldLabel(): string
    {
        $type = strtolower((string) $this->type);
        $slug = strtolower((string) ($this->category?->slug ?? ''));
        $op   = (string) $this->op_code;

        // Utility sub-types need a specific account label.
        if (in_array($op, ['29', '195'], true)) return 'CEB Account Number';
        if (in_array($op, ['30', '196'], true)) return 'LECO Account Number';
        if (in_array($op, ['31', '197'], true)) return 'Water Account Number';
        if ($op === '198')                      return 'SLT Telephone / Account Number';
        if (in_array($op, ['9', '18'], true))   return 'Telephone Number';

        if ($type === 'dth' || $slug === 'dth')             return 'Smart Card / VC Number';
        if ($type === 'insurance' || $slug === 'insurance') return 'Policy Number';
        if ($type === 'wallet' || $slug === 'wallet-topup') return 'Registered Mobile Number';
        if ($type === 'broadband' || $slug === 'broadband') return 'Account / Username';
        if ($type === 'tv' || $slug === 'tv')               return 'Account / Smart Card Number';
        if ($type === 'utility' || $slug === 'utility')     return 'Account / Bill Number';
        if ($type === 'postpaid' || $type === 'prepaid' || $slug === 'mobile') return 'Mobile Number';

        return 'Account / Reference Number';
    }

    /** Placeholder text matching accountFieldLabel(). */
    public function accountFieldPlaceholder(): string
    {
        $type = strtolower((string) $this->type);
        $slug = strtolower((string) ($this->category?->slug ?? ''));
        $op   = (string) $this->op_code;

        if ($type === 'dth' || $slug === 'dth')             return 'Smart card / viewing card number';
        if ($type === 'insurance' || $slug === 'insurance') return 'Policy number';
        if (in_array($op, ['9', '18'], true))               return 'e.g. 0112345678';
        if ($type === 'broadband' || $slug === 'broadband') return 'Account number or username';
        if ($type === 'tv' || $slug === 'tv')               return 'Account / smart card number';
        if ($type === 'utility' || $slug === 'utility')     return 'Account / bill number';

        return 'e.g. 0771234567';
    }

    /** Short helper line under the account field. */
    public function accountFieldHint(): string
    {
        $type = strtolower((string) $this->type);
        $slug = strtolower((string) ($this->category?->slug ?? ''));

        if ($type === 'dth' || $slug === 'dth') {
            return 'Enter the smart card / viewing card (VC) number printed on your DTH box or bill.';
        }
        if ($type === 'insurance' || $slug === 'insurance') {
            return 'Enter your insurance policy number.';
        }
        if ($type === 'wallet' || $slug === 'wallet-topup') {
            return 'Enter the mobile number registered with the app/wallet.';
        }
        if ($type === 'utility' || $slug === 'utility') {
            return 'Enter the account / bill reference number shown on your bill.';
        }
        if ($type === 'broadband' || $slug === 'broadband') {
            return 'Enter your broadband account number or login username.';
        }
        if ($type === 'tv' || $slug === 'tv') {
            return 'Enter your TV account or smart card number.';
        }

        return 'The number you want to recharge or pay for.';
    }

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

    /** True for Indian DTH services (priced in INR, wallet charged in LKR). */
    public function isDth(): bool
    {
        $type = strtolower((string) $this->type);
        $slug = strtolower((string) ($this->category?->slug ?? ''));

        return $type === 'dth' || $slug === 'dth';
    }

    /**
     * INR -> LKR rate that applies to this service. DTH services convert the
     * INR pack value the customer enters into a LKR wallet charge; every other
     * service is 1:1 (LKR).
     */
    public function fxRate(): float
    {
        return $this->isDth() ? \App\Models\Setting::dthInrRate() : 1.0;
    }

    /**
     * The amount to send to the provider for a given LKR amount the customer
     * entered. DTH packs are priced in INR, so the provider receives
     * amount / fxRate. Every other service is 1:1 (LKR).
     */
    public function providerAmountFor(float $amount): float
    {
        return round($amount / $this->fxRate(), 2);
    }

    /**
     * Only bill-like services (utility / postpaid / insurance / wallet) may
     * charge the customer an extra fee. Mobile reloads stay cashback-only.
     */
    public function allowsFee(): bool
    {
        return $this->isBillLike();
    }

    /** Flat LKR fee for the modal (0 unless a FLAT negative profit fee applies). */
    public function feeFlat(?User $user = null): float
    {
        if (! $this->allowsFee()) return 0.0;
        $eff = $this->effectivePricing($user);
        if ($eff['profit'] >= 0 || $eff['type'] === 'PCT') return 0.0;
        return round(abs($eff['profit']), 2);
    }

    /** Percentage fee for the modal (0 unless a PCT negative profit fee applies). */
    public function feePct(?User $user = null): float
    {
        if (! $this->allowsFee()) return 0.0;
        $eff = $this->effectivePricing($user);
        if ($eff['profit'] >= 0 || $eff['type'] !== 'PCT') return 0.0;
        return round(abs($eff['profit']), 2);
    }

    /**
     * Customer service fee (surcharge) for a given order amount. A NEGATIVE
     * profit means "charge the customer extra" — the magnitude of that negative
     * profit is the fee. Returns a positive LKR value (0 when no fee applies).
     *
     * Fees are only honoured for bill-like services; a negative profit on a
     * mobile reload is ignored (treated as zero) to avoid surprise surcharges.
     */
    public function calculateFee(float $amount, ?User $user = null): float
    {
        if (! $this->allowsFee()) {
            return 0.0;
        }

        $eff    = $this->effectivePricing($user);
        $profit = $eff['profit'];
        if ($profit >= 0) return 0.0;

        $fee = abs($profit);
        if ($eff['type'] === 'PCT') {
            return round(($amount * $fee) / 100, 2);
        }
        return round($fee, 2);
    }
}
