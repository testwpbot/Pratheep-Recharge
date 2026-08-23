<?php

namespace App\Services\Providers;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Topup Mart (Sri Lanka) provider integration.
 *
 * Notes:
 *  - The recharge endpoint can take 30-60 seconds to respond while carrier
 *    retries happen. We use a generous timeout (60s connect + 90s total) and
 *    rely on the cron to reconcile any order that still times out.
 *  - We pass a unique client_ref (our order reference) so the provider can
 *    de-duplicate accidental retries, and so their /status.php endpoint can
 *    look up by our reference instead of their txn id (critical for
 *    reconciling timeouts when we never got a transaction_id back).
 */
class TopupMart implements ProviderInterface
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct(?string $baseUrl = null, ?string $apiKey = null)
    {
        $this->baseUrl = rtrim($baseUrl ?? config('services.topup_mart.base_url', 'https://topupmart.online/api/v2'), '/');
        $this->apiKey  = (string) ($apiKey ?? config('services.topup_mart.api_key', ''));
    }

    /* ---------- catalog (used by admin "Import" button) ---------- */
    public function fetchServices(): array
    {
        return [
            ['op_code' => '921', 'name' => 'Dialog',          'type' => 'api',     'category_slug' => 'mobile',     'logo' => 'assets/logos/dialog.png'],
            ['op_code' => '922', 'name' => 'Dialog HBB',      'type' => 'api',     'category_slug' => 'broadband',  'logo' => 'assets/logos/dialog.png'],
            ['op_code' => '923', 'name' => 'Dialog TV',       'type' => 'api',     'category_slug' => 'tv',         'logo' => 'assets/logos/dialog.png'],

            ['op_code' => '101', 'name' => 'Dialog 4G Router',      'type' => 'broadband', 'category_slug' => 'broadband', 'logo' => 'assets/logos/dialog.png'],
            ['op_code' => '102', 'name' => 'Dialog HBB Prepaid',    'type' => 'broadband', 'category_slug' => 'broadband', 'logo' => 'assets/logos/dialog.png'],
            ['op_code' => '103', 'name' => 'SLT Prepaid Router',    'type' => 'broadband', 'category_slug' => 'broadband', 'logo' => 'assets/logos/sltmobitel.png'],

            ['op_code' => '180', 'name' => 'Airtel Prepaid',  'type' => 'prepaid', 'category_slug' => 'mobile', 'logo' => 'assets/logos/airtel.png'],
            ['op_code' => '181', 'name' => 'Dialog Prepaid',  'type' => 'prepaid', 'category_slug' => 'mobile', 'logo' => 'assets/logos/dialog.png'],
            ['op_code' => '183', 'name' => 'Mobitel Prepaid', 'type' => 'prepaid', 'category_slug' => 'mobile', 'logo' => 'assets/logos/sltmobitel.png'],
            ['op_code' => '182', 'name' => 'Hutch Prepaid',   'type' => 'prepaid', 'category_slug' => 'mobile', 'logo' => 'assets/logos/hutch.png'],

            ['op_code' => '170', 'name' => 'Airtel Postpaid',  'type' => 'postpaid', 'category_slug' => 'mobile', 'logo' => 'assets/logos/airtel.png'],
            ['op_code' => '171', 'name' => 'Dialog Postpaid',  'type' => 'postpaid', 'category_slug' => 'mobile', 'logo' => 'assets/logos/dialog.png'],
            ['op_code' => '172', 'name' => 'Hutch Postpaid',   'type' => 'postpaid', 'category_slug' => 'mobile', 'logo' => 'assets/logos/hutch.png'],
            ['op_code' => '173', 'name' => 'Mobitel Postpaid', 'type' => 'postpaid', 'category_slug' => 'mobile', 'logo' => 'assets/logos/sltmobitel.png'],

            ['op_code' => '193', 'name' => 'TV Lanka',    'type' => 'tv', 'category_slug' => 'tv', 'logo' => 'assets/logos/tvlanka.png'],
            ['op_code' => '194', 'name' => 'TV Lanka HD', 'type' => 'tv', 'category_slug' => 'tv', 'logo' => 'assets/logos/tvlanka.png'],

            ['op_code' => '195', 'name' => 'CEB Electricity', 'type' => 'utility', 'category_slug' => 'utility', 'logo' => 'assets/logos/ceb.png'],
            ['op_code' => '196', 'name' => 'LECO Electricity','type' => 'utility', 'category_slug' => 'utility', 'logo' => 'assets/logos/leco.png'],
            ['op_code' => '197', 'name' => 'Water (NWSDB)',   'type' => 'utility', 'category_slug' => 'utility', 'logo' => 'assets/logos/nwsdb.png'],
            ['op_code' => '198', 'name' => 'SLT Bill',        'type' => 'utility', 'category_slug' => 'utility', 'logo' => 'assets/logos/sltmobitel.png'],

            ['op_code' => '192', 'name' => 'Dialog TV Prepaid',  'type' => 'tv', 'category_slug' => 'tv', 'logo' => 'assets/logos/dialog.png'],
            ['op_code' => '191', 'name' => 'Dialog TV Postpaid', 'type' => 'tv', 'category_slug' => 'tv', 'logo' => 'assets/logos/dialog.png'],
            ['op_code' => '190', 'name' => 'Ask Cable Vision',   'type' => 'tv', 'category_slug' => 'tv', 'logo' => 'assets/logos/askcable.png'],

            ['op_code' => '130', 'name' => 'AIA Life',           'type' => 'insurance', 'category_slug' => 'insurance', 'logo' => 'assets/logos/aia.png'],
            ['op_code' => '131', 'name' => 'Arpico Insurance',   'type' => 'insurance', 'category_slug' => 'insurance', 'logo' => 'assets/logos/arpico.png'],
            ['op_code' => '132', 'name' => 'Ceylinco Life',      'type' => 'insurance', 'category_slug' => 'insurance', 'logo' => 'assets/logos/ceylinco.png'],
            ['op_code' => '133', 'name' => 'HNB Assurance',      'type' => 'insurance', 'category_slug' => 'insurance', 'logo' => 'assets/logos/hnbassu.png'],
            ['op_code' => '134', 'name' => 'Sri Lanka Insurance','type' => 'insurance', 'category_slug' => 'insurance', 'logo' => 'assets/logos/srilankains.png'],

            // DTH is routed through Happy Recharge Center. These rows exist only
            // so admin failover can re-send a stuck HRC order via Topup Mart.
            ['op_code' => '120', 'name' => 'Airtel DTH',   'type' => 'dth', 'category_slug' => 'dth', 'logo' => 'assets/logos/airtel.png',    'is_active' => false],
            ['op_code' => '121', 'name' => 'DishTV',       'type' => 'dth', 'category_slug' => 'dth', 'logo' => 'assets/logos/dishtv.png',    'is_active' => false],
            ['op_code' => '122', 'name' => 'Sun Direct',   'type' => 'dth', 'category_slug' => 'dth', 'logo' => 'assets/logos/sundirect.png', 'is_active' => false],
            ['op_code' => '123', 'name' => 'Tata Play',    'type' => 'dth', 'category_slug' => 'dth', 'logo' => 'assets/logos/tataplay.png',  'is_active' => false],
            ['op_code' => '124', 'name' => 'Videocon d2h', 'type' => 'dth', 'category_slug' => 'dth', 'logo' => 'assets/logos/d2h.png',       'is_active' => false],

            ['op_code' => '104', 'name' => 'PickMe',    'type' => 'wallet', 'category_slug' => 'wallet-topup', 'logo' => 'assets/logos/pickme.png'],
            ['op_code' => '105', 'name' => 'Uber Eats', 'type' => 'wallet', 'category_slug' => 'wallet-topup', 'logo' => 'assets/logos/ubereats.png'],
        ];
    }

    /* ---------- balance ---------- */
    public function balance(): ?float
    {
        if (! $this->apiKey) {
            return null;
        }
        try {
            $res = Http::timeout(15)->connectTimeout(10)->get($this->baseUrl . '/balance.php', [
                'api_key' => $this->apiKey,
            ]);
            if (! $res->successful()) {
                Log::warning("TopupMart balance HTTP {$res->status()}: {$res->body()}");
                return null;
            }
            $data = $res->json() ?? [];
            if (strtolower((string) ($data['status'] ?? '')) === 'failed') {
                Log::warning('TopupMart balance error: ' . ($data['message'] ?? 'unknown'));
                return null;
            }
            return (float) ($data['balance'] ?? 0);
        } catch (\Throwable $e) {
            Log::warning('TopupMart balance exception: ' . $e->getMessage());
            return null;
        }
    }

    /* ---------- recharge ---------- */
    public function recharge(Order $order): array
    {
        $mobile = preg_replace('/[^0-9]/', '', $order->account_number);
        $notify = $order->notify_number
            ? preg_replace('/[^0-9]/', '', $order->notify_number)
            : $mobile;

        // Normalise amount: strip trailing zeros so "10.00" becomes "10"
        // (some LK provider APIs reject decimal amounts on integer services).
        $amount = rtrim(rtrim(number_format((float) $order->amount, 2, '.', ''), '0'), '.');

        $payload = [
            'api_key'    => $this->apiKey,
            'mobile'     => $mobile,
            'amount'     => $amount,
            'op_code'    => $order->sendOpCode(),
            'NotifyNo'   => $notify,
            'client_ref' => $order->providerClientRef(),
        ];

        Log::info('TopupMart recharge request', [
            'order'   => $order->reference,
            'op_code' => $order->sendOpCode() ?: $order->service->op_code,
            'mobile'  => $mobile,
            'amount'  => $amount,
            'url'     => $this->baseUrl . '/recharge.php',
        ]);

        // TopupMart expects application/json. We use asJson() (sets Content-Type
        // to application/json and JSON-encodes the body). 90s timeout because
        // carrier retries on their end can take a while.
        $res = Http::timeout(90)->connectTimeout(15)->asJson()->post(
            $this->baseUrl . '/recharge.php',
            $payload
        );

        $body = $res->body();
        $data = $res->json();
        if (! is_array($data)) {
            Log::warning('TopupMart recharge returned non-JSON response', [
                'order'    => $order->reference,
                'status'   => $res->status(),
                'body'     => mb_substr($body, 0, 1000),
            ]);
            // Non-JSON = we don't know what happened — mark pending for cron.
            return ['status' => 'pending', 'message' => 'Provider returned an unrecognised response — we will verify your order shortly.', '_raw' => mb_substr($body, 0, 500)];
        }

        Log::info('TopupMart recharge response', [
            'order'   => $order->reference,
            'op_code' => $order->sendOpCode() ?: $order->service->op_code,
            'payload' => $data,
        ]);

        return $data;
    }

    /* ---------- status check ---------- */
    public function checkStatus(Order $order): array
    {
        $amount = null;
        if ($order->amount) {
            $amount = rtrim(rtrim(number_format((float) $order->amount, 2, '.', ''), '0'), '.');
        }

        // Try transaction_id first. Fall back to client_ref + mobile + amount
        // so orders that timed out before we got a transaction_id can still
        // be reconciled.
        $payload = array_filter([
            'api_key'        => $this->apiKey,
            'transaction_id' => $order->provider_txn_id ?: null,
            'client_ref'     => $order->providerClientRef(),
            'mobile'         => preg_replace('/[^0-9]/', '', $order->account_number),
            'amount'         => $amount,
            'op_code'        => $order->sendOpCode() ?: $order->service?->op_code,
        ]);

        try {
            $res = Http::timeout(30)->connectTimeout(10)->asJson()->post(
                $this->baseUrl . '/status.php',
                $payload
            );
            $data = $res->json();
            if (! is_array($data)) {
                Log::warning('TopupMart status returned non-JSON response', [
                    'order' => $order->reference,
                    'body'  => mb_substr($res->body(), 0, 500),
                ]);
                return ['status' => 'pending', 'message' => 'Status check pending…'];
            }
            return $data;
        } catch (\Throwable $e) {
            Log::warning("TopupMart status exception for {$order->reference}: " . $e->getMessage());
            return ['status' => 'pending', 'message' => 'Status check failed — will retry.'];
        }
    }
}
