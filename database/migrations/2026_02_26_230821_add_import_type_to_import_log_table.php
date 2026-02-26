<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('import_log', function (Blueprint $table) {
            $table->enum('import_type', ['account', 'member'])->default('account')->after('filename');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_log', function (Blueprint $table) {
            $table->dropColumn('import_type');
        });
    }
};
