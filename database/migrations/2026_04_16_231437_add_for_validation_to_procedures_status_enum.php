<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE procedures MODIFY COLUMN status ENUM('pending','signed','for_validation','valid','invalid','returned','processed','cancelled') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE procedures MODIFY COLUMN status ENUM('pending','signed','valid','invalid','returned','processed','cancelled') NOT NULL");
    }
};
