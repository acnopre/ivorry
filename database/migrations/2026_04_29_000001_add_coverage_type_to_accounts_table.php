<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->enum('coverage_type', ['DEFAULT', 'ALL_PRINCIPAL', 'ALL_DEPENDENT'])
                ->default('DEFAULT')
                ->after('plan_type');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('coverage_type');
        });
    }
};
