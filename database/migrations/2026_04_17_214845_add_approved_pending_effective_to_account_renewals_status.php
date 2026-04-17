<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE account_renewals MODIFY COLUMN status ENUM('PENDING','APPROVED','APPROVED_PENDING_EFFECTIVE','REJECTED') DEFAULT 'PENDING'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE account_renewals MODIFY COLUMN status ENUM('PENDING','APPROVED','REJECTED') DEFAULT 'PENDING'");
    }
};
