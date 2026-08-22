<?php

namespace App\Services;

use App\Models\Cashback;
use App\Models\Order;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Support\WalletLimits;
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
            $hrc = Provider::query()
                ->where(function ($q) {
                    $q->where('slug', 'happy-recharge-center')
                      ->orWhere('api_class', 'happy_recharge_center');
                })
                ->first();
            if ($hrc && $hrc->is_active && $hrc->hasCredentials()) {
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

        WalletLimits::assertCanDebit($user, $wallet, $amount);

        // Cashback (profit) amount per this order — credited ONLY after order success.
        $profit = $service->calculateCashback($amount, $user);

        // Atomically create order + debit wallet + write debit transaction
        // record in ONE DB transaction so they can never go out of sync.
        $order = null;
        $balanceBeforeDebit = (float) $wallet->balance;
        DB::transaction(function () use ($user, $service, $provider, $accountNumber, $notifyNumber, $amount, $profit, $wallet, $balanceBeforeDebit, &$order) {
            // Re-lock wallet inside the transaction (the lock above was outside)
            $w = Wallet::whereKey($wallet->id)->lockForUpdate()->first() ?? $wallet;
            WalletLimits::assertCanDebit($user, $w, $amount);
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

        $this->syncWalletNotice($user->id);

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
        $this->syncWalletNotice((int) $order->user_id);
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
        $this->syncWalletNotice((int) $order->user_id);
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
     * Re-sends THE SAME order through Topup Mart. Wallet is not refunded or
     * re-debited. We probe HRC for a cancel endpoint (their public docs do not
     * include one); if cancel is unsupported the original HRC request may still
     * complete later and must be reconciled manually.
     */
    public function failoverToTopupMart(Order $order, User $admin, ?string $note = null): Order
    {
        $order->loadMissing(['provider', 'service', 'user']);

        if ($order->status !== 'pending' && $order->status !== 'processing') {
            throw new \RuntimeException('Only pending/processing orders can be failed over.');
        }

        $hrc = $order->provider;
        if (! $hrc || ! $hrc->isHappyRechargeCenter()) {
            throw new \RuntimeException('Failover is only supported for Happy Recharge Center orders.');
        }

        $topup = Provider::query()
            ->where(function ($q) {
                $q->where('slug', 'topup-mart')->orWhere('api_class', 'topup_mart');
            })
            ->first();
        if (! $topup || ! $topup->is_active) {
            throw new \RuntimeException('Topup Mart provider is not active — cannot fail over.');
        }

        $fallbackService = $this->findTopupMartDthEquivalent($order->service, $topup);
        if (! $fallbackService) {
            throw new \RuntimeException('No matching Topup Mart DTH service found for this operator — import Topup Mart services first (DTH rows can stay hidden from customers).');
        }

        $cancelResult = ['status' => 'unsupported', 'message' => 'Cancel not attempted'];
        try {
            $hrcClient = ProviderFactory::make($hrc);
            if (method_exists($hrcClient, 'cancel')) {
                $cancelResult = $hrcClient->cancel($order);
            }
        } catch (\Throwable $e) {
            $cancelResult = ['status' => 'error', 'message' => $e->getMessage()];
            Log::warning('HRC cancel during failover failed', [
                'order' => $order->reference,
                'error' => $e->getMessage(),
            ]);
        }

        $stamp = now()->timezone('Asia/Colombo')->format('Y-m-d H:i');
        $noteText = "[ADMIN FAILOVER {$stamp} by {$admin->name}] "
            . "Same order re-sent via Topup Mart. "
            . "HRC cancel: " . ($cancelResult['status'] ?? 'unknown')
            . ' — ' . ($cancelResult['message'] ?? 'n/a')
            . ' If HRC later completes the original transaction, reconcile manually.';
        if ($note) {
            $noteText .= ' Admin note: ' . $note;
        }

        $prevResp = is_array($order->provider_response) ? $order->provider_response : [];
        $originalProviderId = $order->provider_id;
        $originalServiceId  = $order->service_id;
        $originalTxn        = $order->provider_txn_id;

        $order->provider_id     = $topup->id;
        $order->service_id      = $fallbackService->id;
        $order->provider_txn_id = null;
        $order->status          = 'processing';
        $order->provider_status = 'failover_processing';
        $order->processed_at    = now();
        $order->completed_at    = null;
        $order->message         = trim(($order->message ? $order->message . "\n\n" : '') . $noteText);
        $order->provider_response = array_merge($prevResp, [
            '_failover' => [
                'at'               => now()->toDateTimeString(),
                'by_admin_id'      => $admin->id,
                'from_provider_id' => $originalProviderId,
                'from_service_id'  => $originalServiceId,
                'from_txn'         => $originalTxn,
                'to_provider_id'   => $topup->id,
                'to_service_id'    => $fallbackService->id,
                'cancel'           => $cancelResult,
            ],
        ]);
        $order->save();
        $order->setRelation('provider', $topup);
        $order->setRelation('service', $fallbackService);

        $resp = null;
        $timedOut = false;
        try {
            $resp = ProviderFactory::make($topup)->recharge($order);
        } catch (ConnectionException $e) {
            $timedOut = true;
            $resp = ['status' => 'pending', 'message' => 'Failover sent to Topup Mart — waiting for confirmation…'];
            Log::warning('TopupMart failover TIMEOUT', [
                'order' => $order->reference,
                'error' => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            Log::error('TopupMart failover exception', [
                'order' => $order->reference,
                'error' => $e->getMessage(),
            ]);
            $resp = ['status' => 'failed', 'message' => 'Failover provider error: ' . $e->getMessage()];
        }

        $merged = is_array($order->provider_response) ? $order->provider_response : [];
        $order->provider_response = array_merge($merged, ['_failover_response' => $resp, '_timed_out' => $timedOut]);
        if (! empty($resp['transaction_id'])) {
            $order->provider_txn_id = $resp['transaction_id'];
        }
        if (! empty($resp['message'])) {
            $order->message = ($order->message ? $order->message . "\n\n" : '') . $resp['message'];
        }

        $status = strtolower((string) ($resp['status'] ?? 'failed'));
        if ($status === 'success') {
            $order->save();
            $this->markSuccess($order);
        } elseif ($status === 'pending' || $timedOut) {
            $order->status = 'pending';
            $order->provider_status = $timedOut ? 'awaiting_confirmation' : 'pending';
            $order->save();
        } else {
            $order->save();
            $this->markFailed($order, $resp['message'] ?? 'Topup Mart rejected the failover recharge');
        }

        Log::info("Admin failover: {$order->reference} HRC → TopupMart by admin {$admin->id}", [
            'status' => $order->fresh()->status,
            'cancel' => $cancelResult['status'] ?? null,
        ]);

        return $order->fresh(['provider', 'service', 'user']);
    }

    /** Email the customer if this debit left their wallet below the minimum. */
    protected function syncWalletNotice(int $userId): void
    {
        try {
            app(WalletBalanceNotifier::class)->syncUser($userId);
        } catch (\Throwable $e) {
            Log::warning('Wallet low-balance check failed: ' . $e->getMessage());
        }
    }

    /** Match an HRC DTH service to the hidden Topup Mart DTH equivalent (by opcode map / name). */
    protected function findTopupMartDthEquivalent(?Service $hrcService, Provider $topup): ?Service
    {
        if (! $hrcService) {
            return null;
        }

        $metaOp = $hrcService->meta['failover_op_code'] ?? null;
        if ($metaOp) {
            $found = Service::where('provider_id', $topup->id)->where('op_code', (string) $metaOp)->first();
            if ($found) return $found;
        }

        foreach (\App\Services\Providers\HappyRechargeCenter::dthCatalog() as $row) {
            $sameCode = (string) $row['op_code'] === (string) $hrcService->op_code;
            $sameName = strcasecmp((string) $row['name'], (string) $hrcService->name) === 0;
            if ($sameCode || $sameName) {
                $found = Service::where('provider_id', $topup->id)
                    ->where('op_code', (string) $row['failover_op_code'])
                    ->first();
                if ($found) return $found;
            }
        }

        $name = strtolower((string) $hrcService->name);
        $needles = [
            'airtel'   => '120',
            'dish'     => '121',
            'sun'      => '122',
            'tata'     => '123',
            'play'     => '123',
            'videocon' => '124',
            'd2h'      => '124',
        ];
        foreach ($needles as $needle => $op) {
            if (str_contains($name, $needle)) {
                $found = Service::where('provider_id', $topup->id)->where('op_code', $op)->first();
                if ($found) return $found;
            }
        }

        return Service::where('provider_id', $topup->id)
            ->where(function ($q) use ($hrcService) {
                $q->where('type', 'dth');
                if ($hrcService->category_id) {
                    $q->orWhere('category_id', $hrcService->category_id);
                }
            })
            ->first();
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
