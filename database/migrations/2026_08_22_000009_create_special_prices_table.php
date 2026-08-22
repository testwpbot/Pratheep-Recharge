<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_retailer')) {
                $table->boolean('is_retailer')->default(false)->after('is_admin');
            }
        });

        Schema::create('special_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->decimal('profit', 8, 2)->default(0);
            $table->char('profit_type', 4)->default('FLAT'); // FLAT | PCT
            $table->timestamps();
            $table->unique(['user_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('special_prices');
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_retailer')) {
                $table->dropColumn('is_retailer');
            }
        });
    }
};
