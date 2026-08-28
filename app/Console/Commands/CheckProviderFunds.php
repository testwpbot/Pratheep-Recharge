<?php

namespace App\Console\Commands;

use App\Services\FundHealthService;
use Illuminate\Console\Command;

class CheckProviderFunds extends Command
{
    protected $signature = 'funds:check {--fresh : Bypass the 60s provider-balance cache}';

    protected $description = 'Check if the provider wallet has more money than customers, save history, and email admin if it is too low.';

    public function handle(FundHealthService $funds): int
    {
        $health = $funds->check(fresh: (bool) $this->option('fresh'), persist: true, alert: true);

        $this->info('Customers have: LKR ' . number_format($health['user_total'], 2)
            . ' · ' . ($health['overall'] === 'low' ? 'not enough' : ($health['overall'] === 'healthy' ? 'enough' : "can't check")));

        foreach ($health['providers'] as $row) {
            $bal = $row['balance'] === null
                ? ($row['error_label'] ?? "can't check")
                : $row['currency'] . ' ' . number_format((float) $row['balance'], 2);
            $label = $row['status'] === 'low' ? 'not enough' : ($row['status'] === 'healthy' ? 'ok' : "can't check");
            $this->line(sprintf('  %-28s  %s  [%s]', $row['name'], $bal, $label));
        }

        if ($health['pay']) {
            foreach ($health['pay'] as $p) {
                $this->warn("Add {$p['currency']} " . number_format($p['amount'], 2) . " to {$p['provider']}");
            }
        }

        return self::SUCCESS;
    }
}
