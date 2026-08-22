<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->string('title', 160);
            $table->string('eyebrow', 80)->nullable();
            $table->string('heading', 180);
            $table->text('body')->nullable();
            $table->string('image_path', 255)->nullable();
            $table->string('button_label', 80)->nullable();
            $table->string('button_url', 500)->nullable();
            $table->string('button2_label', 80)->nullable();
            $table->string('button2_url', 500)->nullable();
            $table->string('theme', 20)->default('navy');
            $table->string('audience', 20)->default('all');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_dismissible')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['is_active', 'starts_at', 'ends_at']);
        });

        Schema::create('alert_dismissals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_id')->constrained('alerts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();
            $table->unique(['alert_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_dismissals');
        Schema::dropIfExists('alerts');
    }
};
