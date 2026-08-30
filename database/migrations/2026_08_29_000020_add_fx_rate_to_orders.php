<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DTH (Indian Direct-To-Home) recharges are priced in INR. The customer pays in
 * LKR (order->amount, like every other service); the provider must be credited
 * the INR equivalent = LKR / rate, where the rate (LKR per 1 INR) is set in
 * admin settings (general.dth_inr_rate). Example: LKR 1,825 / 3.65 = 500 INR.
 *
 * `orders.fx_rate` records the rate that was actually used for the order so the
 * provider amount, invoices and reports stay reproducible even if the admin
 * changes the rate later. Non-DTH orders keep fx_rate = 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('fx_rate', 10, 4)->default(1)->after('fee');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('fx_rate');
        });
    }
};
