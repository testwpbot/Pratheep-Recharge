<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletDeposit;
use RuntimeException;

/**
 * Customer wallet rules:
 *  - LKR 100 (or the admin setting) must stay in the wallet after a recharge.
 *    Example: a LKR 50 recharge needs LKR 150 in the wallet.
 *  - New customers must deposit at least that reserve first.
 *  - Admins are not limited (so they can still test).
 */
class WalletLimits
{
    public const DEFAULT_MIN = 100.0;

    /** Smallest bill a customer can pay — used to decide if they can open recharge. */
    public const SMALLEST_ORDER = 10.0;

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

    /** How much must be in the wallet to place this recharge. */
    public static function requiredBalance(float $amount): float
    {
        return round($amount + self::minBalance(), 2);
    }

    /** Money that can be spent while leaving the reserve untouched. */
    public static function spendable(Wallet $wallet): float
    {
        return max(0, round((float) $wallet->balance - self::minBalance(), 2));
    }

    public static function canStartRecharge(User $user, Wallet $wallet): bool
    {
        if (! self::appliesTo($user)) {
            return true;
        }

        return self::spendable($wallet) + 0.0001 >= self::SMALLEST_ORDER;
    }

    /**
     * Throw if this customer cannot pay $amount and still keep the reserve.
     */
    public static function assertCanDebit(User $user, Wallet $wallet, float $amount): void
    {
        if ($amount <= 0) {
            throw new RuntimeException('Enter a valid amount.');
        }

        $balance = round((float) $wallet->balance, 2);
        $min = self::minBalance();

        if (! self::appliesTo($user)) {
            if ($balance < $amount) {
                $short = $amount - $balance;
                throw new RuntimeException(
                    'Not enough wallet money. You need ' . self::money($short) . ' more. Please add money to your wallet.'
                );
            }

            return;
        }

        if ($balance < $min) {
            if ($balance <= 0.009) {
                throw new RuntimeException(
                    'Add at least ' . self::money($min) . ' to your wallet before you can recharge.'
                );
            }

            throw new RuntimeException(
                'Your wallet is below ' . self::money($min) . '. Add money to keep recharging.'
            );
        }

        $need = self::requiredBalance($amount);
        if ($balance + 0.0001 < $need) {
            throw new RuntimeException(
                'You must keep ' . self::money($min) . ' in your wallet. '
                . 'This ' . self::money($amount) . ' recharge needs ' . self::money($need)
                . ' in your wallet. You have ' . self::money($balance) . '.'
            );
        }
    }

    public static function isLow(Wallet $wallet, ?User $user = null): bool
    {
        if ($user && ! self::appliesTo($user)) {
            return false;
        }

        return self::spendable($wallet) + 0.0001 < self::SMALLEST_ORDER;
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

        if (self::canStartRecharge($user, $wallet)) {
            return null;
        }

        if ($balance + 0.0001 < $min) {
            return [
                'type'    => 'low',
                'title'   => 'Your wallet is low',
                'message' => 'You have ' . self::money($balance) . '. Add money so your wallet is at least '
                    . self::money($min) . ' and you can keep recharging.',
                'balance' => $balance,
                'min'     => $min,
            ];
        }

        return [
            'type'    => 'reserve',
            'title'   => 'Keep ' . self::money($min) . ' in your wallet',
            'message' => 'You must keep ' . self::money($min) . ' in your wallet. Add more money to place a recharge.',
            'balance' => $balance,
            'min'     => $min,
        ];
    }

    public static function blockMessage(User $user, Wallet $wallet): string
    {
        $notice = self::notice($user, $wallet);
        if ($notice) {
            return $notice['message'];
        }

        $min = self::minBalance();

        return 'You must keep ' . self::money($min) . ' in your wallet. Add more money to place a recharge.';
    }
}
