<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletDeposit;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
}
