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
            $table->date('availment_date')->nullable();
            $table->string('quantity')->nullable();
            $table->string('approval_code')->nullable();
            $table->enum('status', ['pending', 'completed', 'valid', 'invalid', 'returned']);
            $table->longText('remarks')->nullable();
            $table->string('qr_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procedures');
    }
};
