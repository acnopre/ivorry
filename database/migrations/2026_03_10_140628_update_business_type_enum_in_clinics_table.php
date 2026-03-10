<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE clinics MODIFY COLUMN business_type VARCHAR(255) NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE clinics MODIFY COLUMN business_type ENUM('SOLE PROPRIETORSHIP', 'PARTNERSHIP', 'GENERAL PROFESSIONAL PARTNERSHIP', 'CORPORATION', 'ONE PERSON CORPORATION') NULL");
    }
};
