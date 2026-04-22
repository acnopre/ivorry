<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $canalTypeId = DB::table('unit_types')->where('name', 'Canal')->value('id');

        if (!$canalTypeId) return;

        $options = ['CENTRAL', 'BUCCAL', 'PALATAL', 'MB1', 'MB2', 'DB', 'ML'];

        foreach ($options as $name) {
            $exists = DB::table('units')
                ->where('unit_type_id', $canalTypeId)
                ->where('name', $name)
                ->exists();

            if (!$exists) {
                DB::table('units')->insert([
                    'unit_type_id' => $canalTypeId,
                    'name'         => $name,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        $canalTypeId = DB::table('unit_types')->where('name', 'Canal')->value('id');

        if (!$canalTypeId) return;

        DB::table('units')
            ->where('unit_type_id', $canalTypeId)
            ->whereIn('name', ['CENTRAL', 'BUCCAL', 'PALATAL', 'MB1', 'MB2', 'DB', 'ML'])
            ->delete();
    }
};
