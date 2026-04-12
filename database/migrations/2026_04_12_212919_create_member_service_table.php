<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_service', function (Blueprint $table) {
            $table->id();
            $table->string('card_number')->index();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->integer('default_quantity')->default(0);
            $table->boolean('is_unlimited')->default(false);
            $table->timestamps();

            $table->unique(['card_number', 'account_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_service');
    }
};
