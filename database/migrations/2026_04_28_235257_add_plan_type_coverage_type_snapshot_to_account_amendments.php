<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_amendments', function (Blueprint $table) {
            $table->string('old_plan_type')->nullable()->after('old_mbl_amount');
            $table->string('old_coverage_type')->nullable()->after('old_plan_type');
        });
    }

    public function down(): void
    {
        Schema::table('account_amendments', function (Blueprint $table) {
            $table->dropColumn(['old_plan_type', 'old_coverage_type']);
        });
    }
};
