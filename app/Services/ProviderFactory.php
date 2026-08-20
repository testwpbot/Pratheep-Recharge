<?php

namespace App\Services;

use App\Models\Provider;
use App\Services\Providers\ProviderInterface;
use App\Services\Providers\TopupMart;
use App\Services\Providers\HappyRechargeCenter;
use InvalidArgumentException;

class ProviderFactory
{
    protected static array $map = [
        'topup_mart'          => TopupMart::class,
        'happy_recharge_center' => HappyRechargeCenter::class,
    ];

    public static function make(Provider $provider): ProviderInterface
    {
        // Allow admin to override the api_class field on the Provider record
        $class = $provider->api_class;

        // If they stored a short slug like "topup_mart", resolve it
        if (isset(static::$map[$class])) {
            $class = static::$map[$class];
        }

        if (!class_exists($class)) {
            throw new InvalidArgumentException("Provider class {$class} not found for provider {$provider->name}");
        }

        // Pass credentials directly so providers don't have to guess the config key
        return new $class($provider->base_url, $provider->api_key);
    }
}
