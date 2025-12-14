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
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();

            // Clinic Info
            $table->string('clinic_name');
            $table->string('registered_name')->nullable();

            // PTR
            $table->string('ptr_no')->nullable();
            $table->date('ptr_date_issued')->nullable();

            // Accreditation & Tax
            $table->string('other_hmo_accreditation')->nullable();
            $table->string('tax_identification_no')->nullable();
            $table->boolean('is_branch')->default(false);
            $table->string('complete_address')->nullable();

            // UPDATE INFO PER BIR FORM 1903
            $table->enum('update_info_1903', [
                'CHANGE IN BUSINESS NAME',
                'CHANGE IN ADDRESS',
                'CHANGE IN TAX TYPE',
            ])->nullable();

            // BUSINESS TYPE
            $table->enum('business_type', [
                'SOLE PROPRIETORSHIP',
                'PARTNERSHIP',
                'GENERAL PROFESSIONAL PARTNERSHIP',
                'CORPORATION',
                'ONE PERSON CORPORATION',
            ])->nullable();

            // VAT TYPE
            $table->enum('vat_type', [
                'VAT 12%',
                'VAT ZERO',
                'VAT EXEMPT',
                'NON-VAT',
            ])->nullable();

            // WITHHOLDING TAX
            $table->enum('withholding_tax', [
                'ZERO',
                '2%',
                '5%',
                '10%',
                '15%',
            ])->nullable();


            $table->string('sec_registration_no')->nullable();

            // Address / Contact
            $table->string('street')->nullable()->comment('Street address or barangay');
            $table->string('region_id')->nullable()->constrained('regions')->cascadeOnDelete();
            $table->string('province_id')->nullable()->constrained('provinces')->cascadeOnDelete();
            $table->string('municipality_id')->nullable()->constrained('municipalities')->cascadeOnDelete();
            $table->string('barangay_id')->nullable();
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
            $table->longText('remarks')->nullable();
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
