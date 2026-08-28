<?php

namespace App\Services\Providers;

use App\Models\Order;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * TMobiling (Sri Lanka) API v2.1
 *
 * Docs (TMobiling_Api_Document_2.1v):
 *  GET https://www.tmobiling.lk/livenew/apis/api_request
 *    method=recharge     api_key, reference, amount, number, operator
 *                        + ref_no, from_bbps=1 for bill / utility (BBPS)
 *    method=txn-status   api_key, ref_no = our reference
 *    method=txnid-status api_key, ref_no = their txn_id
 *    method=balance      api_key
 *    method=billcheck    api_key, operator, number  (CEB 29 / Water 31)
 *
 * Recharge responses use recharge_status: success | pending | failed.
 * Status responses use result: success | Pending | failed.
 */
class TMobiling implements ProviderInterface
{
    public const DEFAULT_BASE_URL = 'https://www.tmobiling.lk/livenew/apis/api_request';

    protected string $baseUrl;

    protected string $apiKey;

    protected ?string $lastError = null;

    public function __construct(?string $baseUrl = null, ?string $apiKey = null)
    {
        $this->baseUrl = rtrim(
            ($baseUrl !== null && $baseUrl !== '')
                ? $baseUrl
                : (string) config('services.tmobiling.base_url', self::DEFAULT_BASE_URL),
            '/'
        );
        $this->apiKey = (string) (
            ($apiKey !== null && $apiKey !== '')
                ? $apiKey
                : config('services.tmobiling.api_key', '')
        );
    }

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    /** @return list<array<string, mixed>> */
    public static function catalog(): array
    {
        $d = 'assets/logos/dialog.png';
        $a = 'assets/logos/airtel.png';
        $m = 'assets/logos/sltmobitel.png';
        $h = 'assets/logos/hutch.png';

        return [
            // Mobile prepaid
            self::row('1', 'Dialog Prepaid', 'prepaid', 'mobile', $d, 'dialog-prepaid'),
            self::row('2', 'Airtel Prepaid', 'prepaid', 'mobile', $a, 'airtel-prepaid'),
            self::row('3', 'Mobitel Prepaid', 'prepaid', 'mobile', $m, 'mobitel-prepaid'),
            self::row('4', 'Hutch Prepaid', 'prepaid', 'mobile', $h, 'hutch-prepaid'),
            self::row('7', 'Dialog CDMA Prepaid', 'prepaid', 'mobile', $d, 'dialog-cdma-prepaid'),

            // Mobile postpaid
            self::row('12', 'Dialog Postpaid', 'postpaid', 'mobile', $d, 'dialog-postpaid'),
            self::row('13', 'Airtel Postpaid', 'postpaid', 'mobile', $a, 'airtel-postpaid'),
            self::row('14', 'Mobitel Postpaid', 'postpaid', 'mobile', $m, 'mobitel-postpaid'),
            self::row('15', 'Hutch Postpaid', 'postpaid', 'mobile', $h, 'hutch-postpaid'),

            // Broadband / TV
            self::row('5', 'Dialog TV Prepaid', 'tv', 'tv', $d, 'dialog-tv-prepaid'),
            self::row('6', 'Dialog 4G Router Prepaid', 'broadband', 'broadband', $d, 'dialog-router-prepaid'),
            self::row('28', 'SLT Router Prepaid', 'broadband', 'broadband', $m, 'slt-router-prepaid'),
            self::row('11', 'LESI Pay', 'broadband', 'broadband', 'assets/logos/lesipay.png', 'lesi-pay'),
            self::row('16', 'Dialog TV Postpaid', 'tv', 'tv', $d, 'dialog-tv-postpaid'),
            self::row('17', 'Dialog 4G Router Postpaid', 'broadband', 'broadband', $d, 'dialog-router-postpaid'),
            self::row('19', 'Mobitel 4G Router Postpaid', 'broadband', 'broadband', $m, 'mobitel-router-postpaid'),

            // Cab / wallet
            self::row('10', 'PickMe', 'wallet', 'wallet-topup', 'assets/logos/pickme.png', 'pickme'),
            self::row('40', 'Uber Lanka', 'wallet', 'wallet-topup', 'assets/logos/uber.png', 'uber'),
            self::row('41', 'Tripmo', 'wallet', 'wallet-topup', 'assets/logos/tripmo.png', 'tripmo'),
            self::row('25', 'EzCash Send', 'wallet', 'wallet-topup', 'assets/logos/ezcash.png', 'ezcash-send'),
            self::row('27', 'EzCash Withdraw', 'wallet', 'wallet-topup', 'assets/logos/ezcash.png', 'ezcash-withdraw'),

            // Indian DTH (amount is the pack value; TMobiling wallet is LKR)
            self::row('20', 'Sun Direct', 'dth', 'dth', 'assets/logos/sundirect.png', 'sun-direct'),
            self::row('21', 'Videocon d2h', 'dth', 'dth', 'assets/logos/d2h.png', 'videocon-d2h'),
            self::row('22', 'Dish TV', 'dth', 'dth', 'assets/logos/dishtv.png', 'dish-tv'),
            self::row('23', 'Airtel Digital TV', 'dth', 'dth', $a, 'airtel-dth'),
            self::row('79', 'Tata Sky', 'dth', 'dth', 'assets/logos/tataplay.png', 'tata-sky'),

            // Bills (BBPS)
            self::row('29', 'CEB Electricity', 'utility', 'utility', 'assets/logos/ceb.png', 'ceb', true),
            self::row('30', 'LECO Electricity', 'utility', 'utility', 'assets/logos/leco.png', 'leco', true),
            self::row('31', 'Water (NWSDB)', 'utility', 'utility', 'assets/logos/nwsdb.png', 'nwsdb', true),

            // Insurance (BBPS)
            self::row('32', 'AIA Insurance', 'insurance', 'insurance', 'assets/logos/aia.png', 'aia', true),
            self::row('33', 'HNB Finance', 'insurance', 'insurance', 'assets/logos/hnbfinance.png', 'hnb-finance', true),
            self::row('34', 'Janashakthi Life', 'insurance', 'insurance', 'assets/logos/janashakthi.png', 'janashakthi', true),
            self::row('35', 'Allianz Life', 'insurance', 'insurance', 'assets/logos/allianz.png', 'allianz', true),
            self::row('36', 'Sri Lanka Insurance', 'insurance', 'insurance', 'assets/logos/srilankains.png', 'sli', true),
            self::row('37', 'Union Assurance Life', 'insurance', 'insurance', 'assets/logos/unionassurance.png', 'union-assurance', true),
            self::row('38', 'Softlogic Life', 'insurance', 'insurance', 'assets/logos/softlogic.png', 'softlogic-life', true),
            self::row('39', 'VisionFund Lanka', 'insurance', 'insurance', 'assets/logos/visionfund.png', 'visionfund', true),
            self::row('68', 'HNB Assurance', 'insurance', 'insurance', 'assets/logos/hnbassu.png', 'hnb-assurance', true),
            self::row('80', 'Fintrex Finance', 'insurance', 'insurance', 'assets/logos/fintrex.png', 'fintrex', true),

            // Landline bills (BBPS)
            self::row('9', 'Lanka Bell', 'utility', 'utility', 'assets/logos/lankabell.png', 'lanka-bell', true),
            self::row('18', 'Dialog CDMA Landline', 'utility', 'utility', $d, 'dialog-cdma-landline', true),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function row(
        string $op,
        string $name,
        string $type,
        string $cat,
        ?string $logo,
        string $key,
        bool $bbps = false
    ): array {
        $row = [
            'op_code' => $op,
            'name' => $name,
            'type' => $type,
            'category_slug' => $cat,
            'logo' => $logo,
            'catalog_key' => $key,
        ];
        if ($bbps) {
            $row['bbps'] = true;
        }

        return $row;
    }

    public function fetchServices(): array
    {
        return static::catalog();
    }

    public function balance(): ?float
    {
        $this->lastError = null;
        if ($this->apiKey === '') {
            $this->lastError = 'No API key configured';
            return null;
        }

        try {
            $res = Http::timeout(15)->connectTimeout(10)->get($this->baseUrl, [
                'method' => 'balance',
                'api_key' => $this->apiKey,
            ]);
            if (! $res->successful()) {
                $this->lastError = "HTTP {$res->status()}";
                Log::warning("TMobiling balance HTTP {$res->status()}: {$res->body()}");
                return null;
            }
            $data = $res->json();
            if (! is_array($data)) {
                $this->lastError = 'Balance response was not JSON';
                return null;
            }
            $status = strtolower(trim((string) ($data['status'] ?? '')));
            $raw = $data['message'] ?? ($data['balance'] ?? '');
            if ($status !== '' && $status !== 'success') {
                $this->lastError = is_scalar($raw) ? (string) $raw : "Balance STATUS={$status}";
                Log::warning('TMobiling balance rejected: '.$this->lastError);
                return null;
            }
            $clean = str_replace([',', ' ', 'LKR', 'lkr', 'Rs.', 'Rs', 'INR'], '', (string) $raw);
            if ($clean === '' || ! is_numeric($clean)) {
                $this->lastError = is_scalar($raw) && (string) $raw !== '' ? (string) $raw : 'Balance response was not a number';
                Log::warning('TMobiling balance parse error: '.$res->body());
                return null;
            }

            return (float) $clean;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            Log::warning('TMobiling balance exception: '.$e->getMessage());
            return null;
        }
    }

    public function recharge(Order $order): array
    {
        $number = preg_replace('/[^0-9]/', '', (string) $order->account_number);
        $amount = rtrim(rtrim(number_format((float) $order->amount, 2, '.', ''), '0'), '.');
        $op = $order->sendOpCode() ?: (string) ($order->service?->op_code ?? '');

        $query = [
            'method' => 'recharge',
            'api_key' => $this->apiKey,
            'reference' => $order->providerClientRef(),
            'amount' => $amount,
            'number' => $number,
            'operator' => $op,
        ];

        $order->loadMissing('service');
        if ($order->service && $order->service->usesBbps()) {
            $ref = $order->notify_number
                ? preg_replace('/[^0-9]/', '', (string) $order->notify_number)
                : $number;
            $query['ref_no'] = $ref ?: $number;
            $query['from_bbps'] = 1;
        }

        Log::info('TMobiling recharge request', [
            'order' => $order->reference,
            'op_code' => $op,
            'number' => $number,
            'amount' => $amount,
            'bbps' => isset($query['from_bbps']),
        ]);

        try {
            $res = Http::timeout(90)->connectTimeout(15)->get($this->baseUrl, $query);
        } catch (ConnectionException $e) {
            Log::warning('TMobiling recharge TIMEOUT', [
                'order' => $order->reference,
                'error' => $e->getMessage(),
            ]);

            return ['status' => 'pending', 'message' => 'Request sent — waiting for provider confirmation…'];
        }

        $body = $res->body();
        $data = $res->json();
        if (! is_array($data)) {
            Log::warning('TMobiling recharge returned non-JSON', [
                'order' => $order->reference,
                'status' => $res->status(),
                'body' => mb_substr($body, 0, 1000),
            ]);

            return [
                'status' => 'pending',
                'message' => 'Provider returned an unrecognised response — we will verify your order shortly.',
                '_raw' => mb_substr($body, 0, 500),
            ];
        }

        Log::info('TMobiling recharge response', [
            'order' => $order->reference,
            'payload' => $data,
        ]);

        return $this->normaliseRecharge($data);
    }

    public function checkStatus(Order $order): array
    {
        $txn = trim((string) $order->provider_txn_id);
        $query = [
            'api_key' => $this->apiKey,
            'ref_no' => $txn !== '' ? $txn : $order->providerClientRef(),
            'method' => $txn !== '' ? 'txnid-status' : 'txn-status',
        ];

        try {
            $res = Http::timeout(30)->connectTimeout(10)->get($this->baseUrl, $query);
            $data = $res->json();
            if (! is_array($data)) {
                Log::warning('TMobiling status returned non-JSON', [
                    'order' => $order->reference,
                    'body' => mb_substr($res->body(), 0, 500),
                ]);

                return ['status' => 'pending', 'message' => 'Status check pending…'];
            }

            Log::info('TMobiling status response', [
                'order' => $order->reference,
                'payload' => $data,
            ]);

            return $this->normaliseStatus($data, $order);
        } catch (\Throwable $e) {
            Log::warning("TMobiling status exception for {$order->reference}: ".$e->getMessage());

            return ['status' => 'pending', 'message' => 'Status check failed — will retry.'];
        }
    }

    /**
     * Optional bill lookup (CEB 29 / Water 31). Never throws.
     *
     * @return array<string, mixed>
     */
    public function billInfo(string $operator, string $number): array
    {
        try {
            $res = Http::timeout(20)->connectTimeout(10)->get($this->baseUrl, [
                'method' => 'billcheck',
                'api_key' => $this->apiKey,
                'operator' => $operator,
                'number' => preg_replace('/[^0-9A-Za-z]/', '', $number),
            ]);
            $data = $res->json();

            return is_array($data) ? $data : ['status' => 'failed', 'message' => 'Could not read bill details.'];
        } catch (\Throwable $e) {
            Log::warning('TMobiling billcheck failed: '.$e->getMessage());

            return ['status' => 'failed', 'message' => 'Could not read bill details.'];
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normaliseRecharge(array $data): array
    {
        $outer = strtolower(trim((string) ($data['status'] ?? '')));
        $inner = strtolower(trim((string) ($data['recharge_status'] ?? $data['result'] ?? $outer)));
        $txn = $data['txn_id'] ?? $data['transaction_id'] ?? null;
        $message = $data['message'] ?? $data['reason'] ?? null;

        return [
            'status' => $this->mapStatus($inner !== '' ? $inner : $outer),
            'transaction_id' => $txn ? (string) $txn : null,
            'operator_id' => $data['auth_code'] ?? $data['op_code'] ?? $data['operator_code'] ?? null,
            'message' => is_scalar($message) ? (string) $message : null,
            'balance' => $data['balance'] ?? null,
            '_raw_status' => $inner ?: $outer,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normaliseStatus(array $data, Order $order): array
    {
        $outer = strtolower(trim((string) ($data['status'] ?? '')));
        $result = strtolower(trim((string) ($data['result'] ?? $data['recharge_status'] ?? '')));
        $reason = $data['reason'] ?? $data['message'] ?? null;
        $txn = $data['txn_id'] ?? $data['transaction_id'] ?? $order->provider_txn_id;

        if ($outer === 'failed' && $result === '') {
            $msg = is_scalar($reason) ? (string) $reason : 'Reference number is not matched with client system.';
            if (stripos($msg, 'not matched') !== false || stripos($msg, 'not found') !== false) {
                return [
                    'status' => 'pending',
                    'message' => 'Transaction not yet found at provider — will retry.',
                    'transaction_id' => $txn ? (string) $txn : null,
                    '_raw_status' => $outer,
                ];
            }

            return [
                'status' => 'failed',
                'message' => $msg,
                'transaction_id' => $txn ? (string) $txn : null,
                '_raw_status' => $outer,
            ];
        }

        $mapped = $this->mapStatus($result !== '' ? $result : $outer);

        return [
            'status' => $mapped,
            'transaction_id' => $txn ? (string) $txn : null,
            'operator_id' => $data['op_code'] ?? $data['operator_code'] ?? $data['auth_code'] ?? null,
            'message' => is_scalar($reason) ? (string) $reason : null,
            '_raw_status' => $result ?: $outer,
        ];
    }

    protected function mapStatus(string $raw): string
    {
        $raw = strtolower(trim($raw));
        if (in_array($raw, ['success', 'successful', 'completed'], true)) {
            return 'success';
        }
        if (in_array($raw, ['failed', 'fail', 'failure', 'refund', 'cancelled', 'canceled'], true)) {
            return 'failed';
        }

        return 'pending';
    }
}
