<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UpperManagementSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::firstOrCreate(['name' => 'Upper Management']);

        $permissions = [
            'system.logs',
            'system.export',
            'system.manage_users',
            'system.manage_permissions',
            'dashboard.view',
            'claim.view',
            'clinic.view',

            'dentist.view',

            'account.view',
            'account.create',
            'account.update',
            'account.delete',
            'account.import',
            'account.approve',
            'account.reject',
            'account.renew',
            'account.amend',

            'member.view',
            'member.create',
            'member.update',
            'member.delete',
            'member.import',
            'import-logs.view',
            'import-logs.details.view',

            'fee.approval',
        ];

        $role->syncPermissions(Permission::whereIn('name', $permissions)->get());

        $user = User::firstOrCreate(
            ['email' => 'upper@example.com'],
            [
                'name' => 'Upper Manager',
                'password' => Hash::make('password'),
            ]
        );

        $user->assignRole($role);

        $this->command->info('✅ Upper Management seeded (upper@example.com / password)');
    }
}
