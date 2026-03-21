<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return; // columns are already VARCHAR in SQLite
        }

        DB::statement("ALTER TABLE clinics MODIFY COLUMN business_type VARCHAR(255) NULL");
        DB::statement("ALTER TABLE clinics MODIFY COLUMN update_info_1903 VARCHAR(255) NULL");
        DB::statement("ALTER TABLE clinics MODIFY COLUMN vat_type VARCHAR(255) NULL");
        DB::statement("ALTER TABLE clinics MODIFY COLUMN withholding_tax VARCHAR(255) NULL");
        DB::statement("ALTER TABLE clinics MODIFY COLUMN account_type VARCHAR(255) NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE clinics MODIFY COLUMN business_type ENUM('SOLE PROPRIETORSHIP', 'PARTNERSHIP', 'GENERAL PROFESSIONAL PARTNERSHIP', 'CORPORATION', 'ONE PERSON CORPORATION') NULL");
    }
};
