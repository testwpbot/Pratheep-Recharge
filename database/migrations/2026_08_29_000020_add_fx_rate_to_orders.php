<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DTH (Indian Direct-To-Home) recharges are priced in INR. The customer enters
 * the INR pack value (e.g. 500), which is what we send to the provider, but the
 * LKR wallet must be charged at the INR->LKR rate set in admin settings
 * (general.dth_inr_rate). Example: 500 INR x 3.65 = LKR 1,825.
 *
 * `orders.fx_rate` records the rate that was actually used for the order so the
 * wallet charge, refunds, invoices and reports stay exact even if the admin
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
