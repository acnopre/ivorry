<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->comment('User who initiated the print');
            $table->unsignedBigInteger('document_id')->comment('Related SOA/ADC ID');
            $table->string('copy_type')->comment('ORIGINAL, DUPLICATE');
            $table->string('printer')->nullable()->comment('Printer name or ID used');
            $table->timestamps();

            // Optional: foreign key if you have users table
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_logs');
    }
};
