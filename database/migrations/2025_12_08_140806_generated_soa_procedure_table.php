<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_soa_procedure', function (Blueprint $table) {
            $table->id();

            $table->foreignId('generated_soa_id')
                ->constrained('generated_soas')
                ->cascadeOnDelete();

            $table->foreignId('procedure_id')
                ->constrained('procedures')
                ->cascadeOnDelete();

            $table->decimal('amount', 12, 2)->nullable(); // optional, if you want amount per procedure

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_soa_procedure');
    }
};
