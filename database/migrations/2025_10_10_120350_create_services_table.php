<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // unified services table
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['basic', 'enhancement'])->default('basic');
            $table->enum('unit_type', ['Session', 'Quadrant', 'Tooth', 'Arch', 'Surface', 'Canal']);
            $table->timestamps();
            $table->softDeletes();
        });

        // pivot for account ↔ services
        Schema::create('account_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->integer('default_quantity')->nullable();
            $table->integer('quantity')->nullable();
            $table->longText('remarks')->nullable();
            $table->boolean('is_unlimited')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        // pivot for dentist ↔ services
        Schema::create('clinic_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->decimal('fee', 10, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_service');
        Schema::dropIfExists('dentist_service');
        Schema::dropIfExists('services');
    }
};
