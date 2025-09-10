<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MiddleManagementSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::firstOrCreate(['name' => 'Middle Management']);

        $permissions = [
            'dashboard.view',
            'claim.view',
            'soa.view',
            'account.view',
            'clinic.view',
            'dentist.view',
            'claim.override',
            'account.override',
        ];

        $role->syncPermissions(Permission::whereIn('name', $permissions)->get());

        $user = User::firstOrCreate(
            ['email' => 'middle@example.com'],
            [
                'name' => 'Middle Manager',
                'password' => Hash::make('password'),
            ]
        );

        $user->assignRole($role);

        $this->command->info('✅ Middle Manager seeded (middle@example.com / password)');
    }
}
