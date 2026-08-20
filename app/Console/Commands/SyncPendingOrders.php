<?php

namespace App\Console\Commands;

use App\Services\OrderService;
use Illuminate\Console\Command;

class SyncPendingOrders extends Command
{
    protected $signature   = 'orders:sync-pending';
    protected $description = 'Poll providers for status updates on pending orders and credit cashback when successful.';

    public function handle(OrderService $svc): int
    {
        $n = $svc->syncPending();
        $this->info("Updated {$n} orders.");
        return self::SUCCESS;
    }
}
