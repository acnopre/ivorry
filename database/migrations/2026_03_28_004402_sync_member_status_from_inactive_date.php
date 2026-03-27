<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            UPDATE members
            SET status = 'inactive'
            WHERE inactive_date IS NOT NULL
              AND inactive_date <= CURDATE()
              AND status != 'inactive'
              AND deleted_at IS NULL
        ");

        DB::statement("
            UPDATE members
            SET status = 'active'
            WHERE inactive_date IS NULL
              AND status = 'inactive'
              AND deleted_at IS NULL
        ");
    }

    public function down(): void {}
};
