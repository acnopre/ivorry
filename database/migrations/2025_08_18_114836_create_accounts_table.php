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
            $table->enum('plan_type', ['INDIVIDUAL', 'SHARED'])->default('INDIVIDUAL');
            $table->enum('coverage_period_type', ['ACCOUNT', 'MEMBER'])->default('ACCOUNT');

            // endorsement workflow
            $table->enum('endorsement_type', [
                'NEW',
                'RENEWAL',
                'RENEWED',
                'AMENDMENT',
                'AMENDED'
            ])->default('NEW');
            $table->enum('endorsement_status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');

            // account activation
            $table->enum('account_status', ['inactive', 'active', 'expired'])
                ->default('inactive');
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
