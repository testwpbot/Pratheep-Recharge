<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletDeposit;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WalletService
{
    /**
     * Approve a pending deposit: credit user wallet + record transaction.
     * Idempotent (won't double-credit if called twice).
     */
    public function approve(WalletDeposit $deposit, int $adminId, ?string $note = null): WalletDeposit
    {
        if ($deposit->status !== 'pending') {
            return $deposit;
        }

        $deposit = DB::transaction(function () use ($deposit, $adminId, $note) {
            $wallet = Wallet::firstOrCreate(['user_id' => $deposit->user_id]);
            $wallet = Wallet::whereKey($wallet->id)->lockForUpdate()->first() ?? $wallet;

            $before = (float) $wallet->balance;
            $wallet->balance = $before + (float) $deposit->amount;
            // Keep legacy column zeroed (single-wallet world)
            $wallet->cashback_balance = 0;
            $wallet->save();

            WalletTransaction::create([
                'wallet_id'     => $wallet->id,
                'type'          => 'deposit',
                'amount'        => (float) $deposit->amount,
                'balance_before'=> $before,
                'balance_after' => (float) $wallet->balance,
                'description'   => 'Bank deposit approved (' . $deposit->reference() . ')',
                'transactable_type' => WalletDeposit::class,
                'transactable_id'   => $deposit->id,
            ]);

            $deposit->status       = 'approved';
            $deposit->approved_by  = $adminId;
            $deposit->approved_at  = now();
            $deposit->admin_note   = $note;
            $deposit->save();

            return $deposit;
        });

        try {
            app(WalletBalanceNotifier::class)->syncUser((int) $deposit->user_id);
        } catch (\Throwable $e) {
            Log::warning('Wallet low-balance check after deposit failed: ' . $e->getMessage());
        }

        return $deposit;
    }

    /**
     * Reject a pending deposit (no money movement).
     */
    public function reject(WalletDeposit $deposit, int $adminId, ?string $note = null): WalletDeposit
    {
        if ($deposit->status !== 'pending') {
            return $deposit;
        }

        $deposit->status       = 'rejected';
        $deposit->approved_by  = $adminId;
        $deposit->rejected_at  = now();
        $deposit->admin_note   = $note;
        $deposit->save();

        return $deposit;
    }

    /**
     * Admin wallet change. $mode is add | remove | set.
     *
     * @return array{wallet: Wallet, before: float, after: float, delta: float, mode: string}
     */
    public function adjust(Wallet $wallet, string $mode, float $amount, User $admin, string $note): array
    {
        $mode = strtolower(trim($mode));
        if (! in_array($mode, ['add', 'remove', 'set'], true)) {
            throw new RuntimeException('Pick add, take out, or set amount.');
        }

        $amount = round(max(0, $amount), 2);
        $note = trim($note);
        if ($note === '') {
            throw new RuntimeException('Write a short reason for this wallet change.');
        }

        $result = DB::transaction(function () use ($wallet, $mode, $amount, $admin, $note) {
            $w = Wallet::whereKey($wallet->id)->lockForUpdate()->first() ?? $wallet;
            $before = round((float) $w->balance, 2);

            if ($mode === 'add') {
                if ($amount < 0.01) {
                    throw new RuntimeException('Enter an amount to add.');
                }
                $after = round($before + $amount, 2);
                $delta = $amount;
                $label = 'Manual fund add';
            } elseif ($mode === 'remove') {
                if ($amount < 0.01) {
                    throw new RuntimeException('Enter an amount to take out.');
                }
                if ($amount - $before > 0.001) {
                    throw new RuntimeException('Cannot take out more than the wallet has (LKR ' . number_format($before, 2) . ').');
                }
                $after = round($before - $amount, 2);
                $delta = $amount;
                $label = 'Manual funds remove';
            } else {
                $after = $amount;
                $delta = round(abs($after - $before), 2);
                if ($delta < 0.01) {
                    throw new RuntimeException('Wallet is already LKR ' . number_format($before, 2) . '.');
                }
                $label = $after >= $before ? 'Manual fund add' : 'Manual funds remove';
            }

            $w->balance = $after;
            $w->cashback_balance = 0;
            $w->save();

            WalletTransaction::create([
                'wallet_id'         => $w->id,
                'type'              => WalletTransaction::TYPE_ADJUST,
                'amount'            => $delta,
                'balance_before'    => $before,
                'balance_after'     => $after,
                'description'       => $label . ' (' . $admin->name . '): ' . $note,
                'transactable_type' => User::class,
                'transactable_id'   => $admin->id,
            ]);

            return [
                'wallet' => $w,
                'before' => $before,
                'after'  => $after,
                'delta'  => $delta,
                'mode'   => $mode,
            ];
        });

        try {
            app(WalletBalanceNotifier::class)->syncUser((int) $wallet->user_id);
        } catch (\Throwable $e) {
            Log::warning('Wallet low-balance check after admin adjust failed: ' . $e->getMessage());
        }

        if (($result['after'] ?? 0) > ($result['before'] ?? 0)) {
            try {
                app(FundHealthService::class)->check(fresh: false, persist: true, alert: true);
            } catch (\Throwable $e) {
                Log::warning('Funds check after admin wallet adjust failed: ' . $e->getMessage());
            }
        }

        return $result;
    }
}
