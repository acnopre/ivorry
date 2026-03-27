<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Set inactive where inactive_date is set and has passed
        DB::statement("
            UPDATE members
            SET status = 'inactive'
            WHERE inactive_date IS NOT NULL
              AND inactive_date <= DATE('now')
              AND status != 'inactive'
              AND deleted_at IS NULL
        ");

        // Set active where inactive_date was cleared
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
