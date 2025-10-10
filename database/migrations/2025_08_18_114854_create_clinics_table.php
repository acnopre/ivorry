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
        Schema::create('clinics', function (Blueprint $table) {
            $table->id();

            // Clinic Info
            $table->string('clinic_name');
            $table->string('registered_name')->nullable();

            // PTR
            $table->string('ptr_no')->nullable();
            $table->date('ptr_date_issued')->nullable();

            // Accreditation & Tax
            $table->string('other_hmo_accreditation')->nullable();
            $table->string('tax_identification_no')->nullable();
            $table->enum('tax_type', ['VAT', 'NON-VAT', '0%'])->default('NON-VAT');
            $table->enum('business_type', ['SOLE PROPRIETOR', 'PARTNERSHIP', 'CORPORATION'])->nullable();
            $table->string('sec_registration_no')->nullable();

            // Address / Contact
            $table->text('clinic_address')->nullable();
            $table->string('clinic_landline')->nullable();
            $table->string('clinic_mobile')->nullable();
            $table->string('viber_no')->nullable();
            $table->string('clinic_email')->nullable();

            // Alternative Contact
            $table->text('alt_address')->nullable();

            // Clinic Staff
            $table->string('clinic_staff_name')->nullable();
            $table->string('clinic_staff_mobile')->nullable();
            $table->string('clinic_staff_viber')->nullable();
            $table->string('clinic_staff_email')->nullable();

            // Bank Information
            $table->string('bank_account_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_branch')->nullable();
            $table->enum('account_type', ['SAVINGS', 'CURRENT'])->nullable();
            // Status
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
        Schema::dropIfExists('clinics');
    }
};
