<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_log', function (Blueprint $table) {
            $table->unsignedInteger('duplicate_rows')->default(0)->after('skipped_rows');
            $table->unsignedInteger('updated_rows')->default(0)->after('duplicate_rows');
        });
    }

    public function down(): void
    {
        Schema::table('import_log', function (Blueprint $table) {
            $table->dropColumn(['duplicate_rows', 'updated_rows']);
        });
    }
};
