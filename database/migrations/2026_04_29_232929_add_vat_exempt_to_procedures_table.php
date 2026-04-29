<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procedures', function (Blueprint $table) {
            $table->boolean('is_vat_exempt')->default(false)->after('is_fee_adjusted');
            $table->enum('discount_type', ['PWD', 'Senior Citizen'])->nullable()->after('is_vat_exempt');
            $table->string('discount_id_number')->nullable()->after('discount_type');
        });
    }

    public function down(): void
    {
        Schema::table('procedures', function (Blueprint $table) {
            $table->dropColumn(['is_vat_exempt', 'discount_type', 'discount_id_number']);
        });
    }
};
