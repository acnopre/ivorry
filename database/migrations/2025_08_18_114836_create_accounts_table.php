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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');                   // Company / HMO name
            $table->string('policy_code')->unique();          // Policy reference code
            $table->string('hip')->nullable();                // HIP identifier
            $table->string('card_used')->nullable();          // Card used
            $table->date('effective_date')->nullable();       // Policy effective date
            $table->date('expiration_date')->nullable();      // Valid until

            // endorsement workflow
            $table->enum('endorsement_type', ['NEW', 'RENEWAL', 'AMENDMENT'])->default('NEW');
            $table->enum('endorsement_status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');

            // account activation
            $table->boolean('account_status')->default(0); // 0 = inactive, 1 = active
            $table->longText('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
