<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Category;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Service;
use App\Models\Wallet;
use App\Support\HistoryPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Customer dashboard — stats, wallet, ALL categories + services loaded
     * up-front so tab switching is instant (no page reload).
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id]);
        $period = HistoryPeriod::fromRequest($request);

        $orderQuery = $user->orders()->with(['service', 'provider']);
        $period->apply($orderQuery, 'created_at', fn ($q) => $q->whereIn('status', ['pending', 'processing']));
        $orders = $orderQuery->latest()->limit(10)->get();

        $todayOrders = $user->orders();
        $period->apply($todayOrders);
        $totalCashbackEarned = (float) $user->orders()->where('status', 'success')->sum('profit');
        $stats = [
            'total_orders'     => (clone $todayOrders)->count(),
            'successful'       => (clone $todayOrders)->where('status', 'success')->count(),
            'total_spent'      => (clone $todayOrders)->where('status', 'success')->sum('amount'),
            'total_cashback'   => $totalCashbackEarned,
            'balance'          => (float) $wallet->balance,
        ];

        // Active services on an active provider only. Turning a provider Off
        // hides every service that belongs to it.
        $categories = Category::where('is_active', true)
            ->withWhereHas('services', fn ($q) => $q->forCustomers()
                ->with(['plans', 'category', 'provider', 'specialPrices' => fn ($sp) => $sp->where('user_id', $user->id)])
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

        return view('dashboard.index', compact(
            'user', 'wallet', 'orders', 'stats', 'period',
            'categories', 'activeCategory', 'servicesByCategory'
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
                'primary_op' => '181', 'fallback_ops' => ['1'], 'bill_ops' => ['171', '12'], 'other_ops' => ['921'],
                'category' => 'mobile', 'is_bill_only' => false,
                'bill_label' => 'Postpaid bill payment',
            ],
            (object) [
                'key' => 'mobitel', 'label' => 'Mobitel',
                'logo' => 'assets/logos/sltmobitel.png',
                'primary_op' => '183', 'fallback_ops' => ['3'], 'bill_ops' => ['173', '14'], 'other_ops' => [],
                'category' => 'mobile', 'is_bill_only' => false,
                'bill_label' => 'Postpaid bill payment',
            ],
            (object) [
                'key' => 'hutch', 'label' => 'Hutch',
                'logo' => 'assets/logos/hutch.png',
                'primary_op' => '182', 'fallback_ops' => ['4'], 'bill_ops' => ['172', '15'], 'other_ops' => [],
                'category' => 'mobile', 'is_bill_only' => false,
                'bill_label' => 'Postpaid bill payment',
            ],
            (object) [
                'key' => 'airtel', 'label' => 'Airtel',
                'logo' => 'assets/logos/airtel.png',
                'primary_op' => '180', 'fallback_ops' => ['2'], 'bill_ops' => ['170', '13'], 'other_ops' => [],
                'category' => 'mobile', 'is_bill_only' => false,
                'bill_label' => 'Postpaid bill payment',
            ],

            // ===== MOBILE — API (TMobiling) =====
            // The SAME mobile brands, but routed through the TMobiling provider.
            // These render under the "API" filter tab on the mobile section.
            // (TMobiling mobile prepaid op codes: 1=Dialog, 2=Airtel, 3=Mobitel,
            //  4=Hutch — distinct from TopupMart's 18x codes, so no collision.)
            (object) [
                'key' => 'dialog-api', 'label' => 'Dialog', 'logo' => 'assets/logos/dialog.png',
                'tag' => 'API', 'is_api' => true,
                'primary_op' => '1', 'fallback_ops' => [], 'bill_ops' => [], 'other_ops' => [],
                'category' => 'mobile', 'is_bill_only' => false, 'bill_label' => null,
            ],
            (object) [
                'key' => 'mobitel-api', 'label' => 'Mobitel', 'logo' => 'assets/logos/sltmobitel.png',
                'tag' => 'API', 'is_api' => true,
                'primary_op' => '3', 'fallback_ops' => [], 'bill_ops' => [], 'other_ops' => [],
                'category' => 'mobile', 'is_bill_only' => false, 'bill_label' => null,
            ],
            (object) [
                'key' => 'hutch-api', 'label' => 'Hutch', 'logo' => 'assets/logos/hutch.png',
                'tag' => 'API', 'is_api' => true,
                'primary_op' => '4', 'fallback_ops' => [], 'bill_ops' => [], 'other_ops' => [],
                'category' => 'mobile', 'is_bill_only' => false, 'bill_label' => null,
            ],
            (object) [
                'key' => 'airtel-api', 'label' => 'Airtel', 'logo' => 'assets/logos/airtel.png',
                'tag' => 'API', 'is_api' => true,
                'primary_op' => '2', 'fallback_ops' => [], 'bill_ops' => [], 'other_ops' => [],
                'category' => 'mobile', 'is_bill_only' => false, 'bill_label' => null,
            ],

            // ===== BROADBAND =====
            (object) [
                'key' => 'dialog-hbb-prepaid', 'label' => 'Dialog Home Broadband',
                'logo' => 'assets/logos/dialog.png',
                'tag' => 'Prepaid',
                'primary_op' => '102', 'fallback_ops' => ['6'], 'bill_ops' => [], 'other_ops' => ['922'],
                'category' => 'broadband', 'is_bill_only' => false,
                'bill_label' => null,
            ],
            (object) [
                'key' => 'dialog-hbb-postpaid', 'label' => 'Dialog Home Broadband',
                'logo' => 'assets/logos/dialog.png',
                'tag' => 'Postpaid',
                'primary_op' => '101', 'fallback_ops' => ['17'], 'bill_ops' => [], 'other_ops' => [],
                'category' => 'broadband', 'is_bill_only' => false,
                'bill_label' => null,
            ],
            (object) [
                'key' => 'slt-router-prepaid', 'label' => 'SLT-Mobitel 4G Router',
                'logo' => 'assets/logos/sltmobitel.png',
                'tag' => 'Prepaid',
                'primary_op' => '103', 'fallback_ops' => ['28'], 'bill_ops' => [], 'other_ops' => [],
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
                'primary_op' => '192', 'fallback_ops' => ['5'], 'bill_ops' => ['191', '16'], 'other_ops' => ['923'],
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

            // ===== DTH (Indian Direct-To-Home) =====
            // DTH is routed through Topup Mart, so 'primary_op' is the Topup Mart
            // op code (120-124) and the TMobiling code is the fallback. The card
            // renders through whichever provider is currently enabled; with both
            // on, Topup Mart wins. Topup Mart DTH packs are priced in INR — the
            // customer enters INR, their wallet is charged INR × rate (LKR), and
            // we send Topup Mart the INR pack value.
            (object) [
                'key' => 'airtel-dth', 'label' => 'Airtel Digital TV',
                'logo' => 'assets/logos/airtel.png',
                'primary_op' => '120', 'fallback_ops' => ['23'], 'bill_ops' => [], 'other_ops' => [],
                'category' => 'dth', 'is_bill_only' => false, 'bill_label' => null,
            ],
            (object) [
                'key' => 'dish-tv', 'label' => 'Dish TV',
                'logo' => 'assets/logos/dishtv.png',
                'primary_op' => '121', 'fallback_ops' => ['22'], 'bill_ops' => [], 'other_ops' => [],
                'category' => 'dth', 'is_bill_only' => false, 'bill_label' => null,
            ],
            (object) [
                'key' => 'sun-direct', 'label' => 'Sun Direct',
                'logo' => 'assets/logos/sundirect.png',
                'primary_op' => '122', 'fallback_ops' => ['20'], 'bill_ops' => [], 'other_ops' => [],
                'category' => 'dth', 'is_bill_only' => false, 'bill_label' => null,
            ],
            (object) [
                'key' => 'tata-play', 'label' => 'Tata Play',
                'logo' => 'assets/logos/tataplay.png',
                'primary_op' => '123', 'fallback_ops' => ['79'], 'bill_ops' => [], 'other_ops' => [],
                'category' => 'dth', 'is_bill_only' => false, 'bill_label' => null,
            ],
            (object) [
                'key' => 'videocon-d2h', 'label' => 'Videocon d2h',
                'logo' => 'assets/logos/d2h.png',
                'primary_op' => '124', 'fallback_ops' => ['21'], 'bill_ops' => [], 'other_ops' => [],
                'category' => 'dth', 'is_bill_only' => false, 'bill_label' => null,
            ],

            // ===== UTILITY BILLS =====
            (object) [
                'key' => 'ceb', 'label' => 'CEB Electricity',
                'logo' => 'assets/logos/ceb.png',
                'primary_op' => null, 'bill_ops' => ['195', '29'], 'other_ops' => [],
                'category' => 'utility', 'is_bill_only' => true,
                'bill_label' => 'Pay CEB bill',
            ],
            (object) [
                'key' => 'leco', 'label' => 'LECO Electricity',
                'logo' => 'assets/logos/leco.png',
                'primary_op' => null, 'bill_ops' => ['196', '30'], 'other_ops' => [],
                'category' => 'utility', 'is_bill_only' => true,
                'bill_label' => 'Pay LECO bill',
            ],
            (object) [
                'key' => 'nwsdb', 'label' => 'Water (NWSDB)',
                'logo' => 'assets/logos/nwsdb.png',
                'primary_op' => null, 'bill_ops' => ['197', '31'], 'other_ops' => [],
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
            (object) ['key' => 'aia', 'label' => 'AIA Life',           'logo' => 'assets/logos/aia.png',        'primary_op' => null, 'bill_ops' => ['130', '32'], 'category' => 'insurance', 'is_bill_only' => true, 'bill_label' => 'Pay premium'],
            (object) ['key' => 'arpico','label' => 'Arpico Insurance', 'logo' => 'assets/logos/arpico.png',    'primary_op' => null, 'bill_ops' => ['131'], 'category' => 'insurance', 'is_bill_only' => true, 'bill_label' => 'Pay premium'],
            (object) ['key' => 'ceylinco','label' => 'Ceylinco Life',  'logo' => 'assets/logos/ceylinco.png',  'primary_op' => null, 'bill_ops' => ['132'], 'category' => 'insurance', 'is_bill_only' => true, 'bill_label' => 'Pay premium'],
            (object) ['key' => 'hnb', 'label' => 'HNB Assurance',      'logo' => 'assets/logos/hnbassu.png',   'primary_op' => null, 'bill_ops' => ['133', '68'], 'category' => 'insurance', 'is_bill_only' => true, 'bill_label' => 'Pay premium'],
            (object) ['key' => 'sli', 'label' => 'Sri Lanka Insurance','logo' => 'assets/logos/srilankains.png','primary_op' => null, 'bill_ops' => ['134', '36'], 'category' => 'insurance', 'is_bill_only' => true, 'bill_label' => 'Pay premium'],

            // ===== WALLET / DRIVER TOPUPS =====
            (object) ['key' => 'pickme', 'label' => 'PickMe',    'logo' => 'assets/logos/pickme.png',   'primary_op' => null, 'bill_ops' => ['104', '10'], 'category' => 'wallet-topup', 'is_bill_only' => true, 'bill_label' => 'Top up PickMe wallet'],
            (object) ['key' => 'uber',   'label' => 'Uber Eats', 'logo' => 'assets/logos/ubereats.png', 'primary_op' => null, 'bill_ops' => ['105', '40'], 'category' => 'wallet-topup', 'is_bill_only' => true, 'bill_label' => 'Top up Uber Eats'],

        ];

        $viewer = auth()->user();
        $visibleServices = Service::forCustomers()
            ->with(['plans', 'category', 'specialPrices' => fn ($sp) => $sp->where('user_id', $viewer?->id)])
            ->orderBy('name')
            ->get()
            ->each(fn (Service $s) => $s->applyEffectivePricing($viewer));

        $usedOps = collect($groups)->flatMap(function ($g) {
            return array_filter(array_merge(
                [(string) ($g->primary_op ?? '')],
                array_map('strval', $g->fallback_ops ?? []),
                array_map('strval', $g->bill_ops ?? []),
                array_map('strval', $g->other_ops ?? [])
            ));
        })->unique()->all();

        foreach ($visibleServices as $svc) {
            $op = (string) $svc->op_code;
            if (in_array($op, $usedOps, true)) {
                continue;
            }
            $usedOps[] = $op;
            $isDth = strtolower((string) $svc->type) === 'dth' || ($svc->category?->slug === 'dth');
            $billLike = $svc->isBillLike();
            $groups[] = (object) [
                'key'          => ($isDth ? 'dth-' : 'svc-').$svc->id,
                'label'        => $svc->name,
                'logo'         => $svc->logo ?: null,
                'primary_op'   => $op,
                'bill_ops'     => [],
                'other_ops'    => [],
                'category'     => $svc->category?->slug ?: ($isDth ? 'dth' : 'mobile'),
                'is_bill_only' => $billLike && ! in_array(strtolower((string) $svc->type), ['prepaid', 'broadband', 'tv', 'dth'], true),
                'bill_label'   => $billLike ? 'Pay now' : null,
            ];
        }

        $allServices = Service::where('is_active', true)
            ->whereHas('provider', fn ($q) => $q->where('is_active', true))
            ->with(['plans', 'specialPrices' => fn ($sp) => $sp->where('user_id', $viewer?->id)])
            ->get()
            ->each(fn (Service $s) => $s->applyEffectivePricing($viewer))
            ->keyBy(fn (Service $s) => (string) $s->op_code);

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
                    foreach (array_filter(array_merge(
                        [(string) ($g->primary_op ?? '')],
                        array_map('strval', $g->fallback_ops ?? [])
                    )) as $op) {
                        $hit = $allServices->get((string) $op);
                        if ($hit) {
                            $primary = $hit;
                            break;
                        }
                    }
                    // Resolve bill-payment services from every op code in the
                    // group. A brand can offer the SAME payable product under two
                    // providers (e.g. Dialog Postpaid is op 171 on Topup Mart and
                    // op 12 on TMobiling). We must show only ONE "Pay bill" button
                    // per product, otherwise enabling both providers renders a
                    // duplicate CTA. De-dupe by a cross-provider "canonical" op
                    // code so equivalent Topup Mart / TMobiling rows collapse to
                    // one, while genuinely different products in the same group
                    // (e.g. TV Lanka SD vs HD) are both kept. bill_ops are ordered
                    // preferred-provider-first, so the first hit wins.
                    $billServices = collect($g->bill_ops ?? [])
                        ->map(fn ($op) => $allServices->get((string) $op))
                        ->filter()
                        ->unique('id')
                        ->unique(fn (Service $s) => self::canonicalBillKey((string) $s->op_code))
                        ->values();

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
                            // Dialog API plans still show on the Dialog card, but the
                            // customer order is stored as Dialog Prepaid. Sending via
                            // Dialog API happens inside OrderService.
                            $routeSvc = \App\Support\PreferredRoute::faceService($srcSvc);
                            $pl->route_service_id = $routeSvc->id;
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
                return $catGroups->filter(function ($g) {
                    return $g->primary
                        || ($g->billServices && $g->billServices->count());
                })->values();
            });

        // Build a clean list of categories that actually have groups
        $visibleCategories = $categories
            ->filter(fn ($c) => ($groupsByCategory->get($c->slug)?->count() ?? 0) > 0)
            ->map(function ($c) use ($groupsByCategory) {
                $c->groups = $groupsByCategory->get($c->slug, collect());
                return $c;
            })
            ->values();

        $planSlides = \App\Models\Setting::planSlides();

        return view('dashboard.plans', compact('visibleCategories', 'typeMeta', 'planSlides'));
    }

    /**
     * Map a provider op_code to a canonical key so equivalent products from
     * different providers collapse to one bill-payment CTA on the plans page.
     *
     * The same real-world operator has different op codes on Topup Mart vs
     * TMobiling (e.g. Dialog Postpaid = 171 on Topup Mart, 12 on TMobiling).
     * Without this, enabling both providers shows the payment button twice.
     * Any op code not listed maps to itself, so unrelated products stay distinct.
     *
     * @var array<string, string>
     */
    protected const BILL_OP_ALIASES = [
        // Mobile postpaid  (TopupMart => TMobiling)
        '171' => 'dialog-postpaid',  '12' => 'dialog-postpaid',
        '173' => 'mobitel-postpaid', '14' => 'mobitel-postpaid',
        '172' => 'hutch-postpaid',   '15' => 'hutch-postpaid',
        '170' => 'airtel-postpaid',  '13' => 'airtel-postpaid',
        // Dialog TV postpaid
        '191' => 'dialog-tv-postpaid', '16' => 'dialog-tv-postpaid',
        // Electricity / water
        '195' => 'ceb',   '29' => 'ceb',
        '196' => 'leco',  '30' => 'leco',
        '197' => 'nwsdb', '31' => 'nwsdb',
        // Insurance
        '130' => 'aia',      '32' => 'aia',
        '133' => 'hnb-assurance', '68' => 'hnb-assurance',
        '134' => 'sli',      '36' => 'sli',
        // Wallet / driver top-ups
        '104' => 'pickme', '10' => 'pickme',
        '105' => 'uber',   '40' => 'uber',
    ];

    protected static function canonicalBillKey(string $opCode): string
    {
        return self::BILL_OP_ALIASES[$opCode] ?? $opCode;
    }
}
