<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * Seed REAL Sri Lankan operator plans with full meta details
 * (anytime data / night data / voice minutes / SMS / social quota etc.)
 *
 * Sources: dialog.lk, mobitel.lk, hutch.lk, airtel.lk official plan pages
 * (prices & inclusions tax-inclusive, as of current TRC-approved tariff cards).
 */
class PlansSeeder extends Seeder
{
    public function run(): void
    {
        // ===== MOBILE (prepaid) =====
        $this->seedPlans('181', $this->dialogMobilePlans());   // Dialog Prepaid
        $this->seedPlans('183', $this->mobitelMobilePlans()); // Mobitel Prepaid
        $this->seedPlans('182', $this->hutchMobilePlans());   // Hutch Prepaid
        $this->seedPlans('180', $this->airtelMobilePlans());  // Airtel Prepaid

        // Dialog "api" op_code — plain talktime reloads
        $this->seedPlans('921', $this->standardReloadPlans());

        // ===== BROADBAND =====
        $this->seedPlans('102', $this->dialogBroadbandPlans());          // HBB Prepaid
        $this->seedPlans('101', $this->dialogBroadbandPostpaidPlans());  // HBB Postpaid monthly rentals
        $this->seedPlans('103', $this->sltRouterPlans());                // SLT-Mobitel Prepaid 4G Router

        // ===== TV =====
        $this->seedPlans('192', $this->dialogTvPlans()); // Dialog TV Prepaid

        // ===== INDIAN DTH (any active DTH service — HRC codes after import) =====
        $dthOps = Service::where('is_active', true)
            ->where('type', 'dth')
            ->pluck('op_code')
            ->unique()
            ->all();
        foreach ($dthOps as $op) {
            $this->seedPlans((string) $op, $this->indianDthPlaceholderPlans());
        }
    }

    // ============================================================
    // Helper: idempotent upsert — creates plans if missing,
    // updates meta/description/validity/amount/name/sort on existing.
    // ============================================================
    protected function seedPlans(string $opCode, array $plans): void
    {
        $svc = Service::where('op_code', $opCode)->where('is_active', true)->first();
        if (! $svc) return;

        foreach (array_values($plans) as $i => $p) {
            $meta = $p['meta'] ?? [];
            Plan::updateOrCreate(
                ['service_id' => $svc->id, 'name' => $p['name']],
                [
                    'plan_code'   => $p['plan_code'] ?? null,
                    'amount'      => $p['amount'],
                    'validity'    => $p['validity'] ?? null,
                    'description' => $p['description'] ?? null,
                    'type'        => $p['type'],
                    'sort_order'  => $i + 1,
                    'is_active'   => true,
                    'meta'        => !empty($meta) ? ['details' => $meta] : null,
                ]
            );
        }
    }

    protected function row(string $label, string $value, ?string $icon = null): array
    {
        return compact('label', 'value', 'icon');
    }

