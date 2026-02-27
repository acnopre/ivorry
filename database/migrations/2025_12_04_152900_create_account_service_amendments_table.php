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
        Schema::create('account_service_amendments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_amendment_id')
                ->constrained('account_amendments')
                ->cascadeOnDelete();

            $table->foreignId('service_id')->constrained('services');
            $table->integer('quantity')->nullable();
            $table->integer('default_quantity')->nullable();
            $table->boolean('is_unlimited')->default(false);
            $table->longText('remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_service_amendments');
    }
};
