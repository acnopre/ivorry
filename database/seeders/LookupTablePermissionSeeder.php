<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class LookupTablePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create lookup table permissions
        $permissions = [
            'lookup_tables.view',
            'lookup_tables.create',
            'lookup_tables.edit',
            'lookup_tables.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $this->command->info('✅ Lookup table permissions created.');

        // Assign to Super Admin, Upper Management, and Middle Management
        $roles = ['Super Admin', 'Upper Management', 'Middle Management'];

        foreach ($roles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($permissions);
                $this->command->info("✅ Lookup table permissions assigned to {$roleName}.");
            }
        }
    }
}
