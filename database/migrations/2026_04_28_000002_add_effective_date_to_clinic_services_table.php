<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinic_services', function (Blueprint $table) {
            $table->date('effective_date')->nullable()->after('new_fee');
        });
    }

    public function down(): void
    {
        Schema::table('clinic_services', function (Blueprint $table) {
            $table->dropColumn('effective_date');
        });
    }
};
