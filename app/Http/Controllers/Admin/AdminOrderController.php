<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminOrderController extends Controller
{
    public function index(Request $request): View
    {
        $query = Order::with(['user', 'service', 'provider']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('account_number', 'like', "%{$search}%")
                  ->orWhere('provider_txn_id', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$search}%")
                                                    ->orWhere('name', 'like', "%{$search}%"));
            });
        }

        $orders = $query->latest()->paginate(50)->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $order->load(['user', 'service', 'provider', 'cashback']);
        return view('admin.orders.show', compact('order'));
    }

    public function sync(Request $request, Order $order, OrderService $svc)
    {
        try {
            $client = \App\Services\ProviderFactory::make($order->provider);
            $resp = $client->checkStatus($order);
            $status = strtolower((string) ($resp['status'] ?? 'pending'));
            $order->provider_response = $resp;
            $order->message = $resp['message'] ?? $order->message;

            if ($status === 'success') {
                $svc->markSuccess($order);
                $message = "Order {$order->reference} marked as success — cashback credited.";
            } elseif (in_array($status, ['failed', 'refund', 'cancelled'], true) && ! $order->isFailedLike()) {
                $svc->markFailed($order, $resp['message'] ?? null);
                $fresh = $order->fresh();
                $message = $fresh && $fresh->isRefunded()
                    ? "Order {$order->reference} failed. Money was put back in the wallet."
                    : "Order {$order->reference} marked as failed.";
            } else {
                $order->save();
                $message = "Order still {$status}.";
            }

            if ($request->wantsJson()) {
                return response()->json(['ok' => true, 'message' => $message, 'status' => $order->fresh()->status]);
            }
            return back()->with('status', $message);
        } catch (\Throwable $e) {
            $message = 'Sync failed: ' . $e->getMessage();
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $message], 500);
            }
            return back()->with('error', $message);
        }
    }

    /**
     * Admin failover: cancel stuck HRC order locally and re-send via Topup Mart.
     */
    public function failover(Request $request, Order $order, OrderService $svc)
    {
        /** @var User $admin */
        $admin = Auth::user();
        $note  = trim((string) $request->input('note', ''));

        try {
            $updated = $svc->failoverToTopupMart($order, $admin, $note !== '' ? $note : null);
            $message = "Failover complete. Order {$updated->reference} re-sent via Topup Mart (status: {$updated->status}).";
            if ($request->wantsJson()) {
                return response()->json([
                    'ok'               => true,
                    'message'          => $message,
                    'new_order_id'     => $updated->id,
                    'new_order_ref'    => $updated->reference,
                    'new_order_status' => $updated->status,
                    'redirect'         => route('admin.orders.show', $updated),
                ]);
            }
            return redirect()->route('admin.orders.show', $updated)->with('status', $message);
        } catch (\Throwable $e) {
            $message = 'Failover failed: ' . $e->getMessage();
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $message], 500);
            }
            return back()->with('error', $message);
        }
    }

    /**
     * Admin sends a pending Dialog order through Dialog API, or the other way.
     */
    public function transfer(Request $request, Order $order, OrderService $svc)
    {
        /** @var User $admin */
        $admin = Auth::user();
        $note = trim((string) $request->input('note', ''));

        try {
            $updated = $svc->transferToPairedService($order, $admin, $note !== '' ? $note : null);
            $toName = $updated->service->name ?? 'the other route';
            $message = "Order {$updated->reference} sent through {$toName}. Status: {$updated->status}. Customer was not charged again.";
            if ($request->wantsJson()) {
                return response()->json([
                    'ok' => true,
                    'message' => $message,
                    'new_order_id' => $updated->id,
                    'new_order_ref' => $updated->reference,
                    'new_order_status' => $updated->status,
                    'redirect' => route('admin.orders.show', $updated),
                ]);
            }

            return redirect()->route('admin.orders.show', $updated)->with('status', $message);
        } catch (\Throwable $e) {
            $message = 'Could not switch route: ' . $e->getMessage();
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $message], 500);
            }

            return back()->with('error', $message);
        }
    }
}
