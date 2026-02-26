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
        Schema::table('account_amendments', function (Blueprint $table) {
            $table->enum('coverage_period_type', ['ACCOUNT', 'MEMBER'])->nullable()->after('expiration_date');
            $table->enum('mbl_type', ['Procedural', 'Fixed'])->nullable()->after('coverage_period_type');
            $table->decimal('mbl_amount', 10, 2)->nullable()->after('mbl_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_amendments', function (Blueprint $table) {
            $table->dropColumn(['coverage_period_type', 'mbl_type', 'mbl_amount']);
        });
    }
};
