<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ImportBatchPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'import.batch.delete',
            'import.batch.restore',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $this->command->info("✅ Permission created: {$permission}");
        }

        $roles = [
            \App\Models\Role::SUPER_ADMIN,
            \App\Models\Role::UPPER_MANAGEMENT,
            \App\Models\Role::MIDDLE_MANAGEMENT,
        ];

        foreach ($roles as $roleName) {
            $role = Role::where('name', $roleName)->first();

            if (!$role) {
                $this->command->warn("⚠️  Role not found: {$roleName}");
                continue;
            }

            $role->givePermissionTo($permissions);
            $this->command->info("✅ Assigned import batch permissions to {$roleName}");
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
