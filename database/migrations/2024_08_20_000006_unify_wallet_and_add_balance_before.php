<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Unify wallet into a single balance and add balance_before to transactions
     * so customers can see before→after on every wallet movement.
     */
    public function up(): void
    {
        // 1. Add balance_before to wallet_transactions (null OK for legacy rows)
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->decimal('balance_before', 14, 2)->nullable()->after('amount');
        });

        // 2. Back-fill balance_before for existing transactions from prior
        //    balance_after values (walk newest→oldest so math stays correct).
        $txns = DB::table('wallet_transactions')
            ->orderBy('wallet_id')
            ->orderBy('id', 'desc')
            ->get(['id', 'wallet_id', 'amount', 'type', 'balance_after']);

        $lastAfter = []; // wallet_id => balance_after (running "previous" balance)
        foreach ($txns as $t) {
            $after = (float) $t->balance_after;
            // balance_before = balance_after - signed_amount
            $signed = in_array($t->type, ['debit'], true) ? -abs((float) $t->amount) : abs((float) $t->amount);
            $before = $after - $signed;
            DB::table('wallet_transactions')->where('id', $t->id)->update([
                'balance_before' => round($before, 2),
            ]);
        }

        // 3. Merge cashback_balance INTO balance on each wallet (one-time sweep)
        $wallets = DB::table('wallets')->get();
        foreach ($wallets as $w) {
            $cb = (float) ($w->cashback_balance ?? 0);
            if ($cb != 0) {
                $newBal = bcadd((string) $w->balance, (string) $cb, 2);
                DB::table('wallets')->where('id', $w->id)->update([
                    'balance'          => $newBal,
                    'cashback_balance' => 0,
                ]);

                // Record a migration entry transaction so the jump appears in history
                $before = (float) $newBal - $cb;
                DB::table('wallet_transactions')->insert([
                    'wallet_id'         => $w->id,
                    'transactable_type' => null,
                    'transactable_id'   => null,
                    'type'              => 'cashback',
                    'amount'            => $cb,
                    'balance_before'    => round($before, 2),
                    'balance_after'     => (float) $newBal,
                    'description'       => 'Previously earned cashback merged into wallet',
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropColumn('balance_before');
        });
        // We cannot cleanly reverse the cashback merge (cashback transactions
        // are indistinguishable at the DB-level without model logic).
    }
};
