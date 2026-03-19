<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreignId('import_id')->nullable()->constrained('import_log')->nullOnDelete()->after('created_by');
            $table->softDeletes();
        });

        Schema::table('members', function (Blueprint $table) {
            $table->foreignId('import_id')->nullable()->constrained('import_log')->nullOnDelete()->after('mbl_balance');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropForeign(['import_id']);
            $table->dropColumn(['import_id', 'deleted_at']);
        });

        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['import_id']);
            $table->dropColumn(['import_id', 'deleted_at']);
        });
    }
};
