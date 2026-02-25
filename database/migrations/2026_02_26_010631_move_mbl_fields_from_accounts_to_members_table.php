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
        // Add MBL balance to members table
        Schema::table('members', function (Blueprint $table) {
            $table->decimal('mbl_balance', 15, 2)->nullable()->after('status');
        });

        // Remove only MBL balance from accounts table (keep mbl_type and mbl_amount)
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('mbl_balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add MBL balance back to accounts table
        Schema::table('accounts', function (Blueprint $table) {
            $table->decimal('mbl_balance', 15, 2)->nullable()->after('mbl_amount');
        });

        // Remove MBL balance from members table
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('mbl_balance');
        });
    }
};
