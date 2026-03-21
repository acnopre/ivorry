<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') return;
        DB::statement("ALTER TABLE procedures MODIFY COLUMN status ENUM('pending','signed','valid','invalid','returned','processed','cancelled') NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') return;
        DB::statement("ALTER TABLE procedures MODIFY COLUMN status ENUM('pending','signed','valid','invalid','returned','processed') NOT NULL");
    }
};
