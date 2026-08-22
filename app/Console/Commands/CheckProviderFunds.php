<?php

namespace App\Console\Commands;

use App\Services\FundHealthService;
use Illuminate\Console\Command;

class CheckProviderFunds extends Command
{
    protected $signature = 'funds:check {--fresh : Bypass the 60s provider-balance cache}';

    protected $description = 'Compare API provider wallets to customer balances, store history, and email admin if float is low.';

    public function handle(FundHealthService $funds): int
    {
        $health = $funds->check(fresh: (bool) $this->option('fresh'), persist: true, alert: true);

        $this->info('Customer wallets: LKR ' . number_format($health['user_total'], 2)
            . ' · overall ' . strtoupper($health['overall']));

        foreach ($health['providers'] as $row) {
            $bal = $row['balance'] === null
                ? ($row['error_label'] ?? 'unavailable')
                : $row['currency'] . ' ' . number_format((float) $row['balance'], 2);
            $this->line(sprintf('  %-28s  %s  [%s]', $row['name'], $bal, $row['status']));
        }

        if ($health['pay']) {
            foreach ($health['pay'] as $p) {
                $this->warn("Pay {$p['currency']} " . number_format($p['amount'], 2) . " to {$p['provider']}");
            }
        }

        return self::SUCCESS;
    }
}
