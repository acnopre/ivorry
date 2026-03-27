<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_amendments', function (Blueprint $table) {
            $table->foreignId('hip_id')->nullable()->after('hip')->constrained('hips')->nullOnDelete();
        });

        DB::statement('
            UPDATE account_amendments
            SET hip_id = (SELECT id FROM hips WHERE hips.name = account_amendments.hip LIMIT 1)
            WHERE hip IS NOT NULL
        ');

        Schema::table('account_amendments', function (Blueprint $table) {
            $table->dropColumn('hip');
        });
    }

    public function down(): void
    {
        Schema::table('account_amendments', function (Blueprint $table) {
            $table->string('hip')->nullable()->after('hip_id');
        });

        DB::statement('
            UPDATE account_amendments
            SET hip = (SELECT name FROM hips WHERE hips.id = account_amendments.hip_id LIMIT 1)
            WHERE hip_id IS NOT NULL
        ');

        Schema::table('account_amendments', function (Blueprint $table) {
            $table->dropForeign(['hip_id']);
            $table->dropColumn('hip_id');
        });
    }
};
