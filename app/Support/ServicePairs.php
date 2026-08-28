<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Service;

/**
 * Sibling routes that admin can bounce a pending order between.
 * Same wallet debit — only the provider op_code changes.
 */
class ServicePairs
{
    /** @var list<array{0: string, 1: string}> */
    public const PAIRS = [
        ['181', '921'], // Dialog Prepaid <-> Dialog API
        ['102', '922'], // Dialog HBB Prepaid <-> Dialog HBB API
        ['192', '923'], // Dialog TV Prepaid <-> Dialog TV API
    ];

    public static function partnerCode(?string $opCode): ?string
    {
        $opCode = (string) $opCode;
        if ($opCode === '') {
            return null;
        }

        foreach (self::PAIRS as [$a, $b]) {
            if ($opCode === $a) {
                return $b;
            }
            if ($opCode === $b) {
                return $a;
            }
        }

        return null;
    }

    public static function partner(?Service $service): ?Service
    {
        if (! $service) {
            return null;
        }

        $code = self::partnerCode((string) $service->op_code);
        if (! $code) {
            return null;
        }

        $base = Service::query()->where('op_code', $code);
        if ($service->provider_id) {
            $same = (clone $base)
                ->where('provider_id', $service->provider_id)
                ->orderByDesc('is_active')
                ->first();
            if ($same) {
                return $same;
            }
        }

        return $base->orderByDesc('is_active')->first();
    }

    /** Partner of the route we are actually sending right now. */
    public static function partnerFromOrder(Order $order): ?Service
    {
        $code = $order->sendOpCode();
        $partnerCode = self::partnerCode($code);
        if (! $partnerCode) {
            return self::partner($order->service);
        }

        $base = Service::query()->where('op_code', $partnerCode);
        $providerId = $order->provider_id ?: $order->service?->provider_id;
        if ($providerId) {
            $same = (clone $base)
                ->where('provider_id', $providerId)
                ->orderByDesc('is_active')
                ->first();
            if ($same) {
                return $same;
            }
        }

        return $base->orderByDesc('is_active')->first();
    }
}
