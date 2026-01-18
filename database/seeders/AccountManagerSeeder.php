<?php

namespace Database\Seeders;

use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AccountManagerSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::firstOrCreate(['name' => 'Account Manager']);

        $permissions = [
            'account.view',
            'account.create',
            'account.update',
            'account.delete',
            'account.import',

            'member.view',
            'member.create',
            'member.update',
            'member.delete',
            'member.import',
            'import-logs.view',
            'import-logs.details.view'

        ];

        $role->syncPermissions(Permission::whereIn('name', $permissions)->get());

        $user = User::firstOrCreate(
            ['email' => 'account@example.com'],
            [
                'name' => 'Account Manager',
                'password' => Hash::make('password'),
            ]
        );

        $user->assignRole($role);

        $this->command->info('✅ Account Manager seeded (account@example.com / password)');
    }
}