    // ============================================================
    // Dialog Mobile Prepaid (op 181)
    // ============================================================
    protected function dialogMobilePlans(): array
    {
        $w = 'wifi'; $p = 'phone'; $g = 'grid'; $u = 'users'; $b = 'bolt';
        return [
            // Data
            ['name'=>'59 Data Plan (440MB + 440MB night)','amount'=>59,'type'=>'data','validity'=>'7 Days','meta'=>[
                $this->row('Anytime Data','440 MB',$w),
                $this->row('Night Data (12AM-8AM)','440 MB',$w),
                $this->row('Validity','7 Days','clock'),
            ]],
            ['name'=>'119 Internet Card (880MB + 880MB night)','amount'=>119,'type'=>'data','validity'=>'21 Days','meta'=>[
                $this->row('Anytime Data','880 MB',$w),
                $this->row('Night Data','880 MB',$w),
                $this->row('Validity','21 Days','clock'),
            ]],
            ['name'=>'159 Data Plan (1.5 GB)','amount'=>159,'type'=>'data','validity'=>'3 Days (approx)','meta'=>[
                $this->row('Anytime Data','1.5 GB',$w),$this->row('Validity','3 Days','clock'),
            ]],
            ['name'=>'195 Data Plan (1.95 GB Anytime)','amount'=>195,'type'=>'data','validity'=>'21 Days','meta'=>[
                $this->row('Anytime Data','1.95 GB',$w),$this->row('Validity','21 Days','clock'),
            ]],
            ['name'=>'239 Internet Card (1.92GB + 1.28GB night)','amount'=>239,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','1.92 GB',$w),$this->row('Night Data','1.28 GB',$w),
                $this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'285 Data Plan (3 GB Anytime)','amount'=>285,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','3 GB',$w),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'405 Data Plan (5 GB Anytime)','amount'=>405,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','5 GB',$w),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'715 Data Plan (10 GB Anytime)','amount'=>715,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','10 GB',$w),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'1020 Data Plan (15 GB)','amount'=>1020,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','15 GB',$w),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'1225 Data Plan (20 GB)','amount'=>1225,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','20 GB',$w),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'72 GB 6-Month Plan (12 GB / month)','amount'=>2996,'type'=>'data','validity'=>'6 Months','meta'=>[
                $this->row('Total Data','72 GB (12 GB/month)',$w),
                $this->row('Validity','6 Months','clock'),
            ]],

            // Voice
            ['name'=>'Call Blaster 198 (250 min + 250 SMS)','amount'=>198,'type'=>'voice','validity'=>'30 Days','meta'=>[
                $this->row('Dialog + DBN Minutes','250 min',$p),
                $this->row('SMS','250',$g),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'Call Blaster 298 (400 min + 400 SMS)','amount'=>298,'type'=>'voice','validity'=>'30 Days','meta'=>[
                $this->row('Dialog + DBN Minutes','400 min',$p),
                $this->row('SMS','400',$g),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'Call Blaster 508 (Unlimited Calls)','amount'=>508,'type'=>'voice','validity'=>'30 Days','meta'=>[
                $this->row('Calls','Unlimited Dialog (1000 min other-net)',$p),
                $this->row('SMS','Unlimited Dialog',$g),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'Call Blaster 667 (Unlimited Anynet)','amount'=>667,'type'=>'voice','validity'=>'30 Days','meta'=>[
                $this->row('Calls','Unlimited Any Network',$p),
                $this->row('SMS','Unlimited',$g),$this->row('Validity','30 Days','clock'),
            ]],

            // Combo
            ['name'=>'Triple Blaster 698','amount'=>698,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','6 GB',$w),
                $this->row('Any-Net Calls','1000 min',$p),
                $this->row('SMS','1000',$g),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'Triple Blaster 877 (2000 min + 2000 SMS + 5 GB)','amount'=>877,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','5 GB (DBN)',$w),
                $this->row('Any-Net Calls','2000 min',$p),
                $this->row('SMS','2000 any-net',$g),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'CCTV Plan 1495 (25 GB + Unltd Calls + 1000 SMS)','amount'=>1495,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Data (for CCTV / IOT)','25 GB',$w),
                $this->row('Calls','Unlimited Dialog',$p),
                $this->row('SMS','1000',$g),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'Dialog Ultra 1895 (40 GB + Unltd Calls)','amount'=>1895,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','40 GB (incl. 4G/5G)',$w),
                $this->row('Calls','Unlimited Dialog + 1000 min other-net',$p),
                $this->row('SMS','Unlimited Dialog',$g),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'Dialog Ultra 2595 (75 GB + Unltd Calls)','amount'=>2595,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','75 GB',$w),
                $this->row('Calls','Unlimited Dialog + 1500 min other-net',$p),
                $this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'Dialog Ultra 2995 (100 GB + Unltd Calls)','amount'=>2995,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','100 GB',$w),
                $this->row('Calls','Unlimited Dialog + 2000 min other-net',$p),
                $this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'Dialog Ultra 3999 (200 GB + Unltd Calls)','amount'=>3999,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','200 GB',$w),
                $this->row('Calls','Unlimited Dialog + 3000 min other-net',$p),
                $this->row('Validity','30 Days','clock'),
            ]],

            // Social
            ['name'=>'Unlimited Blaster 399 (Social)','amount'=>399,'type'=>'social','validity'=>'30 Days','meta'=>[
                $this->row('Unlimited Access','WhatsApp, Facebook, Messenger',$u),
                $this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'Unlimited Blaster 999 (FB / WA / YT)','amount'=>999,'type'=>'social','validity'=>'30 Days','meta'=>[
                $this->row('Unlimited','WhatsApp, FB, YouTube',$u),
                $this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'Unlimited Blaster 1249','amount'=>1249,'type'=>'social','validity'=>'30 Days','meta'=>[
                $this->row('Unlimited Social','15 apps (FB, WA, IG, TikTok, YT, etc.)',$u),
                $this->row('Validity','30 Days','clock'),
            ]],

            // Reloads
            ['name'=>'LKR 50 Reload','amount'=>50,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 50 talktime',$b)]],
            ['name'=>'LKR 100 Reload','amount'=>100,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 100 talktime',$b)]],
            ['name'=>'LKR 200 Reload','amount'=>200,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 200 talktime',$b)]],
            ['name'=>'LKR 300 Reload','amount'=>300,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 300 talktime',$b)]],
            ['name'=>'LKR 500 Reload','amount'=>500,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 500 talktime',$b)]],
            ['name'=>'LKR 1000 Reload','amount'=>1000,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 1000 talktime',$b)]],
            ['name'=>'LKR 2000 Reload','amount'=>2000,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 2000 talktime',$b)]],
            ['name'=>'LKR 5000 Reload','amount'=>5000,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 5000 talktime',$b)]],
        ];
    }

    // ============================================================
    // Mobitel Mobile Prepaid (op 183)
    // ============================================================
    protected function mobitelMobilePlans(): array
    {
        $w='wifi';$p='phone';$g='grid';$u='users';$b='bolt';
        return [
            ['name'=>'Internet Chooti 29 (326 MB)','amount'=>29,'type'=>'data','validity'=>'24 Hours','meta'=>[
                $this->row('Anytime Data','326 MB',$w),$this->row('Validity','24 Hours','clock'),
            ]],
            ['name'=>'A49 (750 MB, 375 + 375 4G)','amount'=>49,'type'=>'data','validity'=>'7 Days','meta'=>[
                $this->row('Anytime Data','375 MB',$w),$this->row('4G Bonus Data','375 MB',$w),
                $this->row('Validity','7 Days','clock'),
            ]],
            ['name'=>'A99 (1.5 GB, 768 + 768 4G)','amount'=>99,'type'=>'data','validity'=>'21 Days','meta'=>[
                $this->row('Anytime Data','768 MB',$w),$this->row('4G Bonus Data','768 MB',$w),
                $this->row('Validity','21 Days','clock'),
            ]],
            ['name'=>'A199 (3.4 GB, 1.7 + 1.7 4G)','amount'=>199,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','1.7 GB',$w),$this->row('4G Bonus Data','1.7 GB',$w),
                $this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'239 Anytime (3.4 GB)','amount'=>239,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','3.4 GB',$w),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'299 Anytime (3.5 GB)','amount'=>299,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','3.5 GB',$w),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'389 Anytime (5.6 GB)','amount'=>389,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','5.6 GB',$w),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'399 Anytime (5 GB)','amount'=>399,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','5 GB',$w),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'499 Best Value (7 GB)','amount'=>499,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','7 GB (with Anynet bonus)',$w),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'652 Anytime (8.4 GB)','amount'=>652,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','8.4 GB',$w),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'697 Best Value (10 GB)','amount'=>697,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','10 GB',$w),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'940 Anytime (14 GB)','amount'=>940,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','14 GB',$w),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'999 Best Value (15 GB)','amount'=>999,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','15 GB',$w),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'1343 Anytime (20 GB)','amount'=>1343,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','20 GB',$w),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'60 GB Yearly Pack (5 GB / month)','amount'=>2688,'type'=>'data','validity'=>'365 Days','meta'=>[
                $this->row('Total Data','60 GB (5 GB/month)',$w),$this->row('Validity','365 Days','clock'),
            ]],

            ['name'=>'Nonstop 7-Day 192 (1 GB + 150 min + 3.5 GB)','amount'=>192,'type'=>'combo','validity'=>'7 Days','meta'=>[
                $this->row('Anytime Data','1 GB',$w),$this->row('Nonstop Social/Video','3.5 GB',$u),
                $this->row('Calls','150 min',$p),$this->row('Validity','7 Days','clock'),
            ]],
            ['name'=>'Anytime Combo 249','amount'=>249,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','2 GB',$w),$this->row('Calls','300 min',$p),$this->row('SMS','300',$g),
                $this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'Anytime Combo 333','amount'=>333,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','3 GB',$w),$this->row('Calls','500 min',$p),$this->row('SMS','500',$g),
                $this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'Anytime Combo 444','amount'=>444,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','4 GB',$w),$this->row('Calls','700 min',$p),$this->row('SMS','700',$g),
                $this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'Nonstop 30-Day 646 (5 GB + 750 min + 15 GB)','amount'=>646,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','5 GB',$w),$this->row('Nonstop Social/Video','15 GB',$u),
                $this->row('Calls','750 min any-net',$p),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'9-in-1 Non-Stop Lokka 520','amount'=>520,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','4 GB (DBN)',$w),
                $this->row('Nonstop','9 social/video apps (unlimited)',$u),
                $this->row('Calls','800 min',$p),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'Work & Learn 651 (Non-stop)','amount'=>651,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','5 GB',$w),
                $this->row('Nonstop','Zoom, Teams, YouTube, Social',$u),
                $this->row('Calls','Unlimited Mobitel',$p),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'Work & Learn 1259 (30 GB)','amount'=>1259,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','30 GB',$w),
                $this->row('Nonstop','Zoom, Teams, YouTube, Social',$u),
                $this->row('Calls','Unlimited Mobitel',$p),$this->row('Validity','30 Days','clock'),
            ]],

            ['name'=>'Nonstop TikTok 289','amount'=>289,'type'=>'social','validity'=>'14 Days','meta'=>[
                $this->row('Unlimited','TikTok',$u),$this->row('Validity','14 Days','clock'),
            ]],
            ['name'=>'Nonstop TikTok 899','amount'=>899,'type'=>'social','validity'=>'30 Days','meta'=>[
                $this->row('Unlimited','TikTok',$u),$this->row('Validity','30 Days','clock'),
            ]],

            ['name'=>'LKR 50 Reload','amount'=>50,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 50 talktime',$b)]],
            ['name'=>'LKR 100 Reload','amount'=>100,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 100 talktime',$b)]],
            ['name'=>'LKR 200 Reload','amount'=>200,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 200 talktime',$b)]],
            ['name'=>'LKR 300 Reload','amount'=>300,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 300 talktime',$b)]],
            ['name'=>'LKR 500 Reload','amount'=>500,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 500 talktime',$b)]],
            ['name'=>'LKR 1000 Reload','amount'=>1000,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 1000 talktime',$b)]],
            ['name'=>'LKR 2000 Reload','amount'=>2000,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 2000 talktime',$b)]],
            ['name'=>'LKR 5000 Reload','amount'=>5000,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 5000 talktime',$b)]],
        ];
    }

    // ============================================================
    // Hutch Mobile Prepaid (op 182)
    // ============================================================
    protected function hutchMobilePlans(): array
    {
        $w='wifi';$p='phone';$g='grid';$u='users';$b='bolt';
        return [
            ['name'=>'Anytime 79 (1 GB)','amount'=>79,'type'=>'data','validity'=>'7 Days','meta'=>[
                $this->row('Anytime Data','1 GB',$w),$this->row('Validity','7 Days','clock'),
            ]],
            ['name'=>'Anytime 159 (2 GB)','amount'=>159,'type'=>'data','validity'=>'14 Days','meta'=>[
                $this->row('Anytime Data','2 GB',$w),$this->row('Validity','14 Days','clock'),
            ]],
            ['name'=>'Anytime 285 (4 GB)','amount'=>285,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','4 GB',$w),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'Anytime 405 (5.5 GB)','amount'=>405,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','5.5 GB',$w),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'Anytime 715 (10.5 GB)','amount'=>715,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','10.5 GB',$w),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'Anytime 1021 (15.75 GB)','amount'=>1021,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','15.75 GB',$w),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'Anytime 1228 (21 GB)','amount'=>1228,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','21 GB',$w),$this->row('Validity','30 Days','clock'),
            ]],

            ['name'=>'Combo 358 (2.5 GB + 300 min + Unltd On-net)','amount'=>358,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','2.5 GB',$w),$this->row('On-net Calls','Unlimited Hutch',$p),
                $this->row('Other-net','300 min',$p),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'Combo 399 (4 GB + 400 min + 400 SMS)','amount'=>399,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','4 GB',$w),$this->row('Calls','400 min any-net',$p),
                $this->row('SMS','400 any-net',$g),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'Combo 599 (6 GB + 600 min + 600 SMS)','amount'=>599,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','6 GB',$w),$this->row('Calls','600 min any-net',$p),
                $this->row('SMS','600 any-net',$g),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'Combo 999 (10 GB + 1000 min + 1000 SMS)','amount'=>999,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','10 GB',$w),$this->row('Calls','1000 min any-net',$p),
                $this->row('SMS','1000 any-net',$g),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'Combo 1499 (15 GB + 1500 min + 1500 SMS)','amount'=>1499,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','15 GB',$w),$this->row('Calls','1500 min any-net',$p),
                $this->row('SMS','1500 any-net',$g),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'Drive 500 (5 GB + 500 SMS + App Quota)','amount'=>500,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','5 GB',$w),$this->row('Rideshare App Quota','Unlimited (PickMe/Uber)',$u),
                $this->row('SMS','500',$g),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'Hutch-15 Weekly 399 (9 GB + Unltd Calls)','amount'=>399,'type'=>'combo','validity'=>'7 Days','meta'=>[
                $this->row('Anytime Data','9 GB (15-app nonstop)',$w),
                $this->row('Calls','Unlimited Hutch',$p),$this->row('Validity','7 Days','clock'),
            ]],
            ['name'=>'Hutch-15 Monthly 1199 (35 GB + Unltd Calls + Rs.100 Freeload)','amount'=>1199,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','35 GB (15-app nonstop)',$w),
                $this->row('Calls','Unlimited Hutch',$p),
                $this->row('Freeload Bonus','Rs. 100',$b),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'Ultimate Weekly 619 (25 GB FUP + Unltd Calls + 125 SMS)','amount'=>619,'type'=>'combo','validity'=>'7 Days','meta'=>[
                $this->row('Data','25 GB (FUP, then unlimited 1 Mbps)',$w),
                $this->row('Calls','Unlimited Any-net',$p),$this->row('SMS','125',$g),
                $this->row('Validity','7 Days','clock'),
            ]],
            ['name'=>'Ultimate Monthly 1999 (100 GB FUP + Unltd Calls + 500 SMS + Rs.100 Freeload)','amount'=>1999,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Data','100 GB (FUP, then unlimited 1 Mbps)',$w),
                $this->row('Calls','Unlimited Any-net',$p),$this->row('SMS','500',$g),
                $this->row('Freeload Bonus','Rs. 100',$b),$this->row('Validity','30 Days','clock'),
            ]],

            ['name'=>'Social 54 (Unlimited FB / WA / Messenger)','amount'=>54,'type'=>'social','validity'=>'7 Days','meta'=>[
                $this->row('Unlimited','Facebook, WhatsApp, Messenger',$u),
                $this->row('Validity','7 Days','clock'),
            ]],
            ['name'=>'Social 124 (Unlimited FB / WA / Messenger)','amount'=>124,'type'=>'social','validity'=>'30 Days','meta'=>[
                $this->row('Unlimited','Facebook, WhatsApp, Messenger',$u),
                $this->row('Validity','30 Days','clock'),
            ]],

            ['name'=>'LKR 50 Reload','amount'=>50,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 50 talktime',$b)]],
            ['name'=>'LKR 100 Reload','amount'=>100,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 100 talktime',$b)]],
            ['name'=>'LKR 200 Reload','amount'=>200,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 200 talktime',$b)]],
            ['name'=>'LKR 300 Reload','amount'=>300,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 300 talktime',$b)]],
            ['name'=>'LKR 500 Reload','amount'=>500,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 500 talktime',$b)]],
            ['name'=>'LKR 1000 Reload','amount'=>1000,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 1000 talktime',$b)]],
            ['name'=>'LKR 2000 Reload','amount'=>2000,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 2000 talktime',$b)]],
            ['name'=>'LKR 5000 Reload','amount'=>5000,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 5000 talktime',$b)]],
        ];
    }

    // ============================================================
    // Airtel Mobile Prepaid (op 180)
    // ============================================================
    protected function airtelMobilePlans(): array
    {
        $w='wifi';$p='phone';$g='grid';$u='users';$b='bolt';
        return [
            ['name'=>'Data 79 (1 GB 4G)','amount'=>79,'type'=>'data','validity'=>'7 Days','meta'=>[
                $this->row('Anytime Data','1 GB 4G',$w),$this->row('Validity','7 Days','clock'),
            ]],
            ['name'=>'Data 99 (1 GB 4G)','amount'=>99,'type'=>'data','validity'=>'14 Days','meta'=>[
                $this->row('Anytime Data','1 GB 4G',$w),$this->row('Validity','14 Days','clock'),
            ]],
            ['name'=>'Data 249 (3 GB 4G)','amount'=>249,'type'=>'data','validity'=>'21 Days','meta'=>[
                $this->row('Anytime Data','3 GB 4G',$w),$this->row('Validity','21 Days','clock'),
            ]],
            ['name'=>'Data 255 (4 GB 4G)','amount'=>255,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','4 GB 4G',$w),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'Data 405 (5 GB)','amount'=>405,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','5 GB',$w),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'Data 409 (7 GB 4G)','amount'=>409,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','7 GB 4G',$w),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'Data 1225 (20 GB 4G)','amount'=>1225,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','20 GB 4G/5G',$w),$this->row('Validity','30 Days','clock'),
            ]],

            ['name'=>'Voice 98 (150 min + 150 SMS)','amount'=>98,'type'=>'voice','validity'=>'14 Days','meta'=>[
                $this->row('Any-Net Calls','150 min',$p),$this->row('SMS','150 any-net',$g),
                $this->row('Validity','14 Days','clock'),
            ]],
            ['name'=>'Unlimited Voice 548','amount'=>548,'type'=>'voice','validity'=>'28 Days','meta'=>[
                $this->row('On-net Calls','Unlimited Airtel',$p),
                $this->row('Other-net','200 min',$p),$this->row('Validity','28 Days','clock'),
            ]],
            ['name'=>'Unlimited Voice 769','amount'=>769,'type'=>'voice','validity'=>'30 Days','meta'=>[
                $this->row('Calls','Unlimited Airtel + 500 min other-net',$p),
                $this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'Unlimited Voice 1249','amount'=>1249,'type'=>'voice','validity'=>'30 Days','meta'=>[
                $this->row('Calls','Unlimited Any Network',$p),$this->row('Validity','30 Days','clock'),
            ]],

            ['name'=>'Social 174 (1 GB + Unlimited Social Weekly)','amount'=>174,'type'=>'social','validity'=>'7 Days','meta'=>[
                $this->row('Anytime Data','1 GB',$w),
                $this->row('Unlimited Social','FB, WA, IG, TikTok, YouTube',$u),
                $this->row('Validity','7 Days','clock'),
            ]],
            ['name'=>'Social 277 (10 GB Reload)','amount'=>277,'type'=>'social','validity'=>'28 Days','meta'=>[
                $this->row('Social Data','10 GB (YouTube/FB/WA/IG)',$u),
                $this->row('Validity','28 Days','clock'),
            ]],
            ['name'=>'Social 498 (Unlimited FB / WA / YT)','amount'=>498,'type'=>'social','validity'=>'28 Days','meta'=>[
                $this->row('Unlimited','FB, WA, YouTube + 3 GB anytime',$u),
                $this->row('Validity','28 Days','clock'),
            ]],

            ['name'=>'Youth 988 (12 GB + Unltd Calls + Unltd Social)','amount'=>988,'type'=>'combo','validity'=>'28 Days','meta'=>[
                $this->row('Anytime Data','12 GB 4G/5G',$w),
                $this->row('Calls','Unlimited Any-net',$p),
                $this->row('Unlimited Social','15 apps',$u),$this->row('Validity','28 Days','clock'),
            ]],
            ['name'=>'Youth 1188 (12 GB + Unltd Calls + Unltd Social 15-apps)','amount'=>1188,'type'=>'combo','validity'=>'28 Days','meta'=>[
                $this->row('Anytime Data','12 GB 4G/5G',$w),$this->row('Calls','Unltd Any-net',$p),
                $this->row('Unlimited','15 social apps',$u),$this->row('Validity','28 Days','clock'),
            ]],
            ['name'=>'Freedom Plus 909 (1 GB + Unltd Social + 1000 SMS)','amount'=>909,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','1 GB',$w),$this->row('Unlimited Social','Yes',$u),
                $this->row('SMS','1000 any-net',$g),$this->row('Calls','200 min',$p),
                $this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'Youth 2595 (75 GB 5G + Unltd Calls)','amount'=>2595,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','75 GB 5G',$w),$this->row('Calls','Unltd Any-net',$p),
                $this->row('Unlimited Social','Yes',$u),$this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'Youth 2995 (100 GB 5G + Unltd Calls)','amount'=>2995,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','100 GB 5G',$w),$this->row('Calls','Unltd Any-net',$p),
                $this->row('Validity','30 Days','clock'),
            ]],
            ['name'=>'Youth 3999 (200 GB 5G + Unltd Calls)','amount'=>3999,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','200 GB 5G',$w),$this->row('Calls','Unltd Any-net',$p),
                $this->row('Validity','30 Days','clock'),
            ]],

            ['name'=>'LKR 50 Reload','amount'=>50,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 50 talktime',$b)]],
            ['name'=>'LKR 100 Reload','amount'=>100,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 100 talktime',$b)]],
            ['name'=>'LKR 200 Reload','amount'=>200,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 200 talktime',$b)]],
            ['name'=>'LKR 300 Reload','amount'=>300,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 300 talktime',$b)]],
            ['name'=>'LKR 500 Reload','amount'=>500,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 500 talktime',$b)]],
            ['name'=>'LKR 1000 Reload','amount'=>1000,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 1000 talktime',$b)]],
            ['name'=>'LKR 2000 Reload','amount'=>2000,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 2000 talktime',$b)]],
            ['name'=>'LKR 5000 Reload','amount'=>5000,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 5000 talktime',$b)]],
        ];
    }

    // ============================================================
    // Dialog Home Broadband Prepaid (op 102)
    // ============================================================
    protected function dialogBroadbandPlans(): array
    {
        $w='wifi';$c='clock';$u='users';$g='grid';$b='bolt';
        return [
            // Home Wi-Fi Reload Data Plans
            ['name'=>'10GB Super Saver (14 Days)','amount'=>307,'type'=>'data','validity'=>'14 Days','meta'=>[
                $this->row('Anytime Data','10 GB',$w),$this->row('Validity','14 Days',$c),
            ]],
            ['name'=>'20GB Super Saver (30 Days)','amount'=>555,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','20 GB',$w),$this->row('Validity','30 Days',$c),
            ]],
            ['name'=>'30GB Anytime Plan','amount'=>699,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','30 GB',$w),$this->row('Validity','30 Days',$c),
            ]],
            ['name'=>'Home Wi-Fi Social Plan (20GB + 20GB Social)','amount'=>760,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','20 GB',$w),$this->row('Social Data','20 GB (YT/FB/WA/IG)',$u),
                $this->row('Validity','30 Days',$c),
            ]],
            ['name'=>'Home Wi-Fi YouTube Plan (20GB + 30GB YouTube)','amount'=>860,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','20 GB',$w),$this->row('YouTube Data','30 GB',$u),
                $this->row('Validity','30 Days',$c),
            ]],
            ['name'=>'45GB Home Wi-Fi Plan','amount'=>999,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','45 GB',$w),$this->row('Validity','30 Days',$c),
            ]],
            ['name'=>'70GB Home Wi-Fi Plan','amount'=>1499,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','70 GB',$w),$this->row('Validity','30 Days',$c),
            ]],

            // Anytime Add-ons
            ['name'=>'2GB Anytime Add-on (1 Day)','amount'=>72,'type'=>'data','validity'=>'1 Day','meta'=>[$this->row('Data','2 GB anytime',$w),$this->row('Validity','1 Day',$c)]],
            ['name'=>'2GB Anytime Add-on (7 Days)','amount'=>96,'type'=>'data','validity'=>'7 Days','meta'=>[$this->row('Data','2 GB anytime',$w),$this->row('Validity','7 Days',$c)]],
            ['name'=>'2GB Anytime Add-on (30 Days)','amount'=>120,'type'=>'data','validity'=>'30 Days','meta'=>[$this->row('Data','2 GB anytime',$w),$this->row('Validity','30 Days',$c)]],
            ['name'=>'4GB Anytime Add-on (1 Day)','amount'=>120,'type'=>'data','validity'=>'1 Day','meta'=>[$this->row('Data','4 GB anytime',$w),$this->row('Validity','1 Day',$c)]],
            ['name'=>'4GB Anytime Add-on (7 Days)','amount'=>166,'type'=>'data','validity'=>'7 Days','meta'=>[$this->row('Data','4 GB anytime',$w),$this->row('Validity','7 Days',$c)]],
            ['name'=>'4GB Anytime Add-on (30 Days)','amount'=>220,'type'=>'data','validity'=>'30 Days','meta'=>[$this->row('Data','4 GB anytime',$w),$this->row('Validity','30 Days',$c)]],
            ['name'=>'10GB Anytime Add-on (1 Day)','amount'=>270,'type'=>'data','validity'=>'1 Day','meta'=>[$this->row('Data','10 GB anytime',$w),$this->row('Validity','1 Day',$c)]],
            ['name'=>'10GB Anytime Add-on (7 Days)','amount'=>386,'type'=>'data','validity'=>'7 Days','meta'=>[$this->row('Data','10 GB anytime',$w),$this->row('Validity','7 Days',$c)]],
            ['name'=>'10GB Anytime Add-on (30 Days)','amount'=>480,'type'=>'data','validity'=>'30 Days','meta'=>[$this->row('Data','10 GB anytime',$w),$this->row('Validity','30 Days',$c)]],
            ['name'=>'20GB Anytime Add-on (1 Day)','amount'=>420,'type'=>'data','validity'=>'1 Day','meta'=>[$this->row('Data','20 GB anytime',$w),$this->row('Validity','1 Day',$c)]],
            ['name'=>'20GB Anytime Add-on (7 Days)','amount'=>626,'type'=>'data','validity'=>'7 Days','meta'=>[$this->row('Data','20 GB anytime',$w),$this->row('Validity','7 Days',$c)]],
            ['name'=>'20GB Anytime Add-on (30 Days)','amount'=>840,'type'=>'data','validity'=>'30 Days','meta'=>[$this->row('Data','20 GB anytime',$w),$this->row('Validity','30 Days',$c)]],
            ['name'=>'30GB Anytime Add-on (1 Day)','amount'=>540,'type'=>'data','validity'=>'1 Day','meta'=>[$this->row('Data','30 GB anytime',$w),$this->row('Validity','1 Day',$c)]],
            ['name'=>'30GB Anytime Add-on (7 Days)','amount'=>846,'type'=>'data','validity'=>'7 Days','meta'=>[$this->row('Data','30 GB anytime',$w),$this->row('Validity','7 Days',$c)]],
            ['name'=>'30GB Anytime Add-on (30 Days)','amount'=>1140,'type'=>'data','validity'=>'30 Days','meta'=>[$this->row('Data','30 GB anytime',$w),$this->row('Validity','30 Days',$c)]],
            ['name'=>'50GB Anytime Add-on (1 Day)','amount'=>720,'type'=>'data','validity'=>'1 Day','meta'=>[$this->row('Data','50 GB anytime',$w),$this->row('Validity','1 Day',$c)]],
            ['name'=>'50GB Anytime Add-on (7 Days)','amount'=>1206,'type'=>'data','validity'=>'7 Days','meta'=>[$this->row('Data','50 GB anytime',$w),$this->row('Validity','7 Days',$c)]],
            ['name'=>'50GB Anytime Add-on (30 Days)','amount'=>1680,'type'=>'data','validity'=>'30 Days','meta'=>[$this->row('Data','50 GB anytime',$w),$this->row('Validity','30 Days',$c)]],
            ['name'=>'100GB Anytime Add-on (30 Days)','amount'=>3120,'type'=>'data','validity'=>'30 Days','meta'=>[$this->row('Data','100 GB anytime',$w),$this->row('Validity','30 Days',$c)]],
            ['name'=>'200GB Anytime Add-on (30 Days)','amount'=>6000,'type'=>'data','validity'=>'30 Days','meta'=>[$this->row('Data','200 GB anytime',$w),$this->row('Validity','30 Days',$c)]],

            // Night
            ['name'=>'20GB Night Time Add-on','amount'=>405,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Night Data (12AM-8AM)','20 GB',$w),$this->row('Validity','30 Days',$c),
            ]],
            ['name'=>'100GB Night Time Add-on','amount'=>1347,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Night Data (12AM-8AM)','100 GB',$w),$this->row('Validity','30 Days',$c),
            ]],

            // Social
            ['name'=>'Home Social 20GB (14 Days) — YouTube/FB/WA/IG/Viber','amount'=>318,'type'=>'social','validity'=>'14 Days','meta'=>[
                $this->row('Social Data','20 GB (YT/FB/WA/IG/Viber)',$u),$this->row('Validity','14 Days',$c),
            ]],
            ['name'=>'Home Social 40GB (30 Days) — YouTube/FB/WA/IG/Viber','amount'=>590,'type'=>'social','validity'=>'30 Days','meta'=>[
                $this->row('Social Data','40 GB (YT/FB/WA/IG/Viber)',$u),$this->row('Validity','30 Days',$c),
            ]],

            // Work & Learn
            ['name'=>'Work & Learn Lite 30GB (Zoom/O365)','amount'=>298,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Data (Zoom/Teams/O365)','30 GB',$g),$this->row('Validity','30 Days',$c),
            ]],
            ['name'=>'Work & Learn Lite Unlimited (Zoom/O365)','amount'=>724,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Data (Zoom/Teams/O365)','Unlimited (FUP 2 Mbps)',$g),
                $this->row('Validity','30 Days',$c),
            ]],
            ['name'=>'Work & Learn Plus 35GB (Zoom/Google/Office/YouTube/Edu)','amount'=>672,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Data','35 GB (edu & productivity apps)',$g),$this->row('Validity','30 Days',$c),
            ]],
            ['name'=>'Work & Learn Plus 80GB (Zoom/Google/Office/YouTube/Edu)','amount'=>1342,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Data','80 GB (edu & productivity apps)',$g),$this->row('Validity','30 Days',$c),
            ]],

            // Unlimited
            ['name'=>'Unlimited 2Mbps One-time','amount'=>260,'type'=>'combo','validity'=>'1 Day','meta'=>[
                $this->row('Speed','Up to 2 Mbps',$w),$this->row('Data','Unlimited (one-time)',$w),
                $this->row('Validity','1 Day',$c),
            ]],
            ['name'=>'Unlimited 4Mbps One-time','amount'=>482,'type'=>'combo','validity'=>'1 Day','meta'=>[
                $this->row('Speed','Up to 4 Mbps',$w),$this->row('Data','Unlimited (one-time)',$w),
                $this->row('Validity','1 Day',$c),
            ]],
            ['name'=>'Unlimited 8Mbps One-time','amount'=>1563,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Speed','Up to 8 Mbps',$w),$this->row('Data','Unlimited (one-time)',$w),
                $this->row('Validity','30 Days',$c),
            ]],
            ['name'=>'Unlimited 2Mbps Recurrent','amount'=>2685,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Speed','Up to 2 Mbps (recurrent)',$w),$this->row('Data','Unlimited',$w),
                $this->row('Validity','30 Days, auto-renews',$c),
            ]],
            ['name'=>'Unlimited 4Mbps Recurrent','amount'=>5927,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Speed','Up to 4 Mbps (recurrent)',$w),$this->row('Data','Unlimited',$w),
                $this->row('Validity','30 Days, auto-renews',$c),
            ]],
            ['name'=>'Unlimited 8Mbps Recurrent','amount'=>8845,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Speed','Up to 8 Mbps (recurrent)',$w),$this->row('Data','Unlimited',$w),
                $this->row('Validity','30 Days, auto-renews',$c),
            ]],

            // Data top-ups
            ['name'=>'LKR 250 Data Top-up','amount'=>250,'type'=>'reload','validity'=>null,'meta'=>[$this->row('Account credit','LKR 250 added to router balance',$b)]],
            ['name'=>'LKR 500 Data Top-up','amount'=>500,'type'=>'reload','validity'=>null,'meta'=>[$this->row('Account credit','LKR 500 added to router balance',$b)]],
            ['name'=>'LKR 1000 Data Top-up','amount'=>1000,'type'=>'reload','validity'=>null,'meta'=>[$this->row('Account credit','LKR 1000 added to router balance',$b)]],
            ['name'=>'LKR 2000 Data Top-up','amount'=>2000,'type'=>'reload','validity'=>null,'meta'=>[$this->row('Account credit','LKR 2000 added to router balance',$b)]],
            ['name'=>'LKR 5000 Data Top-up','amount'=>5000,'type'=>'reload','validity'=>null,'meta'=>[$this->row('Account credit','LKR 5000 added to router balance',$b)]],
        ];
    }

    // ============================================================
    // Dialog Home Broadband Postpaid (op 101) — monthly rentals
    // ============================================================
    protected function dialogBroadbandPostpaidPlans(): array
    {
        $w='wifi';$g='grid';$c='clock';
        return [
            ['name'=>'Home Wi-Fi 65GB (Monthly Rental)','amount'=>1593,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Inclusive Data','65 GB (rollover unused)',$w),
                $this->row('Taxes','Inclusive in rental','grid'),
                $this->row('Billing','Postpaid monthly rental',$c),
            ]],
            ['name'=>'Home Wi-Fi 100GB (Monthly Rental)','amount'=>2334,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Inclusive Data','100 GB (rollover unused)',$w),
                $this->row('Taxes','Inclusive in rental','grid'),
                $this->row('Billing','Postpaid monthly rental',$c),
            ]],
            ['name'=>'Home Wi-Fi 140GB (Monthly Rental)','amount'=>3075,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Inclusive Data','140 GB (rollover unused)',$w),
                $this->row('Taxes','Inclusive in rental','grid'),
                $this->row('Billing','Postpaid monthly rental',$c),
            ]],
            ['name'=>'Home Wi-Fi 175GB (Monthly Rental)','amount'=>3693,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Inclusive Data','175 GB (rollover unused)',$w),
                $this->row('Taxes','Inclusive in rental','grid'),
                $this->row('Billing','Postpaid monthly rental',$c),
            ]],
            ['name'=>'Unlimited 2 Mbps (Monthly Rental)','amount'=>2685,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Speed','Up to 2 Mbps (FUP)',$w),
                $this->row('Data','Unlimited',$w),
                $this->row('Taxes','Inclusive in rental','grid'),
                $this->row('Billing','Postpaid monthly rental',$c),
            ]],
            ['name'=>'Unlimited up to 10 Mbps (Monthly Rental)','amount'=>4310,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('Speed','Up to 10 Mbps (FUP)',$w),
                $this->row('Data','Unlimited',$w),
                $this->row('Taxes','Inclusive in rental','grid'),
                $this->row('Billing','Postpaid monthly rental',$c),
            ]],
        ];
    }

    // ============================================================
    // SLT-Mobitel Prepaid 4G Router (op 103)
    // ============================================================
    protected function sltRouterPlans(): array
    {
        $w='wifi';$g='grid';$u='users';$b='bolt';$c='clock';
        return [
            ['name'=>'5GB Anytime (7 Days)','amount'=>155,'type'=>'data','validity'=>'7 Days','meta'=>[
                $this->row('Anytime Data','5 GB',$w),$this->row('Network','SLT-Mobitel 4G','grid'),
                $this->row('Validity','7 Days',$c),
            ]],
            ['name'=>'10GB Anytime (14 Days)','amount'=>295,'type'=>'data','validity'=>'14 Days','meta'=>[
                $this->row('Anytime Data','10 GB',$w),$this->row('Network','SLT-Mobitel 4G','grid'),
                $this->row('Validity','14 Days',$c),
            ]],
            ['name'=>'20GB Anytime (30 Days)','amount'=>535,'type'=>'data','validity'=>'30 Days','meta'=>[
                $this->row('Anytime Data','20 GB',$w),$this->row('Network','SLT-Mobitel 4G','grid'),
                $this->row('Validity','30 Days',$c),
            ]],
            ['name'=>'Zoom / Teams Add-on (30GB)','amount'=>235,'type'=>'combo','validity'=>'30 Days','meta'=>[
                $this->row('App Data','30 GB (Zoom, Microsoft Teams)',$g),
                $this->row('Validity','30 Days',$c),
            ]],
            ['name'=>'Social Add-on 25GB (YouTube/FB/WA/IG/TikTok)','amount'=>390,'type'=>'social','validity'=>'30 Days','meta'=>[
                $this->row('Social Data','25 GB',$u),$this->row('Apps','YouTube, FB, WA, IG, TikTok',$u),
                $this->row('Validity','30 Days',$c),
            ]],
            ['name'=>'LKR 250 Data Top-up','amount'=>250,'type'=>'reload','validity'=>null,'meta'=>[$this->row('Account credit','LKR 250 added to router balance',$b)]],
            ['name'=>'LKR 500 Data Top-up','amount'=>500,'type'=>'reload','validity'=>null,'meta'=>[$this->row('Account credit','LKR 500 added to router balance',$b)]],
            ['name'=>'LKR 1000 Data Top-up','amount'=>1000,'type'=>'reload','validity'=>null,'meta'=>[$this->row('Account credit','LKR 1000 added to router balance',$b)]],
            ['name'=>'LKR 2000 Data Top-up','amount'=>2000,'type'=>'reload','validity'=>null,'meta'=>[$this->row('Account credit','LKR 2000 added to router balance',$b)]],
            ['name'=>'LKR 5000 Data Top-up','amount'=>5000,'type'=>'reload','validity'=>null,'meta'=>[$this->row('Account credit','LKR 5000 added to router balance',$b)]],
        ];
    }

    // ============================================================
    // Dialog TV Prepaid (op 192)
    // ============================================================
    protected function dialogTvPlans(): array
    {
        $tv='tv-card';$c='clock';
        return [
            ['name'=>'LKR 250 Channel Pack','amount'=>250,'type'=>'tv','validity'=>'30 Days','meta'=>[
                $this->row('Pack','Channel pack (Rupavahini / basic tier)',$tv),
                $this->row('Validity','30 Days',$c),
            ]],
            ['name'=>'LKR 500 Monthly Reload','amount'=>500,'type'=>'tv','validity'=>'30 Days','meta'=>[
                $this->row('Account credit','LKR 500 (pay-per-view / channel top-ups)',$tv),
                $this->row('Validity','30 Days',$c),
            ]],
            ['name'=>'Rs.561 HD Prepaid Package','amount'=>561,'type'=>'tv','validity'=>'30 Days','meta'=>[
                $this->row('Pack','HD channels prepaid package',$tv),$this->row('Validity','30 Days',$c),
            ]],
            ['name'=>'LKR 1000 Channel Pack','amount'=>1000,'type'=>'tv','validity'=>'30 Days','meta'=>[
                $this->row('Pack','Premium channel pack',$tv),$this->row('Validity','30 Days',$c),
            ]],
            ['name'=>'LKR 1560 Diamond Package','amount'=>1560,'type'=>'tv','validity'=>'30 Days','meta'=>[
                $this->row('Pack','Diamond (full sports + HD)',$tv),$this->row('Validity','30 Days',$c),
            ]],
            ['name'=>'LKR 2099 Gold Package','amount'=>2099,'type'=>'tv','validity'=>'30 Days','meta'=>[
                $this->row('Pack','Gold (premium + movies + sports)',$tv),$this->row('Validity','30 Days',$c),
            ]],
        ];
    }

    // ============================================================
    // Indian DTH placeholder
    // ============================================================
    protected function indianDthPlaceholderPlans(): array
    {
        $tv='tv-card';
        return [
            ['name'=>'LKR 500 Recharge','amount'=>500,'type'=>'tv','validity'=>null,'meta'=>[$this->row('Account credit','LKR 500',$tv)]],
            ['name'=>'LKR 1000 Recharge','amount'=>1000,'type'=>'tv','validity'=>null,'meta'=>[$this->row('Account credit','LKR 1000',$tv)]],
            ['name'=>'LKR 2000 Recharge','amount'=>2000,'type'=>'tv','validity'=>null,'meta'=>[$this->row('Account credit','LKR 2000',$tv)]],
            ['name'=>'LKR 5000 Recharge','amount'=>5000,'type'=>'tv','validity'=>null,'meta'=>[$this->row('Account credit','LKR 5000',$tv)]],
        ];
    }

    // ============================================================
    // Generic talktime reloads (op 921)
    // ============================================================
    protected function standardReloadPlans(): array
    {
        $b='bolt';
        return [
            ['name'=>'LKR 50 Reload','amount'=>50,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 50 talktime',$b)]],
            ['name'=>'LKR 100 Reload','amount'=>100,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 100 talktime',$b)]],
            ['name'=>'LKR 200 Reload','amount'=>200,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 200 talktime',$b)]],
            ['name'=>'LKR 300 Reload','amount'=>300,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 300 talktime',$b)]],
            ['name'=>'LKR 500 Reload','amount'=>500,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 500 talktime',$b)]],
            ['name'=>'LKR 1000 Reload','amount'=>1000,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 1000 talktime',$b)]],
            ['name'=>'LKR 2000 Reload','amount'=>2000,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 2000 talktime',$b)]],
            ['name'=>'LKR 5000 Reload','amount'=>5000,'type'=>'reload','validity'=>'Standard','meta'=>[$this->row('Credit added','LKR 5000 talktime',$b)]],
        ];
    }
}
