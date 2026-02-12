<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // main procedure record
        Schema::create('procedures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->decimal('applied_fee', 10, 2);
            $table->date('availment_date')->nullable();
            $table->string('quantity')->nullable();
            $table->string('approval_code')->nullable();
            $table->enum('status', ['pending', 'sign', 'valid', 'invalid', 'returned', 'processed']);
            $table->longText('remarks')->nullable();
            $table->string('qr_path')->nullable();
            $table->longText('adc_number')->nullable();
            $table->longText('adc_number_from')->nullable();
            $table->boolean('is_fee_adjusted')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procedures');
    }
};
