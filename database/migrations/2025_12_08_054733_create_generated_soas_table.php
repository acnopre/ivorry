<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_soas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->date('from_date');
            $table->date('to_date');
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->string('file_path')->nullable();
            $table->string('duplicate_file_path')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('generated'); // generated / sent / paid / cancelled

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_soas');
    }
};
