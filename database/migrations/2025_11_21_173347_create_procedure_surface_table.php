<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('procedure_surfaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procedure_unit_id')->constrained('procedure_units')->cascadeOnDelete();
            $table->foreignId('surface_id')->constrained('surfaces')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procedure_surfaces');
    }
};
