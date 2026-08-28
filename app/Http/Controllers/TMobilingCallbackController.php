<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use App\Services\ProviderFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * TMobiling "Response URL" — they POST and GET:
 *   status, reference (our id), txn_id (theirs), operator_code
 *
 * We never trust the callback alone. We look up the order and re-check
 * status with their API, then apply the same success / pending / refund path
 * as the clock.
 */
class TMobilingCallbackController extends Controller
{
    public function __invoke(Request $request, OrderService $orders): Response
    {
        $ref = trim((string) $request->input('reference', ''));
        $txn = trim((string) $request->input('txn_id', $request->input('txnid', '')));

        Log::info('TMobiling callback', [
            'reference' => $ref,
            'txn_id' => $txn,
            'status' => $request->input('status'),
            'method' => $request->method(),
        ]);

        $order = $this->findOrder($ref, $txn);
        if (! $order) {
            return response('ok', 200);
        }

        if (! in_array($order->status, ['pending', 'processing'], true)) {
            return response('ok', 200);
        }

        $order->loadMissing('provider');
        if (! $order->provider || ! $order->provider->isTMobiling()) {
            return response('ok', 200);
        }

        if ($txn !== '' && empty($order->provider_txn_id)) {
            $order->provider_txn_id = $txn;
            $order->save();
        }

        try {
            $resp = ProviderFactory::make($order->provider)->checkStatus($order);
            $orders->applyStatusCheck($order, is_array($resp) ? $resp : []);
        } catch (\Throwable $e) {
            Log::warning('TMobiling callback status check failed: '.$e->getMessage(), [
                'order' => $order->reference,
            ]);
        }

        return response('ok', 200);
    }

    protected function findOrder(string $ref, string $txn): ?Order
    {
        if ($ref !== '') {
            $found = Order::where('reference', $ref)->first();
            if ($found) {
                return $found;
            }

            $base = preg_replace('/-T\d+$/', '', $ref);
            if ($base && $base !== $ref) {
                $found = Order::where('reference', $base)->first();
                if ($found) {
                    return $found;
                }
            }
        }

        if ($txn !== '') {
            return Order::where('provider_txn_id', $txn)->first();
        }

        return null;
    }
}
