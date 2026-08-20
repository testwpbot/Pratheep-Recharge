<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ---------- providers (Topup Mart, Happy Recharge Center, etc.) ----------
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('country', 2)->default('LK');
            $table->string('api_class');       // App\Services\Providers\TopupMart
            $table->string('base_url');
            $table->string('api_key', 512)->nullable();
            $table->string('api_secret', 512)->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        // ---------- categories (Mobile, Broadband, Utility, etc.) ----------
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();   // icon key or path
            $table->string('logo')->nullable();   // logo path (e.g. assets/logos/x.png)
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ---------- services (one per op_code from the provider) ----------
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('providers')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('op_code', 20);
            $table->string('name');
            $table->string('short_name', 80)->nullable();
            $table->string('logo')->nullable();     // operator logo (dialog.png etc.)
            $table->string('type', 20)->default('prepaid'); // prepaid/postpaid/broadband/utility/tv/insurance/dth
            $table->decimal('profit', 8, 2)->default(0);    // admin-configured cashback per service (LKR)
            $table->char('profit_type', 4)->default('FLAT'); // FLAT = LKR, PCT = %
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['provider_id', 'op_code']);
        });

        // ---------- wallets ----------
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('balance', 14, 2)->default(0);
            $table->decimal('cashback_balance', 14, 2)->default(0); // earned cashback
            $table->string('pin', 100)->nullable();   // optional wallet PIN for future use
            $table->timestamps();
        });

        // ---------- wallet transactions (credits/debits/cashbacks) ----------
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->morphs('transactable');   // order, deposit, etc.
            $table->string('type', 20);       // deposit|debit|cashback|refund
            $table->decimal('amount', 14, 2);
            $table->decimal('balance_after', 14, 2);
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        // ---------- orders ----------
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 32)->unique();   // HPR-YYYYMMDD-XXXX
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services');
            $table->foreignId('provider_id')->constrained('providers');
            $table->string('account_number', 30);        // the number being recharged
            $table->string('notify_number', 30)->nullable();
            $table->decimal('amount', 12, 2);            // what customer pays (LKR)
            $table->decimal('profit', 8, 2)->default(0); // cashback to be granted
            $table->string('provider_txn_id', 64)->nullable();   // transaction_id from provider
            $table->string('status', 16)->default('pending');    // pending|processing|success|failed|refunded
            $table->string('provider_status', 16)->nullable();   // raw provider status
            $table->text('message')->nullable();
            $table->json('provider_response')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
            $table->index('provider_txn_id');
        });

        // ---------- cashbacks (tracks cashback granted per completed order) ----------
        Schema::create('cashbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('status', 16)->default('pending'); // pending|credited
            $table->timestamp('credited_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashbacks');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('services');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('providers');
    }
};
