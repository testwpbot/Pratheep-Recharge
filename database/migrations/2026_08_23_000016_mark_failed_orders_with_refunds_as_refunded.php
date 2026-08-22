<?php

use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders') || ! Schema::hasTable('wallet_transactions')) {
            return;
        }

        DB::table('orders')
            ->where('status', 'failed')
            ->whereExists(function ($q) {
                $q->selectRaw('1')
                    ->from('wallet_transactions')
                    ->whereColumn('wallet_transactions.transactable_id', 'orders.id')
                    ->where('wallet_transactions.transactable_type', Order::class)
                    ->where('wallet_transactions.type', 'refund');
            })
            ->update(['status' => 'refunded']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        DB::table('orders')
            ->where('status', 'refunded')
            ->update(['status' => 'failed']);
    }
};
