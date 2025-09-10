<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::firstOrCreate(['name' => 'Super Admin']);

        // Super Admin gets ALL permissions
        $role->syncPermissions(Permission::all());

        $user = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'), // 🔐 change in production
            ]
        );

        if (! $user->hasRole($role->name)) {
            $user->assignRole($role);
        }

        $this->command->info('✅ Super Admin seeded (admin@example.com / password)');
    }
}
