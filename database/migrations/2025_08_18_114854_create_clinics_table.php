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
            $table->string('clinic_owner_last')->nullable();
            $table->string('clinic_owner_first')->nullable();
            $table->string('clinic_owner_middle')->nullable();
            $table->string('specializations')->nullable();

            // PRC / PTR
            $table->string('prc_license_no')->nullable();
            $table->date('prc_expiration_date')->nullable();
            $table->string('ptr_no')->nullable();
            $table->date('ptr_date_issued')->nullable();

            // Accreditation & Tax
            $table->string('other_hmo_accreditation')->nullable();
            $table->string('tax_identification_no')->nullable();
            $table->enum('tax_type', ['vat', 'non_vat'])->nullable();
            $table->enum('business_type', ['sole_proprietor', 'partnership', 'corporation'])->nullable();
            $table->string('sec_registration_no')->nullable();

            // Address / Contact
            $table->text('clinic_address')->nullable();
            $table->string('clinic_landline')->nullable();
            $table->string('clinic_mobile')->nullable();
            $table->string('viber_no')->nullable();
            $table->string('clinic_email')->nullable();

            // Alternative Contact
            $table->text('alt_address')->nullable();

            // Dentist Info
            $table->string('dentist_personal_no')->nullable();
            $table->string('dentist_email')->nullable();
            $table->enum('clinic_schedule', ['first_come', 'by_appointment'])->nullable();
            $table->string('schedule_days')->nullable();
            $table->integer('number_of_chairs')->nullable();
            $table->boolean('dental_xray_periapical')->default(false);
            $table->boolean('dental_xray_panoramic')->default(false);

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
            $table->enum('account_type', ['savings', 'current'])->nullable();

            // Status
            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->timestamps();
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
