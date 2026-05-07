<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_amendments', function (Blueprint $table) {
            $table->string('coverage_type')->nullable()->after('old_coverage_type');
        });
    }

    public function down(): void
    {
        Schema::table('account_amendments', function (Blueprint $table) {
            $table->dropColumn('coverage_type');
        });
    }
};
