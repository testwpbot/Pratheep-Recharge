<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletDeposit;
use RuntimeException;

/**
 * Customer wallet rules:
 *  - Wallet must be at least LKR 100 to place a recharge.
 *  - New customers must deposit at least LKR 100 first.
 *  - After a recharge the leftover can drop below 100; then we block
 *    the next recharge and email them that the wallet is low.
 *  - Admins are not limited (so they can still test).
 */
class WalletLimits
{
    public const DEFAULT_MIN = 100.0;

    public static function minBalance(): float
    {
        $raw = Setting::get('general', 'min_wallet_balance', self::DEFAULT_MIN);
        if (! is_numeric($raw)) {
            return self::DEFAULT_MIN;
        }

        return max(0, round((float) $raw, 2));
    }

    public static function minDeposit(): float
    {
        return self::minBalance();
    }

    public static function appliesTo(?User $user): bool
    {
        return $user !== null && ! $user->isAdmin();
    }

    public static function money(float $amount): string
    {
        return 'LKR ' . number_format($amount, 2);
    }

    public static function canStartRecharge(User $user, Wallet $wallet): bool
    {
        if (! self::appliesTo($user)) {
            return true;
        }

        return (float) $wallet->balance + 0.0001 >= self::minBalance();
    }

    /**
     * Throw if this customer cannot pay $amount from the wallet.
     */
    public static function assertCanDebit(User $user, Wallet $wallet, float $amount): void
    {
        if ($amount <= 0) {
            throw new RuntimeException('Enter a valid amount.');
        }

        $balance = round((float) $wallet->balance, 2);
        $min = self::minBalance();

        if (self::appliesTo($user) && $balance < $min) {
            if ($balance <= 0.009) {
                throw new RuntimeException(
                    'Add at least ' . self::money($min) . ' to your wallet before you can recharge.'
                );
            }

            throw new RuntimeException(
                'Your wallet is below ' . self::money($min) . '. Add money to keep recharging.'
            );
        }

        if ($balance < $amount) {
            $short = $amount - $balance;
            throw new RuntimeException(
                'Not enough wallet money. You need ' . self::money($short) . ' more. Please add money to your wallet.'
            );
        }
    }

    public static function isLow(Wallet $wallet, ?User $user = null): bool
    {
        if ($user && ! self::appliesTo($user)) {
            return false;
        }

        return (float) $wallet->balance + 0.0001 < self::minBalance();
    }

    /**
     * Banner copy for the customer dashboard. Null when the wallet is fine.
     *
     * @return array{type:string,title:string,message:string,balance:float,min:float}|null
     */
    public static function notice(User $user, Wallet $wallet): ?array
    {
        if (! self::appliesTo($user)) {
            return null;
        }

        $min = self::minBalance();
        $balance = (float) $wallet->balance;
        if ($balance + 0.0001 >= $min) {
            return null;
        }

        $pending = WalletDeposit::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        $hasApproved = WalletDeposit::query()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->exists();

        if (! $hasApproved && $balance <= 0.009) {
            if ($pending) {
                return [
                    'type'    => 'pending_first',
                    'title'   => 'We are checking your deposit',
                    'message' => 'Your first deposit is waiting for approval. You need at least '
                        . self::money($min) . ' in your wallet to recharge.',
                    'balance' => $balance,
                    'min'     => $min,
                ];
            }

            return [
                'type'    => 'first',
                'title'   => 'Add money to start',
                'message' => 'New accounts need a first deposit of ' . self::money($min)
                    . ' or more before you can recharge.',
                'balance' => $balance,
                'min'     => $min,
            ];
        }

        return [
            'type'    => 'low',
            'title'   => 'Your wallet is low',
            'message' => 'You have ' . self::money($balance) . '. Add money so your wallet is at least '
                . self::money($min) . ' and you can keep recharging.',
            'balance' => $balance,
            'min'     => $min,
        ];
    }
}
