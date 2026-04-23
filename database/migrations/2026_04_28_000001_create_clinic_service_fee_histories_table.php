<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_service_fee_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->decimal('old_fee', 10, 2)->nullable();
            $table->decimal('new_fee', 10, 2);
            $table->date('effective_date');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['clinic_id', 'service_id', 'effective_date'], 'csfh_clinic_service_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_service_fee_histories');
    }
};
