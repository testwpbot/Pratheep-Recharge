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
}
