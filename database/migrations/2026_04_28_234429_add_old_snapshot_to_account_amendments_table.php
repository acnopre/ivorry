<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_amendments', function (Blueprint $table) {
            $table->string('old_company_name')->nullable()->after('remarks');
            $table->string('old_policy_code')->nullable()->after('old_company_name');
            $table->foreignId('old_hip_id')->nullable()->constrained('hips')->after('old_policy_code');
            $table->string('old_card_used')->nullable()->after('old_hip_id');
            $table->date('old_effective_date')->nullable()->after('old_card_used');
            $table->date('old_expiration_date')->nullable()->after('old_effective_date');
            $table->enum('old_coverage_period_type', ['ACCOUNT', 'MEMBER'])->nullable()->after('old_expiration_date');
            $table->enum('old_mbl_type', ['Procedural', 'Fixed'])->nullable()->after('old_coverage_period_type');
            $table->decimal('old_mbl_amount', 10, 2)->nullable()->after('old_mbl_type');
        });

        Schema::table('account_service_amendments', function (Blueprint $table) {
            $table->integer('old_quantity')->nullable()->after('remarks');
            $table->boolean('old_is_unlimited')->nullable()->after('old_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('account_amendments', function (Blueprint $table) {
            $table->dropForeign(['old_hip_id']);
            $table->dropColumn(['old_company_name', 'old_policy_code', 'old_hip_id', 'old_card_used', 'old_effective_date', 'old_expiration_date', 'old_coverage_period_type', 'old_mbl_type', 'old_mbl_amount']);
        });

        Schema::table('account_service_amendments', function (Blueprint $table) {
            $table->dropColumn(['old_quantity', 'old_is_unlimited']);
        });
    }
};
