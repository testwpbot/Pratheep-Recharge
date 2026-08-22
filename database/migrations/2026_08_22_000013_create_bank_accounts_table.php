<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('bank_slug', 80)->nullable();
            $table->string('bank_name', 160);
            $table->string('account_name', 160);
            $table->string('account_no', 80);
            $table->string('branch', 160)->nullable();
            $table->text('instructions')->nullable();
            $table->string('logo_path', 500)->nullable();
            $table->string('logo_url', 500)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $old = [
            'bank_name'    => Setting::get('bank', 'bank_name'),
            'account_name' => Setting::get('bank', 'account_name'),
            'account_no'   => Setting::get('bank', 'account_no'),
            'branch'       => Setting::get('bank', 'branch'),
            'instructions' => Setting::get('bank', 'instructions'),
        ];
        if (! empty($old['bank_name']) && ! empty($old['account_no'])) {
            \Illuminate\Support\Facades\DB::table('bank_accounts')->insert([
                'bank_slug'     => 'custom',
                'bank_name'     => $old['bank_name'],
                'account_name'  => $old['account_name'] ?: $old['bank_name'],
                'account_no'    => $old['account_no'],
                'branch'        => $old['branch'] ?: null,
                'instructions'  => $old['instructions'] ?: null,
                'sort_order'    => 1,
                'is_active'     => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
