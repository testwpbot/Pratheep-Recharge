<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('name');          // e.g. "Dialog 100 Reload", "Mobitel 1.5GB Anytime"
            $table->string('plan_code')->nullable(); // provider plan/package code if any
            $table->decimal('amount', 10, 2);
            $table->string('validity')->nullable();  // "7 Days", "30 Days" etc.
            $table->text('description')->nullable(); // data/minutes/SMS breakdown
            $table->string('type')->default('reload'); // reload | data | voice | combo | bill
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['service_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
