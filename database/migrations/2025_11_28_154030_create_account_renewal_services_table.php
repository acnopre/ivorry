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
        Schema::create('account_renewal_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('renewal_id')->constrained('account_renewals')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->integer('default_quantity')->nullable();
            $table->integer('quantity')->nullable();
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
        Schema::dropIfExists('account_renewal_services');
    }
};
