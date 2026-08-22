<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Service;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\InvoiceService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RechargeController extends Controller
{
    /**
     * Public-facing services catalog.
     * Authenticated customers are redirected to the embedded dashboard catalog.
     */
    public function index(?string $categorySlug = null)
    {
        if (auth()->check()) {
            // Send authed users to dashboard; deep-link to the category via hash
            // so the JS tab-switcher opens on the right category instantly.
            $route = $categorySlug
                ? route('dashboard') . '#' . $categorySlug
                : route('dashboard');
            return redirect($route);
        }

        $categories = Category::where('is_active', true)
            ->withWhereHas('services', fn ($q) => $q->where('is_active', true))
            ->orderBy('sort_order')->get();

        $activeCategory = $categorySlug
            ? Category::where('slug', $categorySlug)->where('is_active', true)->firstOrFail()
            : $categories->first();

        $services = $activeCategory
            ? Service::where('category_id', $activeCategory->id)
                ->where('is_active', true)
                ->with('provider')
                ->orderBy('name')
                ->get()
            : collect();

        return view('recharge.index', compact('categories', 'activeCategory', 'services'));
    }

    /** Step 2: show amount form for a specific service */
    public function form(Service $service): View
    {
        abort_unless($service->is_active, 404);
        $service->load(['category', 'plans', 'specialPrices' => fn ($sp) => $sp->where('user_id', auth()->id())]);
        $service->applyEffectivePricing(auth()->user());
        return view('recharge.form', compact('service'));
    }

    /** Step 3: confirm order & submit (supports both regular redirect and AJAX) */
    public function confirm(Request $request, OrderService $svc, InvoiceService $invoices)
    {
        $data = $request->validate([
            'service_id' => 'required|exists:services,id',
            'account_number' => 'required|string|min:6|max:30',
            'notify_number'  => 'nullable|string|max:30',
            'amount'         => 'required|numeric|min:10|max:100000',
        ]);

        $user = $request->user();
        $service = Service::where('is_active', true)->with('category')->findOrFail($data['service_id']);
        if ($service->hidesNotifyNumber()) {
            $data['notify_number'] = null;
        }

        // Per-type minimum amount (LK market reality):
        // Mobile prepaid/reload generally starts at LKR 50;
        // bills/insurance/wallet topups can go as low as LKR 10.
        $type = strtolower((string) $service->type);
        $isBillLike = in_array($type, ['utility', 'postpaid', 'bill', 'insurance', 'wallet'], true);
        $minAmount = $isBillLike ? 10 : 50;

        if ((float) $data['amount'] < $minAmount) {
            $msg = $isBillLike
                ? "Minimum amount for bill payments is LKR {$minAmount}."
                : "Minimum recharge amount is LKR {$minAmount}. Please enter LKR {$minAmount} or more.";
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $msg], 422);
            }
            return back()->withInput()->with('error', $msg);
        }

        // Cashback preview
        $cashback = $service->calculateCashback((float) $data['amount']);

        try {
            $order = $svc->placeOrder(
                user:          $user,
                serviceId:     (int) $data['service_id'],
                accountNumber: $data['account_number'],
                amount:        (float) $data['amount'],
                notifyNumber:  $data['notify_number'] ?? null,
            );
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => 'Order failed: ' . $e->getMessage()], 422);
            }
            return back()->withInput()->with('error', 'Order failed: ' . $e->getMessage());
        }

        $order->load(['service', 'cashback']);

        // Generate invoice image only when the order is synchronously successful.
        // Pending orders will get an invoice when the cron reconciles them to success.
        $invoiceUrl = null;
        if ($order->status === 'success' && !$order->invoice_path) {
            try {
                $invRel = $invoices->generate($order);
                $invoiceUrl = asset('storage/' . $invRel);
                $order->refresh();
            } catch (\Throwable $e) {
                logger()->error('Invoice generation failed: ' . $e->getMessage());
            }
        } elseif ($order->invoice_path) {
            $invoiceUrl = asset('storage/' . $order->invoice_path);
        }

        if ($order->status === 'success') {
            $cashbackNote = (float) $order->profit > 0
                ? ' You earned LKR ' . number_format($order->profit, 2) . ' cashback.'
                : '';
            $msg = 'Recharge successful! Your ' . $order->service->name . ' of LKR ' . number_format((float) $order->amount, 2) . ' has been processed.' . $cashbackNote;
        } elseif ($order->status === 'pending') {
            $msg = 'Your recharge request has been sent and is being processed. You can track its status on the order details page.';
        } else {
            $msg = 'Order failed: ' . ($order->message ?? 'Unknown error.');
        }

        if ($request->wantsJson()) {
            $hasInvoice = $order->status === 'success' && (bool) $order->invoice_path;
            $invoiceUrl = $hasInvoice ? asset('storage/' . $order->invoice_path) : null;
            return response()->json([
                'ok'          => $order->status !== 'failed',
                'status'      => $order->status,
                'message'     => $msg,
                'has_invoice' => $hasInvoice,
                'invoice_url' => $hasInvoice ? route('recharge.invoice', $order) : null,
                'download_url'=> $hasInvoice ? route('recharge.invoice.download', $order) : null,
                'order'       => [
                    'reference'    => $order->reference,
                    'service_name' => $order->service->name,
                    'account'      => $order->account_number,
                    'amount'       => (float) $order->amount,
                    'cashback'     => (float) $order->profit,
                    'redirect'     => route('recharge.invoice', $order),
                ],
            ]);
        }

        return redirect()->route('recharge.show', $order)->with('status', $msg);
    }

    /** Show order details / status */
    public function show(Order $order): View
    {
        abort_unless(auth()->id() === $order->user_id || auth()->user()?->is_admin, 403);
        $order->load(['service', 'provider', 'cashback']);

        // Generate invoice lazily if somehow missing (e.g. older order, or cron
        // hadn't generated it yet for a reconciled success)
        if ($order->status === 'success' && !$order->invoice_path) {
            try {
                app(InvoiceService::class)->generate($order);
                $order->refresh();
            } catch (\Throwable $e) {
                logger()->warning('Lazy invoice generation failed: ' . $e->getMessage());
            }
        }

        return view('recharge.show', compact('order'));
    }

    /** Full-page invoice viewer (image-based). */
    public function invoice(Order $order, InvoiceService $invoices): View
    {
        abort_unless(auth()->id() === $order->user_id || auth()->user()?->is_admin, 403);

        if ($order->status === 'success' && !$order->invoice_path) {
            try {
                $invoices->generate($order);
                $order->refresh();
            } catch (\Throwable $e) {
                logger()->warning('Invoice generation failed: ' . $e->getMessage());
            }
        }

        $invoiceUrl = $order->invoice_path ? asset('storage/' . $order->invoice_path) : null;
        $order->load(['service', 'provider']);

        return view('recharge.invoice', compact('order', 'invoiceUrl'));
    }

    /** Download the invoice PNG directly. */
    public function invoiceDownload(Order $order, InvoiceService $invoices)
    {
        abort_unless(auth()->id() === $order->user_id || auth()->user()?->is_admin, 403);

        if ($order->status !== 'success') {
            return back()->with('error', 'Receipt is available once payment is successful.');
        }

        if (!$order->invoice_path) {
            try {
                $invoices->generate($order);
                $order->refresh();
            } catch (\Throwable $e) {
                return back()->with('error', 'Could not generate receipt: ' . $e->getMessage());
            }
        }

        $abs = storage_path('app/public/' . $order->invoice_path);
        if (!file_exists($abs)) {
            // Regenerate if missing from disk
            try {
                $invoices->generate($order);
                $order->refresh();
                $abs = storage_path('app/public/' . $order->invoice_path);
            } catch (\Throwable $e) {
                abort(404, 'Receipt file missing');
            }
        }

        return response()->download($abs, $order->reference . '.png', [
            'Content-Type' => 'image/png',
        ]);
    }

    /** Customer order history */
    public function history(): View
    {
        $user = auth()->user();
        $orders = $user->orders()->with(['service', 'cashback', 'complaints'])->latest()->paginate(25);

        // Look up wallet transactions tied to these orders (cashback credits,
        // and any future order-payment debits) so we can show before→after
        // balance changes on the history table.
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id]);
        $orderIds = $orders->getCollection()->pluck('id')->filter()->all();
        $txByOrder = [];
        if ($orderIds) {
            $txs = WalletTransaction::where('wallet_id', $wallet->id)
                ->where('transactable_type', Order::class)
                ->whereIn('transactable_id', $orderIds)
                ->get();
            foreach ($txs as $t) {
                $txByOrder[$t->transactable_id][] = $t;
            }
        }
        foreach ($orders as $o) {
            $o->setRelation('wallet_txs', collect($txByOrder[$o->id] ?? []));
        }

        return view('recharge.history', compact('orders'));
    }
}
