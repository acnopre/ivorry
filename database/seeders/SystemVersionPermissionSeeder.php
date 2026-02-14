<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SystemVersionPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view_any_system::version',
            'view_system::version',
            'create_system::version',
            'update_system::version',
            'delete_system::version',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $upperManagement = Role::where('name', 'upper management')->first();
        
        if ($upperManagement) {
            $upperManagement->givePermissionTo($permissions);
        }
    }
}
