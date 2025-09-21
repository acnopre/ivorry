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
        Schema::create('dentists', function (Blueprint $table) {
            $table->id();
            $table->string('last_name');
            $table->string('first_name');
            $table->string('middle_initial')->nullable();
            $table->string('suffix')->nullable();

            $table->string('owner_last_name')->nullable();
            $table->string('owner_first_name')->nullable();
            $table->string('owner_middle_initial')->nullable();
            $table->string('owner_suffix')->nullable();
            $table->string('corporate_name')->nullable();

            $table->string('clinic_name')->nullable();
            $table->string('branch_code')->nullable();
            $table->string('tin_number')->nullable();
            $table->string('clinic_address')->nullable();
            $table->string('barangay')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('region')->nullable();
            
            $table->string('landline')->nullable();
            $table->string('mobile_number')->nullable();
            $table->string('alternative_number')->nullable();

            $table->string('bank_account_name')->nullable();
            $table->string('bank_branch')->nullable();
            $table->string('bank_account_number')->nullable();

            $table->enum('tax_registration', ['VAT', 'NON-VAT', '0%'])->default('NON-VAT');
            $table->string('withholding_tax')->nullable();
            $table->string('specializations')->nullable();
            $table->enum('accreditation_status', ['ACTIVE', 'INACTIVE', 'SILENT', 'SPECIFIC ACCOUNT'])->default('INACTIVE');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dentists');
    }
};
