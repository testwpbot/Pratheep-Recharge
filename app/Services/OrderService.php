<?php

namespace App\Services;

use App\Models\Cashback;
use App\Models\Order;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use App\Models\Setting;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Support\PreferredRoute;
use App\Support\ProviderErrors;
use App\Support\ServicePairs;
use App\Support\WalletLimits;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    /** @var list<string> */
    public array $lastSyncReport = [];

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
        /** @var Service $picked */
        $picked = Service::where('is_active', true)->with(['provider', 'category'])->findOrFail($serviceId);
        $service = PreferredRoute::faceService($picked);
        $send = PreferredRoute::startService($service);
        $provider = $this->resolveProvider($send);

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
        DB::transaction(function () use ($user, $service, $send, $provider, $accountNumber, $notifyNumber, $amount, $profit, $wallet, $balanceBeforeDebit, &$order) {
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
                'provider_response' => PreferredRoute::orderMeta($service, $send),
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
            // Code error before a provider decision — refund happens in the
            // hard-fail branch below (idempotent).
            $resp = ['status' => 'failed', 'message' => 'Provider error: ' . $e->getMessage()];
        }

        $this->applyProviderResult($order, is_array($resp) ? $resp : [], $timedOut, true);

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

    /**
     * Mark order failed and put the wallet debit back. Idempotent.
     * After a successful wallet credit the order status is "refunded"
     * (not "failed") so lists show Refunded.
     */
    public function markFailed(Order $order, ?string $message = null): void
    {
        DB::transaction(function () use ($order, $message) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->first();
            if (! $locked) return;
            if ($locked->status === Order::STATUS_REFUNDED) return;

            $locked->provider_status = 'failed';
            if ($message) {
                $locked->message = $locked->message
                    ? ($locked->message . "\n\n" . $message)
                    : $message;
            }
            if (! $locked->completed_at) $locked->completed_at = now();

            $refunded = $this->tryRefundWallet($locked, 'Recharge failed — refunded to wallet');
            $locked->status = $refunded ? Order::STATUS_REFUNDED : Order::STATUS_FAILED;
            $locked->save();
        });

        $order->refresh();
        $this->syncWalletNotice((int) $order->user_id);
    }

    /**
     * Refund the wallet for an order's debit. Idempotent — if a refund
     * transaction already exists for the order, this is a no-op.
     * Creates a "refund" WalletTransaction and credits the balance back.
     *
     * @return bool true when a refund row exists for this order (new or already there)
     */
    public function refundWallet(Order $order, ?string $note = null): bool
    {
        return (bool) DB::transaction(function () use ($order, $note) {
            $wallet = Wallet::firstOrCreate(['user_id' => $order->user_id]);
            $wallet = Wallet::whereKey($wallet->id)->lockForUpdate()->first() ?? $wallet;

            // Locate the original debit tx for this order
            $debitTx = WalletTransaction::where('wallet_id', $wallet->id)
                ->where('transactable_type', Order::class)
                ->where('transactable_id', $order->id)
                ->where('type', 'debit')
                ->first();

            if (! $debitTx) {
                return false; // nothing to refund
            }

            // Idempotency: if a refund already exists for this order, skip
            $alreadyRefunded = WalletTransaction::where('wallet_id', $wallet->id)
                ->where('transactable_type', Order::class)
                ->where('transactable_id', $order->id)
                ->where('type', 'refund')
                ->exists();
            if ($alreadyRefunded) return true;

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

            return true;
        });
    }

    /** Refund without throwing — logs and returns false on error. */
    protected function tryRefundWallet(Order $order, ?string $note = null): bool
    {
        try {
            return $this->refundWallet($order, $note);
        } catch (\Throwable $e) {
            Log::error('Wallet refund failed for ' . $order->reference . ': ' . $e->getMessage());
            return false;
        }
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

    /**
     * Admin switches a pending Dialog order to Dialog API (or the other way).
     * Same order, same wallet debit — only the op_code / service changes.
     */
    public function transferToPairedService(Order $order, ?User $admin = null, ?string $note = null): Order
    {
        $order->loadMissing(['provider', 'service.provider', 'user']);

        if ($order->status !== 'pending' && $order->status !== 'processing') {
            throw new \RuntimeException('Only a pending order can be sent through the other Dialog route.');
        }

        $fromService = $order->service;
        $fromCode = $order->sendOpCode() ?: (string) ($fromService?->op_code ?? '');
        $partner = ServicePairs::partnerFromOrder($order);
        if (! $partner) {
            throw new \RuntimeException('This service has no matching Dialog / Dialog API pair.');
        }

        $toProvider = $partner->provider ?: $order->provider;
        if (! $toProvider) {
            throw new \RuntimeException('The other Dialog route has no provider set.');
        }

        $prevResp = is_array($order->provider_response) ? $order->provider_response : [];
        $count = (int) ($prevResp['_transfer_count'] ?? 0) + 1;
        $clientRef = $order->reference . '-T' . $count;

        $stamp = now()->timezone('Asia/Colombo')->format('Y-m-d H:i');
        $who = $admin?->name ?: 'system';
        $tag = $admin ? 'ADMIN SWITCH' : 'AUTO SWITCH';
        $fromLabel = PreferredRoute::adminLabel($fromService, $fromCode);
        $toLabel = PreferredRoute::adminLabel($partner);
        $noteText = "[{$tag} {$stamp} by {$who}] "
            . "Same order sent through {$toLabel} (op {$partner->op_code}). "
            . "First route was {$fromLabel} (op {$fromCode}). "
            . 'Customer was not charged again. If the first route later succeeds, check it by hand.';
        if ($note) {
            $noteText .= ' Admin note: ' . $note;
        }

        $originalProviderId = $order->provider_id;
        $originalServiceId = $order->service_id;
        $originalTxn = $order->provider_txn_id;

        DB::transaction(function () use ($order, $partner, $toProvider, $prevResp, $count, $clientRef, $noteText, $originalProviderId, $originalServiceId, $originalTxn, $fromCode, $fromService, $admin) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->first();
            if (! $locked) {
                throw new \RuntimeException('Order not found.');
            }
            if ($locked->status !== 'pending' && $locked->status !== 'processing') {
                throw new \RuntimeException('Only a pending order can be sent through the other Dialog route.');
            }

            $locked->provider_id = $toProvider->id;
            $locked->service_id = $partner->id;
            $locked->provider_txn_id = null;
            $locked->status = 'processing';
            $locked->provider_status = 'transfer_processing';
            $locked->processed_at = now();
            $locked->completed_at = null;
            $locked->message = trim(($locked->message ? $locked->message . "\n\n" : '') . $noteText);
            $catalogId = $prevResp['_catalog_service_id'] ?? $originalServiceId;
            $catalogName = $prevResp['_catalog_service_name'] ?? ($fromService->name ?? null);
            $locked->provider_response = array_merge($prevResp, [
                '_client_ref' => $clientRef,
                '_transfer_count' => $count,
                '_route_service_id' => $partner->id,
                '_route_op_code' => (string) $partner->op_code,
                '_route_started_at' => now()->toDateTimeString(),
                '_catalog_service_id' => $catalogId,
                '_catalog_service_name' => $catalogName,
                '_awaiting_funds' => false,
                '_transfer' => [
                    'at' => now()->toDateTimeString(),
                    'from_provider_id' => $originalProviderId,
                    'from_service_id' => $originalServiceId,
                    'from_op' => $fromCode,
                    'from_txn' => $originalTxn,
                    'to_provider_id' => $toProvider->id,
                    'to_service_id' => $partner->id,
                    'to_op' => (string) $partner->op_code,
                    'client_ref' => $clientRef,
                    'auto' => $admin === null,
                ],
                '_auto_fallback_at' => $admin === null ? now()->toDateTimeString() : ($prevResp['_auto_fallback_at'] ?? null),
            ]);
            $locked->save();
        });

        $order->refresh()->load(['provider', 'service', 'user']);
        $order->setRelation('provider', $toProvider);
        $order->setRelation('service', $partner);

        $resp = null;
        $timedOut = false;
        try {
            $resp = ProviderFactory::make($toProvider)->recharge($order);
        } catch (ConnectionException $e) {
            $timedOut = true;
            $resp = ['status' => 'pending', 'message' => 'Sent through ' . $partner->name . ' — waiting for confirmation…'];
            Log::warning('Dialog pair transfer TIMEOUT', [
                'order' => $order->reference,
                'to' => $partner->op_code,
                'error' => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Dialog pair transfer exception', [
                'order' => $order->reference,
                'to' => $partner->op_code,
                'error' => $e->getMessage(),
            ]);
            $resp = ['status' => 'failed', 'message' => 'Could not send through ' . $partner->name . ': ' . $e->getMessage()];
        }

        $merged = is_array($order->provider_response) ? $order->provider_response : [];
        $order->provider_response = array_merge($merged, [
            '_transfer_response' => $resp,
            '_timed_out' => $timedOut,
        ]);
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
        } elseif (ProviderErrors::isFundsIssue($resp['message'] ?? null, is_array($resp) ? $resp : [])) {
            $this->holdForProviderFunds($order, $resp['message'] ?? 'Provider has no money');
        } else {
            // Keep pending so admin can send it back the other way.
            // Do not refund — the first request may still complete.
            $order->status = 'pending';
            $order->provider_status = 'transfer_rejected';
            $order->save();
        }

        Log::info("Service switch: {$order->reference} {$fromCode} → {$partner->op_code} by ".($admin?->id ?? 'system'), [
            'status' => $order->fresh()->status,
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
        $notes = [];
        $orders = Order::whereIn('status', ['pending', 'processing'])
            ->where(function ($q) {
                $q->where('created_at', '>=', now()->subHours(48))
                  ->orWhere(function ($q2) {
                      $q2->where('provider_status', 'awaiting_provider_funds')
                         ->where('created_at', '>=', now()->subDays(7));
                  });
            })
            ->with(['provider', 'service', 'user'])
            ->get();

        $notes[] = 'Checked '.$orders->count().' pending/processing order(s).';

        foreach ($orders as $order) {
            try {
                $label = $order->reference.' ('.$order->status.', op '.$order->sendOpCode().')';
                // Dialog API already said no — do not wait for another status poll.
                // Do not use the 5-minute switch here or we skip a late success check.
                if ($this->maybeDialogPrepaidFallback($order, false)) {
                    $notes[] = $label.': sent through Dialog Prepaid (API already failed).';
                    $count++;
                    continue;
                }

                if ($order->isAwaitingProviderFunds()) {
                    if ($this->retryIfProviderFunded($order)) {
                        $notes[] = $label.': provider has no money — resent on the same route.';
                        $count++;
                    } else {
                        $notes[] = $label.': provider has no money — waiting, not switching to Dialog Prepaid.';
                    }
                    continue;
                }

                $client = ProviderFactory::make($order->provider);
                $resp = $client->checkStatus($order);
                $status = strtolower((string) ($resp['status'] ?? 'pending'));

                $prev = is_array($order->provider_response) ? $order->provider_response : [];
                $order->provider_response = array_merge($prev, $resp, ['_last_checked' => now()->toDateTimeString()]);
                if (! empty($resp['transaction_id']) && empty($order->provider_txn_id)) {
                    $order->provider_txn_id = $resp['transaction_id'];
                }
                if (! empty($resp['message'])) {
                    $order->message = $resp['message'];
                }

                $turnedSuccess = false;
                if ($status === 'success' && $order->status !== 'success') {
                    $this->markSuccess($order);
                    $turnedSuccess = true;
                    $count++;
                    $notes[] = $label.': provider said success.';
                    Log::info("Order {$order->reference} reconciled to SUCCESS by cron");
                } elseif (($status === 'failed' || $status === 'refund' || $status === 'cancelled')
                    && ! $order->isFailedLike()) {
                    if (ProviderErrors::isFundsIssue($resp['message'] ?? null, $resp)) {
                        $this->holdForProviderFunds($order, $resp['message'] ?? 'Provider has no money');
                        $notes[] = $label.': provider has no money — waiting.';
                        $count++;
                    } elseif ($this->canFallbackDialogPrepaid($order)) {
                        $this->fallbackDialogPrepaid($order, 'Dialog API later failed — trying Dialog Prepaid');
                        $notes[] = $label.': provider failed — sent through Dialog Prepaid.';
                        $count++;
                    } else {
                        $this->markFailed($order, $resp['message'] ?? null);
                        $notes[] = $label.': provider failed — refunded.';
                        $count++;
                        Log::info("Order {$order->reference} reconciled to {$order->fresh()->status} by cron");
                    }
                } else {
                    $order->save();
                }

                if ($turnedSuccess && ! $order->invoice_path) {
                    try {
                        app(InvoiceService::class)->generate($order->fresh());
                    } catch (\Throwable $e) {
                        Log::warning("Invoice generation failed for {$order->reference}: " . $e->getMessage());
                    }
                }

                $fresh = $order->fresh();
                if ($fresh && $this->maybeDialogPrepaidFallback($fresh)) {
                    $notes[] = $label.': still waiting after 5 minutes — sent through Dialog Prepaid.';
                    $count++;
                } elseif ($fresh && $fresh->status === $order->status) {
                    $notes[] = $label.': still '.$fresh->status.' on op '.$fresh->sendOpCode().'.';
                }
            } catch (\Throwable $e) {
                Log::warning("Order sync error for {$order->reference}: " . $e->getMessage());
                $notes[] = $order->reference.': clock error — '.$e->getMessage();
                try {
                    $fresh = $order->fresh();
                    if ($fresh && $this->maybeDialogPrepaidFallback($fresh)) {
                        $notes[] = $order->reference.': sent through Dialog Prepaid after clock error.';
                        $count++;
                    }
                } catch (\Throwable $ignored) {
                    // already logged the main error
                }
            }
        }

        $this->lastSyncReport = $notes;
        try {
            Setting::set('cron', 'last_sync_at', now()->timezone('Asia/Colombo')->toDateTimeString());
            Setting::set('cron', 'last_sync_note', implode("
", $notes));
        } catch (\Throwable $e) {
            //
        }

        return $count;
    }

    public function applyStatusCheck(Order $order, array $resp): string
    {
        $status = strtolower((string) ($resp['status'] ?? 'pending'));
        $prev = is_array($order->provider_response) ? $order->provider_response : [];
        $order->provider_response = array_merge($prev, $resp, ['_last_checked' => now()->toDateTimeString()]);
        if (! empty($resp['transaction_id']) && empty($order->provider_txn_id)) {
            $order->provider_txn_id = $resp['transaction_id'];
        }
        if (! empty($resp['message'])) {
            $order->message = $resp['message'];
        }

        if ($status === 'success' && $order->status !== 'success') {
            $this->markSuccess($order);
            return 'success';
        }

        if (in_array($status, ['failed', 'refund', 'cancelled'], true) && ! $order->isFailedLike()) {
            if (ProviderErrors::isFundsIssue($resp['message'] ?? null, $resp)
                || $order->provider_status === 'awaiting_provider_funds') {
                $this->holdForProviderFunds($order, $resp['message'] ?? $order->message ?? 'Provider has no money');
                return 'processing';
            }
            if ($this->canFallbackDialogPrepaid($order)) {
                $updated = $this->fallbackDialogPrepaid($order, 'Dialog API later failed — trying Dialog Prepaid');
                return $updated->status;
            }
            $this->markFailed($order, $resp['message'] ?? null);
            return $order->fresh()?->status ?? 'refunded';
        }

        $order->save();

        $fresh = $order->fresh();
        if ($fresh && $this->maybeDialogPrepaidFallback($fresh)) {
            return $fresh->fresh()?->status ?? $status;
        }

        return $status;
    }

    protected function applyProviderResult(Order $order, array $resp, bool $timedOut = false, bool $refundOnHardFail = true): void
    {
        $prev = is_array($order->provider_response) ? $order->provider_response : [];
        $order->provider_response = array_merge($prev, $resp, ['_timed_out' => $timedOut]);
        if (! empty($resp['transaction_id'])) {
            $order->provider_txn_id = $resp['transaction_id'];
        }
        if (! empty($resp['message'])) {
            $order->message = $resp['message'];
        }

        $status = strtolower((string) ($resp['status'] ?? 'failed'));

        if ($status === 'success') {
            try {
                $this->markSuccess($order);
            } catch (\Throwable $e) {
                Log::error('markSuccess failed after successful provider response', [
                    'order' => $order->reference, 'err' => $e->getMessage(),
                ]);
                $order->status = 'success';
                $order->provider_status = 'success';
                $order->completed_at = now();
                $order->save();
            }
            return;
        }

        if ($status === 'pending' || $timedOut) {
            $order->status = 'pending';
            $order->provider_status = $timedOut ? 'awaiting_confirmation' : 'pending';
            $order->save();
            return;
        }

        if (ProviderErrors::isFundsIssue($resp['message'] ?? null, $resp)) {
            $this->holdForProviderFunds($order, $resp['message'] ?? 'Provider has no money');
            return;
        }

        if ($this->canFallbackDialogPrepaid($order)) {
            $this->fallbackDialogPrepaid($order, 'Dialog API failed — trying Dialog Prepaid');
            return;
        }

        if (! $refundOnHardFail) {
            $order->status = 'pending';
            $order->provider_status = 'transfer_rejected';
            $order->save();
            return;
        }

        $refunded = $this->tryRefundWallet($order, 'Recharge failed — refunded to wallet');
        $order->status = $refunded ? Order::STATUS_REFUNDED : Order::STATUS_FAILED;
        $order->provider_status = $status;
        $order->completed_at = now();
        $order->save();
    }

    protected function holdForProviderFunds(Order $order, string $rawMessage): void
    {
        $prev = is_array($order->provider_response) ? $order->provider_response : [];
        $prev['_awaiting_funds'] = true;
        $prev['_funds_error'] = $rawMessage;
        $order->provider_response = $prev;
        $order->message = $rawMessage;
        $order->status = Order::STATUS_PROCESSING;
        $order->provider_status = 'awaiting_provider_funds';
        $order->completed_at = null;
        $order->save();
        Log::warning("Order {$order->reference} waiting — provider has no money", [
            'error' => $rawMessage,
        ]);
    }

    protected function retryIfProviderFunded(Order $order): bool
    {
        $order->loadMissing(['provider', 'service']);
        if (! $order->provider) {
            return false;
        }

        $need = (float) $order->amount;
        $info = $order->provider->fetchBalanceInfo(true);
        $bal = $info['balance'];
        $enough = false;
        if ($bal === null) {
            $last = $order->responseArray()['_funds_retry_at'] ?? null;
            if ($last) {
                try {
                    if (\Illuminate\Support\Carbon::parse($last)->gt(now()->subMinutes(2))) {
                        return false;
                    }
                } catch (\Throwable $e) {
                    // retry
                }
            }
            $enough = true;
        } elseif ($order->provider->currency() === 'INR') {
            $enough = $bal > 0;
        } else {
            $enough = $bal + 0.009 >= $need;
        }

        if (! $enough) {
            return false;
        }

        $this->resendOrder($order, 'provider_funds');
        return true;
    }

    protected function resendOrder(Order $order, string $reason): void
    {
        $prev = is_array($order->provider_response) ? $order->provider_response : [];
        $n = (int) ($prev['_retry_count'] ?? 0) + 1;
        $prev['_client_ref'] = $order->reference . '-R' . $n;
        $prev['_retry_count'] = $n;
        $prev['_funds_retry_at'] = now()->toDateTimeString();
        $prev['_awaiting_funds'] = false;
        $prev['_resend_reason'] = $reason;
        $order->provider_response = $prev;
        $order->provider_txn_id = null;
        $order->status = 'processing';
        $order->provider_status = 'processing';
        $order->processed_at = now();
        $order->completed_at = null;
        $order->save();

        $timedOut = false;
        try {
            $resp = ProviderFactory::make($order->provider)->recharge($order);
        } catch (ConnectionException $e) {
            $timedOut = true;
            $resp = ['status' => 'pending', 'message' => 'Request sent — waiting for provider confirmation…'];
            Log::warning('Provider resend TIMEOUT', [
                'order' => $order->reference,
                'reason' => $reason,
                'error' => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Provider resend exception', [
                'order' => $order->reference,
                'reason' => $reason,
                'error' => $e->getMessage(),
            ]);
            $resp = ['status' => 'failed', 'message' => 'Provider error: ' . $e->getMessage()];
        }

        $this->applyProviderResult($order, is_array($resp) ? $resp : [], $timedOut, true);
    }

    /**
     * Send a Dialog API order through Dialog Prepaid when:
     *  - the API already returned a hard fail, or
     *  - it is still waiting after 5 minutes.
     * Funds-wait orders stay on Dialog API.
     */
    protected function maybeDialogPrepaidFallback(Order $order, bool $allowFiveMinuteWait = true): bool
    {
        if ($this->canFallbackDialogPrepaid($order) && $this->hasRecordedHardFail($order)) {
            $this->fallbackDialogPrepaid($order, 'Dialog API failed — trying Dialog Prepaid');
            return true;
        }

        if ($allowFiveMinuteWait && $this->shouldAutoFallbackDialog($order)) {
            $this->transferToPairedService($order, null, 'Still waiting after 5 minutes');
            return true;
        }

        return false;
    }

    protected function hasRecordedHardFail(Order $order): bool
    {
        return $order->hasRecordedHardFail();
    }

    protected function canFallbackDialogPrepaid(Order $order): bool
    {
        if (! in_array($order->status, ['pending', 'processing'], true)) {
            return false;
        }
        if ($order->isAwaitingProviderFunds()) {
            return false;
        }
        if ($order->sendOpCode() !== PreferredRoute::DIALOG_API) {
            return false;
        }
        $resp = $order->responseArray();
        if (! empty($resp['_auto_fallback_at'])) {
            return false;
        }

        $partner = ServicePairs::partnerFromOrder($order);

        return $partner && (string) $partner->op_code === PreferredRoute::DIALOG_PREPAID;
    }

    protected function fallbackDialogPrepaid(Order $order, string $note): Order
    {
        $order->status = 'processing';
        $order->completed_at = null;
        $order->save();

        Log::info("Order {$order->reference} Dialog API failed — sending same order through Dialog Prepaid");

        $updated = $this->transferToPairedService($order, null, $note);

        if ($updated->provider_status === 'transfer_rejected' && ! $updated->isAwaitingProviderFunds()) {
            $this->markFailed($updated, $updated->message);
            return $updated->fresh(['provider', 'service', 'user']);
        }

        return $updated;
    }

    protected function shouldAutoFallbackDialog(Order $order): bool
    {
        if (! in_array($order->status, ['pending', 'processing'], true)) {
            return false;
        }
        if ($order->isAwaitingProviderFunds()) {
            return false;
        }
        if ($order->sendOpCode() !== PreferredRoute::DIALOG_API) {
            return false;
        }
        $resp = $order->responseArray();
        if (! empty($resp['_auto_fallback_at'])) {
            return false;
        }
        if ($order->routeStartedAt()->gt(now()->subMinutes(PreferredRoute::AUTO_FALLBACK_MINUTES))) {
            return false;
        }

        return ServicePairs::partnerFromOrder($order) !== null;
    }
}

