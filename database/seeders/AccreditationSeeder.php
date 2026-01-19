<?php

namespace Database\Seeders;

use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AccreditationSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::firstOrCreate(['name' => 'Accreditation']);

        $permissions = [
            'clinic.view',
            'clinic.create',
            'clinic.update',
            'clinic.delete',
            'clinic.import',
            'dentist.view',
            'dentist.create',
            'dentist.update',
            'dentist.delete',
        ];

        $role->syncPermissions(Permission::whereIn('name', $permissions)->get());

        $user = User::firstOrCreate(
            ['email' => 'accreditation@example.com'],
            [
                'name' => 'Accreditation Officer',
                'password' => Hash::make('password'),
            ]
        );

        $user->assignRole($role);

        $this->command->info('✅ Accreditation Officer seeded (accreditation@example.com / password)');
    }
}
