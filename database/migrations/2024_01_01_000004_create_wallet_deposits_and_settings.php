<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ---------- wallet deposits (bank-transfer top-up requests) ----------
        Schema::create('wallet_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('bank_name', 120)->nullable();
            $table->string('depositor_name', 120)->nullable();
            $table->string('reference_number', 120)->nullable(); // customer-side bank ref
            $table->string('slip_path', 500)->nullable();        // stored slip file path
            $table->string('status', 16)->default('pending');    // pending|approved|rejected
            $table->text('admin_note')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        // ---------- settings (key-value for SMTP / bank details / etc.) ----------
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 40)->index();   // smtp, bank, general
            $table->string('key', 80)->index();
            $table->text('value')->nullable();
            $table->unique(['group', 'key']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('wallet_deposits');
    }
};
