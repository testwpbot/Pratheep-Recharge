<?php

namespace App\Console\Commands;

use App\Services\WalletBalanceNotifier;
use Illuminate\Console\Command;

class NotifyLowWallets extends Command
{
    protected $signature = 'wallets:notify-low';

    protected $description = 'Email customers whose wallet is below the minimum and who have not been told yet.';

    public function handle(WalletBalanceNotifier $notifier): int
    {
        $sent = $notifier->notifyAllDue();
        $this->info($sent === 0
            ? 'No low-wallet emails to send.'
            : "Sent {$sent} low-wallet email" . ($sent === 1 ? '.' : 's.'));

        return self::SUCCESS;
    }
}
