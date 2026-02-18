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
        Schema::create('account_amendments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();

            // store ALL editable account fields
            $table->string('company_name');
            $table->string('policy_code');
            $table->string('hip')->nullable();
            $table->string('card_used')->nullable();
            $table->date('effective_date')->nullable();
            $table->date('expiration_date')->nullable();

            $table->enum('endorsement_type', [
                'NEW',
                'RENEWAL',
                'RENEWED',
                'AMENDMENT',
                'AMENDED'
            ])->default('AMENDMENT');

            $table->enum('endorsement_status', ['PENDING', 'APPROVED', 'REJECTED'])
                ->default('PENDING');

            $table->longText('remarks')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_amendments');
    }
};
