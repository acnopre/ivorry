<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CSRSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::firstOrCreate(['name' => 'CSR']);

        $permissions = [
            'member.view',
            'member.create',
            'member.update',
            'member.approve_procedure',
            'member.deny_procedure',
            'member.confirm_procedure',
            'member.search',
            'member.upload',
            // 'chatbot.use',
        ];

        $role->syncPermissions(Permission::whereIn('name', $permissions)->get());

        $user = User::firstOrCreate(
            ['email' => 'csr@example.com'],
            [
                'name' => 'Customer Service Rep',
                'password' => Hash::make('password'),
            ]
        );

        $user->assignRole($role);

        $this->command->info('✅ CSR seeded (csr@example.com / password)');
    }
}
