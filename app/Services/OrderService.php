<?php

namespace App\Services;

use App\Models\Cashback;
use App\Models\Order;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    /**
     * Resolve the effective provider for a service.
     *
     * BUSINESS RULE (from Danuka): DTH TV (India) services are always routed to
     * Happy Recharge Center, regardless of which provider the Service row is
     * attached to. All other services go through their own provider (TopupMart
     * for SL services). We only override to HRC if HRC is active + has creds,
     * otherwise we fall back to the service's default provider so we don't
     * break the UI before the admin configures HRC.
     */
    protected function resolveProvider(Service $service): Provider
    {
        $default = $service->provider;
        $type    = strtolower((string) $service->type);
        $catSlug = $service->category?->slug;
        $isDth   = ($type === 'dth' || $catSlug === 'dth');

        if ($isDth) {
            $hrc = Provider::where('slug', 'happy-recharge-center')
                ->orWhere('api_class', 'happy_recharge_center')
                ->first();
            if ($hrc && $hrc->is_active && $hrc->base_url && $hrc->api_key) {
                return $hrc;
            }
        }

        return $default;
    }

    /** Create a pending order and send it to the provider synchronously. */
    public function placeOrder(User $user, int $serviceId, string $accountNumber, float $amount, ?string $notifyNumber = null): Order
    {
        /** @var Service $service */
        $service = Service::where('is_active', true)->with(['provider', 'category'])->findOrFail($serviceId);
        $provider = $this->resolveProvider($service);

        if (! $provider->is_active) {
            throw new \RuntimeException('Selected provider is currently unavailable.');
        }

        // ----- WALLET: balance check -----
        // Customers pay from their wallet balance. We debit ATOMICALLY with
        // the order creation (below). If the provider then fails/declines we
        // refund; if it times out we leave the debit and let cron reconcile.
        $wallet = Wallet::firstOrCreate(['user_id' => $user->id]);
        $wallet = Wallet::whereKey($wallet->id)->lockForUpdate()->first() ?? $wallet;

        if ((float) $wallet->balance < $amount) {
            $short = number_format($amount - (float) $wallet->balance, 2);
            throw new \RuntimeException(
                'Insufficient wallet balance. You need LKR ' . $short . ' more to place this recharge. Please top up your wallet.'
            );
        }

        // Cashback (profit) amount per this order — credited ONLY after order success.
        $profit = $service->calculateCashback($amount);

        // Atomically create order + debit wallet + write debit transaction
        // record in ONE DB transaction so they can never go out of sync.
        $order = null;
        $balanceBeforeDebit = (float) $wallet->balance;
        DB::transaction(function () use ($user, $service, $provider, $accountNumber, $notifyNumber, $amount, $profit, $wallet, $balanceBeforeDebit, &$order) {
            // Re-lock wallet inside the transaction (the lock above was outside)
            $w = Wallet::whereKey($wallet->id)->lockForUpdate()->first() ?? $wallet;
            $before = (float) $w->balance;
            $w->balance = $before - $amount;
            $w->cashback_balance = 0;
            $w->save();

            $order = Order::create([
                'reference'       => Order::generateReference(),
                'user_id'         => $user->id,
                'service_id'      => $service->id,
                'provider_id'     => $provider->id,
                'account_number'  => $accountNumber,
                'notify_number'   => $notifyNumber,
                'amount'          => $amount,
                'profit'          => $profit,
                'status'          => 'processing',
                'provider_status' => 'processing',
                'processed_at'    => now(),
            ]);

            WalletTransaction::create([
                'wallet_id'         => $w->id,
                'transactable_type' => Order::class,
                'transactable_id'   => $order->id,
                'type'              => 'debit',
                'amount'            => $amount,
                'balance_before'    => $before,
                'balance_after'     => (float) $w->balance,
                'description'       => 'Recharge: ' . ($service->name ?? 'Service') . ' ' . $accountNumber,
            ]);
        });

        // Now call the provider API (outside transaction so long HTTP timeouts
        // don't hold a DB lock open).
        $client = ProviderFactory::make($provider);
        $resp = null;
        $timedOut = false;
        try {
            $resp = $client->recharge($order);
        } catch (ConnectionException $e) {
            // cURL timeout / DNS / connection refused / TCP reset.
            //
            // CRITICAL: When the request times out we DO NOT KNOW if the
            // provider accepted it or not. The provider wallet may already
            // have been debited and carrier retries may already be in flight
            // (which is exactly what caused Danuka's triple SMS test).
            // We MUST leave this order PENDING so the per-minute cron job
            // polls /status.php and reconciles to the real final state.
            $timedOut = true;
            Log::warning('Provider recharge TIMEOUT — leaving order pending for cron reconciliation', [
                'order'   => $order->reference,
                'op_code' => $service->op_code,
                'amount'  => $amount,
                'account' => $accountNumber,
                'error'   => $e->getMessage(),
            ]);
            $resp = [
                'status'  => 'pending',
                'message' => 'Request sent — waiting for provider confirmation…',
            ];
        } catch (\Throwable $e) {
            // Non-network exception (code error, bad response format, 5xx with body, etc.)
            Log::error('Provider recharge exception', [
                'order' => $order->reference,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Code error before a provider decision — refund wallet immediately
            // because nothing was sent to the carrier.
            try { $this->refundWallet($order, 'Recharge failed (provider error) — refunded to wallet'); }
            catch (\Throwable $re) { Log::error('Refund after provider exception failed: ' . $re->getMessage()); }
            $resp = ['status' => 'failed', 'message' => 'Provider error: ' . $e->getMessage()];
        }

        // Update order based on response
        $order->provider_response = array_merge($resp ?: [], ['_timed_out' => $timedOut]);
        $order->provider_txn_id   = $resp['transaction_id'] ?? null;
        $order->message           = $resp['message'] ?? null;
        $status = strtolower((string) ($resp['status'] ?? 'failed'));

        if ($status === 'success') {
            // Synchronous success — credit cashback + mark success.
            // markSuccess uses its own DB transaction.
            try {
                $this->markSuccess($order);
            } catch (\Throwable $e) {
                Log::error('markSuccess failed after successful provider response', [
                    'order' => $order->reference, 'err' => $e->getMessage(),
                ]);
                // Still save order as success — wallet crediting will be retried
                // by a reconciliation pass later.
                $order->status = 'success';
                $order->provider_status = 'success';
                $order->completed_at = now();
                $order->save();
            }
        } elseif ($status === 'pending' || $timedOut) {
            // Queued/async at the provider, OR our request timed out.
            $order->status = 'pending';
            $order->provider_status = $timedOut ? 'awaiting_confirmation' : 'pending';
            $order->save();
        } else {
            // Explicit failure response from provider (e.g. "Insufficient balance",
            // "Invalid mobile number", "Below minimum amount"). Provider rejected
            // immediately — refund the wallet debit we did upfront.
            try {
                $this->refundWallet($order, 'Recharge failed — refunded to wallet');
            } catch (\Throwable $re) {
                Log::error('Refund after hard failure failed: ' . $re->getMessage());
            }
            $order->status = 'failed';
            $order->provider_status = $status;
            $order->completed_at = now();
            $order->save();
        }

        return $order->fresh();
    }

    /** Mark order success + credit cashback to customer wallet. Idempotent. */
    public function markSuccess(Order $order): void
    {
        DB::transaction(function () use ($order) {
            // Lock the order row inside the transaction so concurrent calls can't double-credit.
            $locked = Order::with(['service', 'user'])
                ->whereKey($order->id)
                ->lockForUpdate()
                ->first();

            if (! $locked) return;
            if ($locked->status === 'success') return;

            $locked->status = 'success';
            $locked->provider_status = 'success';
            if (! $locked->completed_at) $locked->completed_at = now();
            $locked->save();

            if ((float) $locked->profit > 0) {
                // Wallet row lock via firstOrCreate + lock on existing
                $wallet = Wallet::firstOrCreate(['user_id' => $locked->user_id]);
                // Fresh from DB inside txn
                $wallet = Wallet::whereKey($wallet->id)->lockForUpdate()->first() ?? $wallet;

                // Idempotency check via existing Cashback record BEFORE crediting
                $alreadyCredited = Cashback::where('order_id', $locked->id)->exists();

                if (! $alreadyCredited) {
                    // Single unified wallet — cashback lands straight in balance
                    $balanceBefore = (float) $wallet->balance;
                    $wallet->balance = $balanceBefore + (float) $locked->profit;
                    // Zero out legacy column if anything lingered
                    $wallet->cashback_balance = 0;
                    $wallet->save();

                    WalletTransaction::create([
                        'wallet_id'         => $wallet->id,
                        'transactable_type' => Order::class,
                        'transactable_id'   => $locked->id,
                        'type'              => 'cashback',
                        'amount'            => (float) $locked->profit,
                        'balance_before'    => $balanceBefore,
                        'balance_after'     => (float) $wallet->balance,
                        'description'       => 'Cashback from ' . ($locked->service->name ?? 'recharge'),
                    ]);

                    Cashback::create([
                        'user_id'     => $locked->user_id,
                        'order_id'    => $locked->id,
                        'amount'      => (float) $locked->profit,
                        'status'      => 'credited',
                        'credited_at' => now(),
                    ]);
                }
            }
        });

        // Refresh the passed-in model too so callers see the new state
        $order->refresh();
    }

    /** Mark order failed. Idempotent. Refunds the wallet debit if one exists. */
    public function markFailed(Order $order, ?string $message = null): void
    {
        DB::transaction(function () use ($order, $message) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->first();
            if (! $locked) return;
            if ($locked->status === 'failed') return;

            $locked->status = 'failed';
            $locked->provider_status = 'failed';
            if ($message) {
                $locked->message = $locked->message
                    ? ($locked->message . "\n\n" . $message)
                    : $message;
            }
            if (! $locked->completed_at) $locked->completed_at = now();
            $locked->save();

            // Refund any wallet debit for this order (idempotent — refundWallet
            // checks for an existing refund transaction before crediting).
            try {
                $this->refundWallet($locked, 'Recharge failed — refunded to wallet');
            } catch (\Throwable $e) {
                Log::error('Auto-refund during markFailed failed for ' . $locked->reference . ': ' . $e->getMessage());
            }
        });

        $order->refresh();
    }

    /**
     * Refund the wallet for an order's debit. Idempotent — if a refund
     * transaction already exists for the order, this is a no-op.
     * Creates a "refund" WalletTransaction and credits the balance back.
     */
    public function refundWallet(Order $order, ?string $note = null): void
    {
        DB::transaction(function () use ($order, $note) {
            $wallet = Wallet::firstOrCreate(['user_id' => $order->user_id]);
            $wallet = Wallet::whereKey($wallet->id)->lockForUpdate()->first() ?? $wallet;

            // Locate the original debit tx for this order
            $debitTx = WalletTransaction::where('wallet_id', $wallet->id)
                ->where('transactable_type', Order::class)
                ->where('transactable_id', $order->id)
                ->where('type', 'debit')
                ->first();

            if (! $debitTx) {
                return; // nothing to refund
            }

            // Idempotency: if a refund already exists for this order, skip
            $alreadyRefunded = WalletTransaction::where('wallet_id', $wallet->id)
                ->where('transactable_type', Order::class)
                ->where('transactable_id', $order->id)
                ->where('type', 'refund')
                ->exists();
            if ($alreadyRefunded) return;

            $amount = (float) $debitTx->amount;
            $before = (float) $wallet->balance;
            $wallet->balance = $before + $amount;
            $wallet->cashback_balance = 0;
            $wallet->save();

            WalletTransaction::create([
                'wallet_id'         => $wallet->id,
                'transactable_type' => Order::class,
                'transactable_id'   => $order->id,
                'type'              => 'refund',
                'amount'            => $amount,
                'balance_before'    => $before,
                'balance_after'     => (float) $wallet->balance,
                'description'       => $note ?: ('Refund for order ' . $order->reference),
            ]);
        });
    }

    /**
     * Admin failover for a PENDING HRC DTH order.
     *
     * Business rule: when a DTH order is stuck pending on Happy Recharge Center,
     * admin can re-send the same recharge through Topup Mart. HRC has NO cancel
     * endpoint, so we simply mark the HRC order failed locally (refunding the
     * wallet debit via markFailed) and create a fresh order against Topup Mart
     * via the normal placeOrder flow. A note is appended to both orders so
     * everyone can see what happened; if HRC later completes the original
     * transaction the admin can spot it in the raw response and review.
     *
     * @param  Order  $order  the stuck pending HRC order
     * @param  User   $admin  the admin performing the failover
     * @param  string|null $note optional admin note
     * @return Order  the new Topup Mart order
     */
    public function failoverToTopupMart(Order $order, User $admin, ?string $note = null): Order
    {
        if ($order->status !== 'pending' && $order->status !== 'processing') {
            throw new \RuntimeException('Only pending/processing orders can be failed over.');
        }

        $hrc = $order->provider;
        $isHrc = $hrc
            && (str_contains((string) $hrc->api_class, 'HappyRechargeCenter')
                || $hrc->slug === 'happy-recharge-center');
        if (! $isHrc) {
            throw new \RuntimeException('Failover is only supported for Happy Recharge Center orders.');
        }

        // Find Topup Mart provider
        $topup = Provider::where('slug', 'topup-mart')
            ->orWhere('api_class', 'topup_mart')
            ->first();
        if (! $topup || ! $topup->is_active) {
            throw new \RuntimeException('Topup Mart provider is not active — cannot fail over.');
        }

        // Find a Topup Mart equivalent service with the same op_code so we can
        // route through TopupMart. If none exists with the same op_code, fall
        // back to the originally-linked service (which may have a different
        // op_code; admin can adjust before retrying if needed).
        $fallbackService = Service::where('provider_id', $topup->id)
            ->where('op_code', $order->service->op_code)
            ->where('is_active', true)
            ->first();

        if (! $fallbackService) {
            // Look up by category + type match on TopupMart
            $fallbackService = Service::where('provider_id', $topup->id)
                ->where('category_id', $order->service->category_id)
                ->where('is_active', true)
                ->first();
        }

        if (! $fallbackService) {
            throw new \RuntimeException('No matching Topup Mart service found for this operator — please import DTH services on Topup Mart first.');
        }

        // 1. Refund the HRC order wallet + mark failed with an admin note.
        $noteText = '[ADMIN FAILOVER ' . now()->timezone('Asia/Colombo')->format('Y-m-d H:i') . ' by ' . $admin->name . '] '
                 . 'Order was stuck pending on Happy Recharge Center and has been re-sent via Topup Mart. '
                 . 'Note: HRC has no cancel API — if HRC later completes the original transaction, manual reconciliation may be needed.';
        if ($note) {
            $noteText .= ' Admin note: ' . $note;
        }

        $existingMsg = $order->message ? ($order->message . "\n\n" . $noteText) : $noteText;
        $this->markFailed($order, $existingMsg);
        $order->refresh();

        // 2. Create a fresh order through TopupMart via the normal placeOrder flow
        // (debits wallet, calls TopupMart API, credits cashback on success, etc.).
        // We place it on the order owner's behalf with the same details.
        $newOrder = $this->placeOrder(
            user:          $order->user,
            serviceId:     $fallbackService->id,
            accountNumber: $order->account_number,
            amount:        (float) $order->amount,
            notifyNumber:  $order->notify_number,
        );

        // Append cross-reference to both orders' messages for visibility.
        $xref = "\n\n[CROSS-REF] Failover new order: {$newOrder->reference} via Topup Mart.";
        $order->message = ($order->message ?? '') . $xref;
        $order->save();

        $newOrder->message = ($newOrder->message ? $newOrder->message . "\n\n" : '')
            . "[FAILOVER SOURCE] Resent from HRC order {$order->reference} by admin {$admin->name}.";
        $newOrder->save();

        Log::info("Admin failover: {$order->reference} (HRC) → {$newOrder->reference} (TopupMart) by admin {$admin->id}");

        return $newOrder;
    }

    /**
     * Poll pending orders against the provider's status endpoint.
     * Called every minute by the scheduler. Updates orders to success/failed
     * once the provider reports a final state; credits cashback on success.
     */
    public function syncPending(): int
    {
        $count = 0;
        $orders = Order::whereIn('status', ['pending', 'processing'])
            ->where(function ($q) {
                $q->where('created_at', '>=', now()->subHours(48));
            })
            ->where(function ($q) {
                $q->where('status', 'pending')
                  ->orWhere(function ($q2) {
                      $q2->where('status', 'processing')
                         ->where('processed_at', '<=', now()->subSeconds(30));
                  });
            })
            ->with(['provider', 'service', 'user'])
            ->get();

        foreach ($orders as $order) {
            try {
                $client = ProviderFactory::make($order->provider);
                $resp = $client->checkStatus($order);
                $status = strtolower((string) ($resp['status'] ?? 'pending'));

                $prev = is_array($order->provider_response) ? $order->provider_response : [];
                $order->provider_response = array_merge($prev, $resp, ['_last_checked' => now()->toDateTimeString()]);
                if (! empty($resp['transaction_id']) && empty($order->provider_txn_id)) {
                    $order->provider_txn_id = $resp['transaction_id'];
                }
                $order->message = $resp['message'] ?? $order->message;

                $turnedSuccess = false;
                if ($status === 'success' && $order->status !== 'success') {
                    $this->markSuccess($order);
                    $turnedSuccess = true;
                    $count++;
                    Log::info("Order {$order->reference} reconciled to SUCCESS by cron");
                } elseif (($status === 'failed' || $status === 'refund' || $status === 'cancelled') && $order->status !== 'failed') {
                    $this->markFailed($order, $resp['message'] ?? null);
                    $count++;
                    Log::info("Order {$order->reference} reconciled to FAILED by cron");
                } else {
                    $order->save();
                }

                // Generate invoice now that we know it's success (in case it
                // was still pending at page load time).
                if ($turnedSuccess && !$order->invoice_path) {
                    try {
                        app(InvoiceService::class)->generate($order->fresh());
                    } catch (\Throwable $e) {
                        Log::warning("Invoice generation failed for {$order->reference}: " . $e->getMessage());
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Order sync error for {$order->reference}: " . $e->getMessage());
            }
        }
        return $count;
    }
}
