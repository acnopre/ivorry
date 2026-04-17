<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->unsignedBigInteger('renewal_id')->nullable()->after('import_id');
            $table->foreign('renewal_id')->references('id')->on('account_renewals')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['renewal_id']);
            $table->dropColumn('renewal_id');
        });
    }
};
