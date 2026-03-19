<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_log', function (Blueprint $table) {
            $table->enum('batch_status', ['active', 'deleted'])->default('active')->after('import_type');
        });
    }

    public function down(): void
    {
        Schema::table('import_log', function (Blueprint $table) {
            $table->dropColumn('batch_status');
        });
    }
};
