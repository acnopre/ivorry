<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('endorsement_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // NEW, RENEWAL, AMENDMENT
            $table->timestamps();
        });

        // If you want to link it to accounts directly
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreignId('endorsement_type_id')
                ->nullable()
                ->constrained('endorsement_types')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('endorsement_type_id');
        });

        Schema::dropIfExists('endorsement_types');
    }
};
