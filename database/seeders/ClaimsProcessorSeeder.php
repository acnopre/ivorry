<?php

namespace Database\Seeders;


use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ClaimsProcessorSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::firstOrCreate(['name' => 'Claims Processor']);

        $permissions = [
            'claim.view',
            'claim.create',
            'claim.update',
            'claim.delete',
            'claim.approve',
            'claim.deny',
            'claim.appeal',
            'claim.override',
            'claim.reprocess',
            'claim.search',
            'claim.filter',
            'soa.view',
            'soa.generate',
        ];

        $role->syncPermissions(Permission::whereIn('name', $permissions)->get());

        $user = User::firstOrCreate(
            ['email' => 'claims@example.com'],
            [
                'name' => 'Claims Processor',
                'password' => Hash::make('password'),
            ]
        );

        $user->assignRole($role);

        $this->command->info('✅ Claims Processor seeded (claims@example.com / password)');
    }
}
