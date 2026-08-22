<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Category;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Service;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Customer dashboard — stats, wallet, ALL categories + services loaded
     * up-front so tab switching is instant (no page reload).
     */
    public function index(): View
    {
        $user = auth()->user();
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id]);

        $orders = $user->orders()->with(['service', 'provider'])->latest()->limit(10)->get();

        $totalCashbackEarned = (float) $user->orders()->where('status', 'success')->sum('profit');
        $stats = [
            'total_orders'     => $user->orders()->count(),
            'successful'       => $user->orders()->where('status', 'success')->count(),
            'total_spent'      => $user->orders()->where('status', 'success')->sum('amount'),
            'total_cashback'   => $totalCashbackEarned,
            'balance'          => (float) $wallet->balance,
        ];

        // Load ALL active categories with their active services + plans (one query)
        $categories = Category::where('is_active', true)
            ->withWhereHas('services', fn ($q) => $q->where('is_active', true)
                ->with(['plans', 'specialPrices' => fn ($sp) => $sp->where('user_id', $user->id)])
                ->orderBy('name'))
            ->orderBy('sort_order')
            ->get();

        foreach ($categories as $cat) {
            foreach ($cat->services as $svc) {
                $svc->applyEffectivePricing($user);
            }
        }

        $activeCategory = $categories->first();

        $servicesByCategory = $categories->mapWithKeys(function ($cat) {
            return [$cat->slug => $cat->services];
        });

        $dashboardAlerts = Alert::forDashboard($user);

        return view('dashboard.index', compact(
            'user', 'wallet', 'orders', 'stats',
            'categories', 'activeCategory', 'servicesByCategory',
            'dashboardAlerts'
        ));
    }

    public function dismissAlert(Request $request, Alert $alert)
    {
        $alert->dismissFor($request->user());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true]);
        }

        return back();
    }

    /**
     * Operator Plans & Rates page.
     *
     * Real-world grouping: customers see BRANDS, not API op_codes.
     * "Dialog" (mobile) is ONE brand — prepaid plans + postpaid bill CTA.
     * "Dialog Home Broadband Prepaid" and "Dialog Home Broadband Postpaid"
     * are SEPARATE cards because they're separate products (different routers,
     * different plans, different reload endpoints).
     */
    public function plans(): View
    {
        $typeOrder = ['data', 'combo', 'voice', 'social', 'reload', 'tv', 'bill'];
        $typeMeta  = [
            'data'   => ['label' => 'Data',   'icon' => 'wifi'],
            'combo'  => ['label' => 'Combo',  'icon' => 'grid'],
            'voice'  => ['label' => 'Voice',  'icon' => 'phone'],
            'social' => ['label' => 'Social', 'icon' => 'users'],
            'reload' => ['label' => 'Reload', 'icon' => 'bolt'],
            'tv'     => ['label' => 'TV',     'icon' => 'tv-card'],
            'bill'   => ['label' => 'Bills',  'icon' => 'bill'],
        ];

        // Logical customer-facing groups (op_code list from TopupMart catalog).
        // Each group = one card on the plans page.
        // 'primary_op' is the op_code to attach PREPAID plans to (plans go through this code).
        // 'bill_ops'  are additional op_codes in the same group that are bill-payment only
        //             (postpaid/utility/insurance/wallet) — shown as a "Pay bill" CTA.
        // 'is_bill_only' = true means the whole group is a bill-payment (no data plans).
        $groups = [
            // ===== MOBILE =====
            (object) [
                'key' => 'dialog', 'label' => 'Dialog',
                'logo' => 'assets/logos/dialog.png',
                'primary_op' => '181', 'bill_ops' => ['171'], 'other_ops' => ['921'],
                'category' => 'mobile', 'is_bill_only' => false,
                'bill_label' => 'Postpaid bill payment',
            ],
            (object) [
                'key' => 'mobitel', 'label' => 'Mobitel',
                'logo' => 'assets/logos/sltmobitel.png',
                'primary_op' => '183', 'bill_ops' => ['173'], 'other_ops' => [],
                'category' => 'mobile', 'is_bill_only' => false,
                'bill_label' => 'Postpaid bill payment',
            ],
            (object) [
                'key' => 'hutch', 'label' => 'Hutch',
                'logo' => 'assets/logos/hutch.png',
                'primary_op' => '182', 'bill_ops' => ['172'], 'other_ops' => [],
                'category' => 'mobile', 'is_bill_only' => false,
                'bill_label' => 'Postpaid bill payment',
            ],
            (object) [
                'key' => 'airtel', 'label' => 'Airtel',
                'logo' => 'assets/logos/airtel.png',
                'primary_op' => '180', 'bill_ops' => ['170'], 'other_ops' => [],
                'category' => 'mobile', 'is_bill_only' => false,
                'bill_label' => 'Postpaid bill payment',
            ],

            // ===== BROADBAND =====
            (object) [
                'key' => 'dialog-hbb-prepaid', 'label' => 'Dialog Home Broadband',
                'logo' => 'assets/logos/dialog.png',
                'tag' => 'Prepaid',
                'primary_op' => '102', 'bill_ops' => [], 'other_ops' => ['922'],
                'category' => 'broadband', 'is_bill_only' => false,
                'bill_label' => null,
            ],
            (object) [
                'key' => 'dialog-hbb-postpaid', 'label' => 'Dialog Home Broadband',
                'logo' => 'assets/logos/dialog.png',
                'tag' => 'Postpaid',
                'primary_op' => '101', 'bill_ops' => [], 'other_ops' => [],
                'category' => 'broadband', 'is_bill_only' => false,
                'bill_label' => null,
            ],
            (object) [
                'key' => 'slt-router-prepaid', 'label' => 'SLT-Mobitel 4G Router',
                'logo' => 'assets/logos/sltmobitel.png',
                'tag' => 'Prepaid',
                'primary_op' => '103', 'bill_ops' => [], 'other_ops' => [],
                'category' => 'broadband', 'is_bill_only' => false,
                'bill_label' => null,
            ],
            (object) [
                'key' => 'slt-router-postpaid', 'label' => 'SLT-Mobitel 4G Router',
                'logo' => 'assets/logos/sltmobitel.png',
                'tag' => 'Postpaid',
                // SLT-Mobitel postpaid broadband bills are paid through the
                // generic 'SLT Bill' service under Utility Bills (op 198) —
                // link to it as a bill-only card so customers can still pay
                // their postpaid router bill.
                'primary_op' => null, 'bill_ops' => ['198'], 'other_ops' => [],
                'category' => 'broadband', 'is_bill_only' => true,
                'bill_label' => 'Postpaid bill payment',
            ],

            // ===== TV =====
            (object) [
                'key' => 'dialog-tv', 'label' => 'Dialog TV',
                'logo' => 'assets/logos/dialog.png',
                'primary_op' => '192', 'bill_ops' => ['191'], 'other_ops' => ['923'],
                'category' => 'tv', 'is_bill_only' => false,
                'bill_label' => 'Postpaid bill payment',
            ],
            (object) [
                'key' => 'tv-lanka', 'label' => 'TV Lanka',
                'logo' => 'assets/logos/tvlanka.png',
                'primary_op' => null, 'bill_ops' => ['193','194'], 'other_ops' => [],
                'category' => 'tv', 'is_bill_only' => true,
                'bill_label' => 'TV Lanka reload / bill',
            ],
            (object) [
                'key' => 'ask-cable', 'label' => 'Ask Cable Vision',
                'logo' => 'assets/logos/askcable.png',
                'primary_op' => null, 'bill_ops' => ['190'], 'other_ops' => [],
                'category' => 'tv', 'is_bill_only' => true,
                'bill_label' => 'Cable bill payment',
            ],

            // ===== UTILITY BILLS =====
            (object) [
                'key' => 'ceb', 'label' => 'CEB Electricity',
                'logo' => 'assets/logos/ceb.png',
                'primary_op' => null, 'bill_ops' => ['195'], 'other_ops' => [],
                'category' => 'utility', 'is_bill_only' => true,
                'bill_label' => 'Pay CEB bill',
            ],
            (object) [
                'key' => 'leco', 'label' => 'LECO Electricity',
                'logo' => 'assets/logos/leco.png',
                'primary_op' => null, 'bill_ops' => ['196'], 'other_ops' => [],
                'category' => 'utility', 'is_bill_only' => true,
                'bill_label' => 'Pay LECO bill',
            ],
            (object) [
                'key' => 'nwsdb', 'label' => 'Water (NWSDB)',
                'logo' => 'assets/logos/nwsdb.png',
                'primary_op' => null, 'bill_ops' => ['197'], 'other_ops' => [],
                'category' => 'utility', 'is_bill_only' => true,
                'bill_label' => 'Pay water bill',
            ],
            (object) [
                'key' => 'slt-bill', 'label' => 'SLT Telephone Bill',
                'logo' => 'assets/logos/sltmobitel.png',
                'primary_op' => null, 'bill_ops' => ['198'], 'other_ops' => [],
                'category' => 'utility', 'is_bill_only' => true,
                'bill_label' => 'Pay SLT bill',
            ],

            // ===== INSURANCE =====
            (object) ['key' => 'aia', 'label' => 'AIA Life',           'logo' => 'assets/logos/aia.png',        'primary_op' => null, 'bill_ops' => ['130'], 'category' => 'insurance', 'is_bill_only' => true, 'bill_label' => 'Pay premium'],
            (object) ['key' => 'arpico','label' => 'Arpico Insurance', 'logo' => 'assets/logos/arpico.png',    'primary_op' => null, 'bill_ops' => ['131'], 'category' => 'insurance', 'is_bill_only' => true, 'bill_label' => 'Pay premium'],
            (object) ['key' => 'ceylinco','label' => 'Ceylinco Life',  'logo' => 'assets/logos/ceylinco.png',  'primary_op' => null, 'bill_ops' => ['132'], 'category' => 'insurance', 'is_bill_only' => true, 'bill_label' => 'Pay premium'],
            (object) ['key' => 'hnb', 'label' => 'HNB Assurance',      'logo' => 'assets/logos/hnbassu.png',   'primary_op' => null, 'bill_ops' => ['133'], 'category' => 'insurance', 'is_bill_only' => true, 'bill_label' => 'Pay premium'],
            (object) ['key' => 'sli', 'label' => 'Sri Lanka Insurance','logo' => 'assets/logos/srilankains.png','primary_op' => null, 'bill_ops' => ['134'], 'category' => 'insurance', 'is_bill_only' => true, 'bill_label' => 'Pay premium'],

            // ===== WALLET / DRIVER TOPUPS =====
            (object) ['key' => 'pickme', 'label' => 'PickMe',    'logo' => 'assets/logos/pickme.png',   'primary_op' => null, 'bill_ops' => ['104'], 'category' => 'wallet-topup', 'is_bill_only' => true, 'bill_label' => 'Top up PickMe wallet'],
            (object) ['key' => 'uber',   'label' => 'Uber Eats', 'logo' => 'assets/logos/ubereats.png', 'primary_op' => null, 'bill_ops' => ['105'], 'category' => 'wallet-topup', 'is_bill_only' => true, 'bill_label' => 'Top up Uber Eats'],

        ];

        // ===== INDIAN DTH (Happy Recharge Center) =====
        // Built from live active DTH services so operator-code edits in admin
        // are reflected here. Topup Mart DTH rows are inactive (failover only).
        $dthServices = Service::where('is_active', true)
            ->where(function ($q) {
                $q->where('type', 'dth')
                  ->orWhereHas('category', fn ($c) => $c->where('slug', 'dth'));
            })
            ->whereHas('provider', function ($q) {
                $q->where('slug', 'happy-recharge-center')
                  ->orWhere('api_class', 'happy_recharge_center');
            })
            ->orderBy('name')
            ->get();
        foreach ($dthServices as $svc) {
            $groups[] = (object) [
                'key'          => 'dth-' . $svc->id,
                'label'        => $svc->name,
                'logo'         => $svc->logo ?: null,
                'primary_op'   => $svc->op_code,
                'bill_ops'     => [],
                'other_ops'    => [],
                'category'     => 'dth',
                'is_bill_only' => false,
                'bill_label'   => null,
            ];
        }

        // Load ALL services keyed by op_code for fast lookup
        $viewer = auth()->user();
        $allServices = Service::where('is_active', true)
            ->with(['plans', 'specialPrices' => fn ($sp) => $sp->where('user_id', $viewer?->id)])
            ->get()
            ->each(fn (Service $s) => $s->applyEffectivePricing($viewer))
            ->keyBy(fn (Service $s) => $s->op_code);

        // Build categories collection keyed by slug (same structure as before)
        // but with grouped "services" so existing tab code keeps working.
        $categories = Category::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->keyBy('slug');

        $groupsByCategory = collect($groups)
            ->groupBy(fn ($g) => $g->category)
            ->map(function ($catGroups) use ($allServices, $typeOrder, $typeMeta) {
                foreach ($catGroups as $g) {
                    // Resolve primary service. For bill-only groups with no primary_op,
                    // point at the first bill service so the CTA doesn't 404.
                    $primary = null;
                    if ($g->primary_op) {
                        $primary = $allServices->get($g->primary_op);
                    }
                    $billServices = collect($g->bill_ops ?? [])
                        ->map(fn ($op) => $allServices->get($op))
                        ->filter();

                    if (! $primary && $g->is_bill_only && $billServices->isNotEmpty()) {
                        $primary = $billServices->first();
                        $billServices = collect();
                    }

                    $g->primary = $primary;
                    $g->billServices = $billServices;

                    // Merge primary plans with any "other_ops" service plans
                    // (e.g. op 921 = Dialog API plain reloads that belong on the Dialog mobile card,
                    // op 922 = Dialog HBB API reloads, op 923 = Dialog TV API).
                    // De-dupe by (amount, type, normalized name) so seeded standard reloads
                    // don't appear twice.
                    $plansCollection = collect();
                    $seen = [];
                    $planSources = collect();
                    if ($primary) $planSources->push($primary);
                    foreach (($g->other_ops ?? []) as $op) {
                        $other = $allServices->get($op);
                        if ($other) $planSources->push($other);
                    }
                    foreach ($planSources as $srcSvc) {
                        foreach ($srcSvc->plans as $pl) {
                            $key = $pl->amount . '|' . $pl->type . '|' . strtolower(trim($pl->name));
                            if (isset($seen[$key])) continue;
                            $seen[$key] = true;
                            // Tag each plan with the service it should actually route through
                            // (so plain reloads from the API op code submit to that op_code, not primary_op).
                            $pl->route_service_id = $srcSvc->id;
                            $plansCollection->push($pl);
                        }
                    }

                    $g->plansGrouped = $plansCollection
                        ->groupBy(fn (Plan $p) => $p->type)
                        ->map(function (Collection $items, string $type) use ($typeMeta) {
                            return [
                                'type'  => $type,
                                'label' => $typeMeta[$type]['label'] ?? ucfirst($type),
                                'icon'  => $typeMeta[$type]['icon'] ?? 'bolt',
                                'items' => $items->sortBy(fn (Plan $p) => $p->amount)->values(),
                            ];
                        })
                        ->sortBy(function ($grp) use ($typeOrder) {
                            $idx = array_search($grp['type'], $typeOrder, true);
                            return $idx !== false ? $idx : 99;
                        })
                        ->values();

                    // Cashback badge
                    $g->cashback = null;
                    if ($primary && (float) $primary->profit > 0) {
                        $g->cashback = (object) [
                            'profit'      => (float) $primary->profit,
                            'profit_type' => $primary->profit_type,
                        ];
                    }

                    $g->planCount = $plansCollection->count();
                }
                return $catGroups;
            });

        // Build a clean list of categories that actually have groups
        $visibleCategories = $categories
            ->filter(fn ($c) => $groupsByCategory->has($c->slug))
            ->map(function ($c) use ($groupsByCategory) {
                $c->groups = $groupsByCategory->get($c->slug, collect());
                return $c;
            })
            ->values();

        return view('dashboard.plans', compact('visibleCategories', 'typeMeta'));
    }
}
