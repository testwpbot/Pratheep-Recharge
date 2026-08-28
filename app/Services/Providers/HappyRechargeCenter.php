<?php

namespace App\Services\Providers;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Happy Recharge Center (India) provider integration.
 *
 * Docs (from provider):
 *  - Recharge: GET Recharge.aspx?Apitoken=…&Amount=…&OperatorCode=…&Number=…&ClientId=…
 *      Responses (STATUS is UPPERCASE):
 *          SUCCESS → {"STATUS":"SUCCESS","TRANSACTIONID":"…","OPERATORID":"…","CLIENTID":"…","MESSAGE":""}
 *          FAILURE → {"STATUS":"FAILURE","TRANSACTIONID":"…","OPERATORID":"","CLIENTID":"…","MESSAGE":""}
 *          PENDING → {"STATUS":"IN PROCESS","TRANSACTIONID":"…","OPERATORID":"","CLIENTID":"…","MESSAGE":""}
 *  - Balance:  GET Balance.aspx?Apitoken=…
 *      MESSAGE contains balance as a comma-formatted string e.g. "1,970.10"; STATUS always SUCCESS.
 *  - Status:   GET rechargestatus.aspx?Apitoken=…&ClientId=…
 *      Outer STATUS always SUCCESS; check RECHARGESTATUS (SUCCESS / FAILURE / "IN PROCESS" / "TRANSACTION NOT FOUND").
 *
 * Timeouts match TopupMart (90s total, 15s connect) because carrier retries can be slow.
 */
class HappyRechargeCenter implements ProviderInterface
{
    protected string $baseUrl;
    protected string $apiKey;
    protected ?string $lastError = null;

    public function __construct(?string $baseUrl = null, ?string $apiKey = null)
    {
        $this->baseUrl = rtrim(
            ($baseUrl !== null && $baseUrl !== '')
                ? $baseUrl
                : (string) config('services.happy_recharge_center.base_url', 'http://happyrechargecenter.com/RechargeApi'),
            '/'
        );
        $this->apiKey = (string) (
            ($apiKey !== null && $apiKey !== '')
                ? $apiKey
                : config('services.happy_recharge_center.api_key', '')
        );
    }

    /**
     * DTH-only catalog. HRC is never used for mobile / utility / SL TV.
     *
     * OperatorCode 1 is Airtel *mobile prepaid* (per HRC docs). DTH codes sit in
     * the standard RechargeApi DTH block used by this ASP.NET software family:
     *   16 Dish TV · 17 Tata Sky/Tata Play · 18 Videocon d2h · 19 Sun Direct · 20 Airtel Digital TV
     *
     * Admin can override each service's op_code after import (Services → Edit)
     * if the operator-list page shows different numbers. Ctrl+F "DTH" / "Dish"
     * on http://happyrechargecenter.com/apiuser/api_operator.aspx (login required).
     *
     * failover_op_code maps to the matching Topup Mart DTH placeholder so admin
     * can re-send a stuck pending order through provider #1.
     */
    public static function dthCatalog(): array
    {
        // Only Airtel DTH is sold through this provider. Other Indian DTH brands
        // stay off the catalog. Topup Mart keeps a hidden Airtel DTH row (op 120)
        // so admin can fail a pending HRC order over to provider #1.
        return [
            ['op_code' => '20', 'name' => 'Airtel DTH', 'type' => 'dth', 'category_slug' => 'dth', 'logo' => 'assets/logos/airtel.png', 'failover_op_code' => '120'],
        ];
    }

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    /* ---------- catalog (admin "Import") — DTH TV only ---------- */
    public function fetchServices(): array
    {
        return static::dthCatalog();
    }

