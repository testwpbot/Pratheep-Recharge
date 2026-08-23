<?php

namespace App\Services;

use App\Mail\LowWalletBalance;
use App\Models\User;
use App\Models\Wallet;
use App\Support\WalletLimits;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class WalletBalanceNotifier
{
    /**
     * Email the customer once when their wallet drops below the minimum.
     * Clears the flag when they add money and go back above the minimum.
     */
    public function sync(Wallet $wallet, ?User $user = null): bool
    {
        $wallet->loadMissing('user');
        $user = $user ?: $wallet->user;
        if (! $user || ! WalletLimits::appliesTo($user)) {
            return false;
        }

        $min = WalletLimits::minBalance();
        $balance = (float) $wallet->balance;

        if (WalletLimits::canStartRecharge($user, $wallet)) {
            if ($wallet->low_balance_notified_at) {
                $wallet->low_balance_notified_at = null;
                $wallet->save();
            }

            return false;
        }

        // Brand-new empty wallets get a dashboard banner, not this email.
        if (! $this->hasUsedWallet($wallet)) {
            return false;
        }

        if ($wallet->low_balance_notified_at) {
            return false;
        }

        try {
            Mail::to($user->email)->send(new LowWalletBalance($user, $wallet, $min));
        } catch (\Throwable $e) {
            Log::warning('Low wallet email failed for user ' . $user->id . ': ' . $e->getMessage());

            return false;
        }

        $wallet->low_balance_notified_at = now();
        $wallet->save();

        return true;
    }

    public function syncUser(int $userId): bool
    {
        $user = User::find($userId);
        if (! $user) {
            return false;
        }

        $wallet = Wallet::firstOrCreate(['user_id' => $user->id]);

        return $this->sync($wallet, $user);
    }

    /**
     * Email every customer who is already below the minimum and has not been told.
     */
    public function notifyAllDue(): int
    {
        $min = WalletLimits::minBalance();
        $sent = 0;

        $wallets = Wallet::query()
            ->whereNull('low_balance_notified_at')
            ->where('balance', '<', $min + WalletLimits::SMALLEST_ORDER)
            ->whereHas('user', fn ($q) => $q->where('is_admin', false))
            ->with('user')
            ->get();

        foreach ($wallets as $wallet) {
            if ($this->sync($wallet, $wallet->user)) {
                $sent++;
            }
        }

        return $sent;
    }

    protected function hasUsedWallet(Wallet $wallet): bool
    {
        return $wallet->transactions()
            ->whereIn('type', ['deposit', 'debit', 'cashback', 'refund', 'adjustment'])
            ->exists();
    }
}
