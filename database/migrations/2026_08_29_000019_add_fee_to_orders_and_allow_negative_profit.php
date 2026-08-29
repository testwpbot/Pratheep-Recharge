<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Support a per-service customer FEE (negative "profit").
 *
 *  - profit  >= 0  -> cashback credited to the customer on success (unchanged)
 *  - profit  <  0  -> a customer fee (surcharge) added on TOP of the bill amount.
 *                     Used for bill payments (e.g. CEB) where the customer is
 *                     charged an extra service amount. The provider still
 *                     receives the exact bill amount; the business keeps the fee.
 *
 * The `orders.fee` column records the surcharge that was actually taken for an
 * order so refunds, invoices and reports stay exact even if the service's
 * profit changes later.
 *
 * decimal columns are widened / made signable so negative profit can be stored
 * (the original columns were plain decimal, which on MySQL is signed already,
 * but we normalise the definition to be safe across environments).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Surcharge (customer fee) charged on top of `amount` for this order.
            $table->decimal('fee', 8, 2)->default(0)->after('profit');
        });

        // On MySQL make sure the money columns are signed so negatives fit.
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE services MODIFY profit DECIMAL(8,2) NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE special_prices MODIFY profit DECIMAL(8,2) NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE orders MODIFY profit DECIMAL(8,2) NOT NULL DEFAULT 0');
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('fee');
        });
    }
};