    /**
     * HRC's published API has Recharge / Balance / Status only — no cancel.
     * We still expose this so failover can record a best-effort attempt.
     */
    public function cancel(Order $order): array
    {
        $custom = trim((string) config('services.happy_recharge_center.cancel_path', ''));
        $paths  = array_values(array_filter([
            $custom,
            'CancelRecharge.aspx',
            'RechargeCancel.aspx',
        ]));

        foreach ($paths as $path) {
            try {
                $res = Http::timeout(8)->connectTimeout(4)->get($this->baseUrl . '/' . ltrim($path, '/'), [
                    'Apitoken' => $this->apiKey,
                    'ClientId' => $order->reference,
                ]);
                if ($res->status() === 404) {
                    continue;
                }
                $data = $res->json();
                Log::info('HappyRechargeCenter cancel response', [
                    'order' => $order->reference,
                    'path'  => $path,
                    'http'  => $res->status(),
                    'body'  => is_array($data) ? $data : mb_substr($res->body(), 0, 400),
                ]);
                $raw = strtoupper(trim((string) (is_array($data) ? ($data['STATUS'] ?? $data['RECHARGESTATUS'] ?? '') : '')));
                if (in_array($raw, ['SUCCESS', 'CANCELLED', 'CANCELED', 'FAILURE', 'REFUND'], true)) {
                    return [
                        'status'  => in_array($raw, ['SUCCESS', 'CANCELLED', 'CANCELED', 'REFUND'], true) ? 'cancelled' : 'failed',
                        'message' => is_array($data) ? ($data['MESSAGE'] ?? $raw) : $raw,
                        '_raw'    => $data,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('HappyRechargeCenter cancel probe failed', [
                    'order' => $order->reference,
                    'path'  => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('HappyRechargeCenter has no cancel API — failover will resend via Topup Mart', [
            'order' => $order->reference,
        ]);

        return [
            'status'  => 'unsupported',
            'message' => 'Happy Recharge Center does not provide a cancel API. The original request may still complete on their side — check the HRC panel.',
        ];
    }

    /* ---------- balance ---------- */
    public function balance(): ?float
    {
        $this->lastError = null;
        if (! $this->apiKey) {
            $this->lastError = 'No API token configured';
            return null;
        }
        try {
            $res = Http::timeout(15)->connectTimeout(10)->get($this->baseUrl . '/Balance.aspx', [
                'Apitoken' => $this->apiKey,
            ]);
            if (! $res->successful()) {
                $this->lastError = "HTTP {$res->status()}";
                Log::warning("HappyRechargeCenter balance HTTP {$res->status()}: {$res->body()}");
                return null;
            }
            $data   = $res->json() ?? [];
            $status = strtoupper(trim((string) ($data['STATUS'] ?? '')));
            $raw    = (string) ($data['MESSAGE'] ?? '');
            if ($status !== '' && $status !== 'SUCCESS') {
                $this->lastError = $raw !== '' ? $raw : "Balance STATUS={$status}";
                Log::warning('HappyRechargeCenter balance rejected: ' . $this->lastError);
                return null;
            }
            // Balance comes back as a comma-formatted string like "1,970.10"
            $clean = str_replace([',', ' '], '', $raw);
            if ($clean === '' || ! is_numeric($clean)) {
                $this->lastError = $raw !== '' ? $raw : 'Balance response was not a number';
                Log::warning('HappyRechargeCenter balance parse error: ' . $res->body());
                return null;
            }
            return (float) $clean;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            Log::warning('HappyRechargeCenter balance exception: ' . $e->getMessage());
            return null;
        }
    }

    /* ---------- recharge ---------- */
    public function recharge(Order $order): array
    {
        $number = preg_replace('/[^0-9]/', '', $order->account_number);
        $amount = rtrim(rtrim(number_format((float) $order->amount, 2, '.', ''), '0'), '.');

        $query = [
            'Apitoken'     => $this->apiKey,
            'Amount'       => $amount,
            'OperatorCode' => $order->sendOpCode() ?: (string) $order->service->op_code,
            'Number'       => $number,
            'ClientId'     => $order->providerClientRef(),
        ];

        Log::info('HappyRechargeCenter recharge request', [
            'order'   => $order->reference,
            'op_code' => $order->sendOpCode() ?: $order->service->op_code,
            'number'  => $number,
            'amount'  => $amount,
        ]);

        try {
            // HRC uses GET with query params (no JSON body).
            $res = Http::timeout(90)->connectTimeout(15)->get(
                $this->baseUrl . '/Recharge.aspx',
                $query
            );
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Timeout / network error — leave pending for cron reconciliation,
            // same as TopupMart.
            Log::warning('HappyRechargeCenter recharge TIMEOUT', [
                'order' => $order->reference, 'error' => $e->getMessage(),
            ]);
            return ['status' => 'pending', 'message' => 'Request sent — waiting for provider confirmation…'];
        }

        $body = $res->body();
        $data = $res->json();
        if (! is_array($data)) {
            Log::warning('HappyRechargeCenter recharge returned non-JSON', [
                'order'  => $order->reference,
                'status' => $res->status(),
                'body'   => mb_substr($body, 0, 1000),
            ]);
            return ['status' => 'pending', 'message' => 'Provider returned an unrecognised response — we will verify your order shortly.', '_raw' => mb_substr($body, 0, 500)];
        }

        Log::info('HappyRechargeCenter recharge response', [
            'order'   => $order->reference,
            'payload' => $data,
        ]);

        // Normalise to lowercase status key/value to match what OrderService expects.
        // HRC returns upper-case STATUS: SUCCESS / FAILURE / IN PROCESS.
        return $this->normaliseResponse($data);
    }

    /* ---------- status check ---------- */
    public function checkStatus(Order $order): array
    {
        try {
            $res = Http::timeout(30)->connectTimeout(10)->get(
                $this->baseUrl . '/rechargestatus.aspx',
                [
                    'Apitoken' => $this->apiKey,
                    'ClientId' => $order->providerClientRef(),
                ]
            );
            $data = $res->json();
            if (! is_array($data)) {
                Log::warning('HappyRechargeCenter status returned non-JSON', [
                    'order' => $order->reference,
                    'body'  => mb_substr($res->body(), 0, 500),
                ]);
                return ['status' => 'pending', 'message' => 'Status check pending…'];
            }

            Log::info('HappyRechargeCenter status response', [
                'order'   => $order->reference,
                'payload' => $data,
            ]);

            // Outer STATUS is always SUCCESS on a valid call. Real status is in RECHARGESTATUS.
            return $this->normaliseStatusResponse($data, $order);
        } catch (\Throwable $e) {
            Log::warning("HappyRechargeCenter status exception for {$order->reference}: " . $e->getMessage());
            return ['status' => 'pending', 'message' => 'Status check failed — will retry.'];
        }
    }

    /* ---------- helpers ---------- */

    /**
     * Normalise the immediate Recharge.aspx response into the shape OrderService expects
     * (lower-case 'status' key + optional 'transaction_id' + 'message').
     */
    protected function normaliseResponse(array $data): array
    {
        $rawStatus = strtoupper(trim((string) ($data['STATUS'] ?? 'FAILURE')));
        $txnId     = $data['TRANSACTIONID'] ?? null;
        $opId      = $data['OPERATORID'] ?? null;
        $message   = $data['MESSAGE'] ?: null;

        return [
            'status'         => $this->mapStatus($rawStatus),
            'transaction_id' => $txnId ?: null,
            'operator_id'    => $opId ?: null,
            'message'        => $message,
            '_raw_status'    => $rawStatus,
        ];
    }

    /**
     * Normalise the rechargestatus.aspx response. Outer STATUS is always SUCCESS;
     * we map RECHARGESTATUS to our canonical statuses.
     */
    protected function normaliseStatusResponse(array $data, Order $order): array
    {
        // If outer STATUS is not SUCCESS (rare), treat as failure.
        $outer = strtoupper(trim((string) ($data['STATUS'] ?? 'FAILURE')));
        if ($outer !== 'SUCCESS') {
            return ['status' => 'pending', 'message' => $data['MESSAGE'] ?? 'Provider status check returned error'];
        }

        $raw    = strtoupper(trim((string) ($data['RECHARGESTATUS'] ?? 'IN PROCESS')));
        $opId   = $data['OPERATORID'] ?? null;
        $message = $data['MESSAGE'] ?: null;

        $out = [
            'status'         => $this->mapStatus($raw),
            'transaction_id' => $order->provider_txn_id ?: null,
            'operator_id'    => $opId ?: null,
            'message'        => $message,
            '_raw_status'    => $raw,
        ];

        // "TRANSACTION NOT FOUND" → treat as pending for up to the reconciliation
        // window; if it never materialises the cron/admin will handle it.
        if (str_contains($raw, 'NOT FOUND')) {
            $out['status'] = 'pending';
            $out['message'] = 'Transaction not yet found at provider — will retry.';
        }

        return $out;
    }

    /** Map HRC upper-case status strings to our internal lowercase status. */
    protected function mapStatus(string $raw): string
    {
        // SUCCESS, FAILURE, IN PROCESS, plus NOT FOUND variants.
        if ($raw === 'SUCCESS')     return 'success';
        if ($raw === 'FAILURE')     return 'failed';
        if ($raw === 'IN PROCESS')  return 'pending';
        if (str_contains($raw, 'NOT FOUND')) return 'pending';
        return 'pending';
    }
}
