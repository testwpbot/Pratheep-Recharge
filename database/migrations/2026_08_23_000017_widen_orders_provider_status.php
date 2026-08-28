<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        // awaiting_provider_funds / transfer_processing / awaiting_confirmation
        // were longer than the old VARCHAR(16) and could fail or get cut on live MySQL.
        DB::statement('ALTER TABLE orders MODIFY provider_status VARCHAR(64) NULL');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE orders MODIFY provider_status VARCHAR(16) NULL');
    }
};
