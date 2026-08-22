<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_balance_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('providers')->cascadeOnDelete();
            $table->decimal('balance', 14, 2)->nullable();
            $table->char('currency', 3);
            $table->decimal('user_wallet_total', 14, 2)->default(0);
            $table->decimal('coverage_lkr', 14, 2)->nullable();
            $table->decimal('shortfall', 14, 2)->default(0);
            $table->decimal('shortfall_lkr', 14, 2)->default(0);
            $table->string('status', 16); // healthy | low | unknown
            $table->string('error', 255)->nullable();
            $table->boolean('alerted')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['provider_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_balance_snapshots');
    }
};
