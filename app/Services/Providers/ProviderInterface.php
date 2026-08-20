<?php

namespace App\Services\Providers;

use App\Models\Order;

interface ProviderInterface
{
    /** Fetch available services from the provider (used in admin "import" flow) */
    public function fetchServices(): array;

    /** Check API wallet balance. Returns null when the API can't be reached. */
    public function balance(): ?float;

    /** Initiate a recharge request */
    public function recharge(Order $order): array;

    /** Check status of a previously initiated transaction */
    public function checkStatus(Order $order): array;
}
