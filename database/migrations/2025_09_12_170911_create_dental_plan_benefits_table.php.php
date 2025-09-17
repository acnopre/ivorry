<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dental_plan_benefits', function (Blueprint $table) {
            $table->id();
            $table->string('category'); // BASIC DENTAL SERVICES, PLAN ENHANCEMENTS
            $table->string('service_name');
            $table->string('unit')->nullable();
            $table->string('limits')->nullable(); // "TO BE ENTERED BASED ON MOA"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dental_plan_benefits');
    }
};
