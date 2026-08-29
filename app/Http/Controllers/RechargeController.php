<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Service;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\InvoiceService;
use App\Services\OrderService;
use App\Support\HistoryPeriod;
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
            ->withWhereHas('services', fn ($q) => $q->forCustomers())
            ->orderBy('sort_order')->get();

        $activeCategory = $categorySlug
            ? Category::where('slug', $categorySlug)->where('is_active', true)->firstOrFail()
            : $categories->first();

        $services = $activeCategory
            ? Service::where('category_id', $activeCategory->id)
                ->forCustomers()
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
        $service->loadMissing('provider');
        if (! auth()->user()?->is_admin) {
            abort_unless($service->isVisibleToCustomers(), 404);
        }
        if (strtolower((string) $service->type) === 'api' && ! auth()->user()?->is_admin) {
            abort(404);
        }
        $service->load(['category', 'plans', 'specialPrices' => fn ($sp) => $sp->where('user_id', auth()->id())]);
        $service->applyEffectivePricing(auth()->user());
        return view('recharge.form', compact('service'));
    }

    /** Step 3: confirm order & submit (supports both regular redirect and AJAX) */
    public function confirm(Request $request, OrderService $svc, InvoiceService $invoices)
    {
        $data = $request->validate([
            'service_id' => 'required|exists:services,id',
            'account_number' => 'required|string|min:4|max:30',
            'notify_number'  => 'nullable|string|max:30',
            'amount'         => 'required|numeric|min:10|max:100000',
        ]);

        $user = $request->user();
        $service = Service::where('is_active', true)->with(['category', 'provider'])->findOrFail($data['service_id']);
        if (! $service->isVisibleToCustomers()) {
            $msg = 'That service is not available right now.';
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $msg], 422);
            }
            return back()->withInput()->with('error', $msg);
        }
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

        $msg = $order->publicMessage();

        if ($request->wantsJson()) {
            $hasInvoice = $order->status === 'success' && $invoices->fileIsReady($order);
            return response()->json([
                'ok'          => ! $order->isFailedLike(),
                'status'      => $order->status,
                'message'     => $msg,
                'has_invoice' => $hasInvoice,
                'invoice_url' => $hasInvoice ? route('recharge.invoice', $order) : null,
                'download_url'=> $hasInvoice ? route('recharge.invoice.download', $order) : null,
                'order'       => [
                    'reference'    => $order->reference,
                    'service_name' => $order->customerServiceName(),
                    'account'      => $order->account_number,
                    'amount'       => (float) $order->amount,
                    'cashback'     => (float) $order->profit,
                    'redirect'     => route('recharge.show', $order),
                ],
            ]);
        }

        return redirect()->route('recharge.show', $order)->with('status', $msg);
    }

    /** Show order details / status */
    public function show(Order $order, InvoiceService $invoices): View
    {
        abort_unless(auth()->id() === $order->user_id || auth()->user()?->is_admin, 403);
        $order->load(['service', 'provider', 'cashback']);

        $this->tryMakeInvoice($order, $invoices);
        $hasInvoice = $invoices->fileIsReady($order);

        return view('recharge.show', compact('order', 'hasInvoice'));
    }

    /** Full-page invoice viewer (image-based). */
    public function invoice(Order $order, InvoiceService $invoices): View
    {
        abort_unless(auth()->id() === $order->user_id || auth()->user()?->is_admin, 403);

        $this->tryMakeInvoice($order, $invoices);
        $order->load(['service', 'provider']);

        $invoiceUrl = $invoices->fileIsReady($order)
            ? route('recharge.invoice.file', $order)
            : null;

        return view('recharge.invoice', compact('order', 'invoiceUrl'));
    }

    /** Stream the PNG so the browser does not need public/storage (DirectAdmin). */
    public function invoiceFile(Order $order, InvoiceService $invoices)
    {
        abort_unless(auth()->id() === $order->user_id || auth()->user()?->is_admin, 403);

        $this->tryMakeInvoice($order, $invoices);
        if (! $invoices->fileIsReady($order)) {
            abort(404, 'Receipt is not ready yet.');
        }

        return response()->file($invoices->absolutePath($order), [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /** Download the invoice PNG directly. */
    public function invoiceDownload(Request $request, Order $order, InvoiceService $invoices)
    {
        abort_unless(auth()->id() === $order->user_id || auth()->user()?->is_admin, 403);

        $ajax = $request->wantsJson() || $request->ajax();

        if ($order->status !== 'success') {
            $msg = 'Receipt is available once payment is successful.';
            if ($ajax) {
                return response()->json(['ok' => false, 'message' => $msg], 409);
            }

            return redirect()->route('recharge.show', $order)->with('error', $msg);
        }

        try {
            $invoices->ensureGenerated($order);
            $order->refresh();
        } catch (\Throwable $e) {
            logger()->warning('Invoice download generate failed: ' . $e->getMessage());
            $msg = 'Could not generate receipt: ' . $e->getMessage();
            if ($ajax) {
                return response()->json(['ok' => false, 'message' => $msg], 500);
            }

            return redirect()->route('recharge.invoice', $order)->with('error', $msg);
        }

        if (! $invoices->fileIsReady($order)) {
            $msg = 'Receipt file is missing. Please try again.';
            if ($ajax) {
                return response()->json(['ok' => false, 'message' => $msg], 404);
            }

            return redirect()->route('recharge.invoice', $order)->with('error', $msg);
        }

        return response()->download($invoices->absolutePath($order), $order->reference . '.png', [
            'Content-Type' => 'image/png',
        ]);
    }

    protected function tryMakeInvoice(Order $order, ?InvoiceService $invoices = null): void
    {
        if ($order->status !== 'success') {
            return;
        }

        try {
            ($invoices ?: app(InvoiceService::class))->ensureGenerated($order);
            $order->refresh();
        } catch (\Throwable $e) {
            logger()->warning('Invoice generation failed for '.$order->reference.': '.$e->getMessage());
        }
    }

    /** Customer order history */
    public function history(Request $request): View
    {
        $user = auth()->user();
        $period = HistoryPeriod::fromRequest($request);
        $orderQuery = $user->orders()->with(['service', 'cashback', 'complaints']);
        $period->apply($orderQuery, 'created_at', fn ($q) => $q->whereIn('status', ['pending', 'processing']));
        $orders = $orderQuery->latest()->paginate(25)->withQueryString();

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

        return view('recharge.history', compact('orders', 'period'));
    }
}
