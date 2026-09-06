<?php

namespace App\Support;

/**
 * Detect provider-side money problems and keep raw API text off customer pages.
 */
class ProviderErrors
{
    /** @var list<string> */
    public const FUND_NEEDLES = [
        'insufficient balance',
        'insufficient fund',
        'not enough balance',
        'not enough fund',
        'low balance',
        'no balance',
        'balance is low',
        'balance not enough',
        'wallet balance',
        'no provider money',
        'provider wallet',
        'out of fund',
        'out of money',
        'funds not available',
        'reseller balance',
        'api balance',
        'account balance is not enough',
        'less balance',
        'no sufficient',
        'wallet is empty',
        'main wallet',
    ];

    public static function haystack(?string $message, array $resp = []): string
    {
        $parts = [
            (string) $message,
            (string) ($resp['message'] ?? ''),
            (string) ($resp['MESSAGE'] ?? ''),
            (string) ($resp['_funds_error'] ?? ''),
        ];
        if (isset($resp['_raw']) && is_string($resp['_raw'])) {
            $parts[] = $resp['_raw'];
        }

        return strtolower(trim(implode(' ', array_filter($parts))));
    }

    public static function isFundsIssue(?string $message, array $resp = []): bool
    {
        $hay = self::haystack($message, $resp);
        if ($hay === '') {
            return false;
        }

        foreach (self::FUND_NEEDLES as $needle) {
            if (str_contains($hay, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Words a provider uses when a transaction was cancelled / refunded /
     * reversed / declined on their side. When we see any of these on a status
     * poll (or in a failed recharge response) the order did NOT go through and
     * must be refunded to the customer wallet.
     *
     * @var list<string>
     */
    public const CANCEL_NEEDLES = [
        'cancel',
        'cancelled',
        'canceled',
        'refund',
        'refunded',
        'reversed',
        'reversal',
        'declined',
        'rejected',
        'not processed',
        'transaction failed',
        'recharge failed',
        'order failed',
        'transaction not successful',
        'unsuccessful',
    ];

    /**
     * True when the provider text indicates the transaction was cancelled or
     * refunded on their side (a terminal, non-recoverable negative outcome).
     */
    public static function isCancelledOrRefunded(?string $message, array $resp = []): bool
    {
        $hay = self::haystack($message, $resp);
        if ($hay === '') {
            return false;
        }

        // A funds problem is recoverable (top up the provider wallet and retry),
        // so it is NOT a cancellation.
        if (self::isFundsIssue($message, $resp)) {
            return false;
        }

        foreach (self::CANCEL_NEEDLES as $needle) {
            if (str_contains($hay, $needle)) {
                return true;
            }
        }

        return false;
    }

}
