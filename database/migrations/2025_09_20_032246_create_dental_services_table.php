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

        Schema::create('dentist_basic_dental_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dentist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('basic_dental_service_id')->constrained()->cascadeOnDelete();
            $table->decimal('fee', 10, 2)->nullable();
        });

        Schema::create('dentist_plan_enhancement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dentist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_enhancement_id')->constrained()->cascadeOnDelete();
            $table->decimal('fee', 10, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dentist_basic_dental_service');
        Schema::dropIfExists('dentist_plan_enhancement');
    }
};
