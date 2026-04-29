<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('schedule_settings')) {
            return;
        }

        Schema::create('schedule_settings', function (Blueprint $table) {
            $table->id();
            $table->string('command');
            $table->string('label');
            $table->string('description')->nullable();
            $table->string('daily_time')->nullable(); // HH:MM
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->string('last_run_status')->nullable(); // success, failed
            $table->timestamps();
        });

        DB::table('schedule_settings')->insert([
            ['command' => 'members:deactivate',  'label' => 'Deactivate Members',       'description' => 'Deactivates members whose inactive_date or expiration_date has passed.',         'daily_time' => '00:05', 'enabled' => true,  'created_at' => now(), 'updated_at' => now()],
            ['command' => 'accounts:activate',   'label' => 'Activate Accounts',        'description' => 'Activates accounts and their members when the effective_date is reached.',        'daily_time' => '00:05', 'enabled' => true,  'created_at' => now(), 'updated_at' => now()],
            ['command' => 'fees:apply-approved', 'label' => 'Apply Approved Fees',      'description' => 'Applies approved service fees whose effective date has arrived.',                 'daily_time' => '00:05', 'enabled' => true,  'created_at' => now(), 'updated_at' => now()],
            ['command' => 'print:check-jobs',    'label' => 'Check Print Jobs',         'description' => 'Polls CUPS every minute for pending print jobs and updates SOA/procedure status.', 'daily_time' => null,    'enabled' => true,  'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_settings');
    }
};
