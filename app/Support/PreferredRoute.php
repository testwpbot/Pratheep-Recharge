<?php

namespace App\Support;

use App\Models\Service;

/**
 * Customer-facing Dialog Prepaid (181) is sent first through Dialog API (921).
 * After 5 minutes still pending, cron (or admin) sends the same order through 181.
 * Do not mention this split on customer pages.
 */
class PreferredRoute
{
    public const DIALOG_PREPAID = '181';
    public const DIALOG_API = '921';
    public const AUTO_FALLBACK_MINUTES = 5;

    public static function faceCode(string $opCode): string
    {
        return $opCode === self::DIALOG_API ? self::DIALOG_PREPAID : $opCode;
    }

    public static function startCode(string $opCode): string
    {
        $face = self::faceCode($opCode);

        return $face === self::DIALOG_PREPAID ? self::DIALOG_API : $opCode;
    }

    public static function faceService(Service $picked): Service
    {
        if ((string) $picked->op_code !== self::DIALOG_API) {
            return $picked;
        }

        return self::findSibling($picked, self::DIALOG_PREPAID) ?? $picked;
    }

    public static function startService(Service $face): Service
    {
        if ((string) $face->op_code !== self::DIALOG_PREPAID) {
            return $face;
        }

        return self::findSibling($face, self::DIALOG_API) ?? $face;
    }

    public static function findSibling(Service $from, string $opCode): ?Service
    {
        $base = Service::query()->where('op_code', $opCode);
        if ($from->provider_id) {
            $same = (clone $base)
                ->where('provider_id', $from->provider_id)
                ->orderByDesc('is_active')
                ->first();
            if ($same) {
                return $same;
            }
        }

        return $base->orderByDesc('is_active')->first();
    }

    public static function adminLabel(?Service $service, ?string $opCode = null): string
    {
        $code = (string) ($opCode ?: ($service?->op_code ?? ''));
        if ($code === self::DIALOG_API) {
            return 'Dialog API';
        }
        if ($code === self::DIALOG_PREPAID) {
            return 'Dialog Prepaid';
        }

        return $service?->name ?: ('op '.$code);
    }

    /** Route fields stored on the order before the first provider call. */
    public static function orderMeta(Service $catalog, Service $send): array
    {
        return [
            '_catalog_service_id' => $catalog->id,
            '_catalog_service_name' => $catalog->name,
            '_route_service_id' => $send->id,
            '_route_op_code' => (string) $send->op_code,
            '_route_started_at' => now()->toDateTimeString(),
        ];
    }
}
