<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('print_logs', function (Blueprint $table) {
            $table->string('cups_job_id')->nullable()->after('printer');
            $table->string('status')->default('sent')->after('cups_job_id'); // sent, completed, failed
        });
    }

    public function down(): void
    {
        Schema::table('print_logs', function (Blueprint $table) {
            $table->dropColumn(['cups_job_id', 'status']);
        });
    }
};
