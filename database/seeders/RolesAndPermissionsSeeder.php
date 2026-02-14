<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // ----------------- Permissions -----------------
        $permissions = [
            // Member
            'member.view',
            'member.create',
            'member.update',
            'member.delete',
            'member.import',
            'member.approve_procedure',
            'member.deny_procedure',
            'member.confirm_procedure',
            'member.search',
            'member.upload',
            'import-logs.view',
            'import-logs.details.view',
            'member-myprofile',
            'member.myaccount',

            // Claims
            'claims.view',
            'claims.search',
            'claims.generate',
            'claims.view_details',
            'claims.valid',
            'claims.reject',
            'claims.return',
            'claims.print',
            'fee.approval',

            // Accounts
            'account.view',
            'account.create',
            'account.update',
            'account.delete',
            'account.import',
            'account.approve',
            'account.reject',
            'account.renew',
            'account.amend',

            // Clinics
            'clinic.view',
            'clinic.create',
            'clinic.update',
            'clinic.delete',
            'clinic.profile',

            // Dentists
            'dentist.search',
            'dentist.view',
            'dentist.list',
            'dentist.add-procedure',
            'dentist.my-procedure',
            'dentist.update',
            'clinic.import',

            // System
            'system.logs',
            'system.audit',
            'system.export',
            'system.manage_users',
            'system.manage_permissions',
            'view_any_system::version',
            'view_system::version',
            'create_system::version',
            'update_system::version',
            'delete_system::version',

            // Reports & Dashboard
            'reports.view',
            'dashboard.view',

            'generated_adc.view',
            'generated_adc.approve',
            'generated_adc.request',
            'generated_adc.print_original'

        ];

        // Seed permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $this->command->info('✅ All permissions seeded.');

        // ----------------- Roles & Users -----------------
        $roles = [
            'Account Manager' => [
                'permissions' => [
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
                    'import-logs.details.view',
                ],
                'user' => [
                    'name' => 'Account Manager',
                    'email' => 'account@example.com',
                    'password' => 'password',
                ],
            ],

            'Accreditation' => [
                'permissions' => [
                    'clinic.view',
                    'clinic.create',
                    'clinic.update',
                    'clinic.delete',
                    'dentist.view',
                    'dentist.create',
                    'dentist.update',
                    'dentist.delete',
                    'clinic.import',
                    'import-logs.view',
                    'import-logs.details.view',
                ],
                'user' => [
                    'name' => 'Accreditation Officer',
                    'email' => 'accreditation@example.com',
                    'password' => 'password',
                ],
            ],

            'Claims Processor' => [
                'permissions' => [
                    'claims.view',
                    'claims.search',
                    'claims.generate',
                    'claims.view_details',
                    'claims.valid',
                    'claims.reject',
                    'claims.return',
                    'claims.print',
                    'generated_adc.view',
                    'generated_adc.request',
                    'generated_adc.print_original'
                ],
                'user' => [
                    'name' => 'Claims Processor',
                    'email' => 'claims@example.com',
                    'password' => 'password',
                ],
            ],

            'CSR' => [
                'permissions' => [
                    'member.view',
                    'member.create',
                    'member.update',
                    'member.approve_procedure',
                    'member.deny_procedure',
                    'member.confirm_procedure',
                    'member.search',
                    'member.upload',
                    'dentist.search',
                    'dentist.add-procedure',
                    'account.view',
                    'member.search'
                ],
                'user' => [
                    'name' => 'Customer Service Rep',
                    'email' => 'csr@example.com',
                    'password' => 'password',
                ],
            ],


            'Dentist' => [
                'permissions' => [
                    'dentist.search',
                    'dentist.view',
                    'dentist.list',
                    'dentist.add-procedure',
                    'dentist.my-procedure',
                    'clinic.profile',

                ],
                'user' => [
                    'name' => 'John Doe Dentist',
                    'email' => 'dentist@example.com',
                    'password' => 'password',
                ],
            ],

            'Member' => [
                'permissions' => [
                    'member.search',
                    'member-myprofile',
                    'member.myaccount',

                ],
                'user' => [
                    'name' => 'Juliana Saw',
                    'email' => 'member@example.com',
                    'password' => 'password',
                ],
            ],

            'Middle Management' => [
                'permissions' => [], // will inherit all permissions
                'user' => [
                    'name' => 'Middle Manager',
                    'email' => 'middle@example.com',
                    'password' => 'password',
                ],
            ],

            'Upper Management' => [
                'permissions' => [], // will inherit all permissions
                'user' => [
                    'name' => 'Upper Manager',
                    'email' => 'upper@example.com',
                    'password' => 'password',
                ],
            ],
        ];

        // First, create all roles except Upper Management
        foreach ($roles as $roleName => $roleData) {
            $role = Role::firstOrCreate(['name' => $roleName]);

            if ($roleName !== 'Upper Management' && !empty($roleData['permissions'])) {
                $role->syncPermissions(Permission::whereIn('name', $roleData['permissions'])->get());
            }

            $user = User::firstOrCreate(
                ['email' => $roleData['user']['email']],
                [
                    'name' => $roleData['user']['name'],
                    'password' => Hash::make($roleData['user']['password']),
                ]
            );

            $user->assignRole($role);

            $this->command->info("✅ {$roleName} seeded ({$roleData['user']['email']} / {$roleData['user']['password']})");
        }

        // Now, Upper Management inherits ALL permissions
        $upperRole = Role::firstOrCreate(['name' => 'Upper Management']);
        $allPermissions = Permission::all();
        $upperRole->syncPermissions($allPermissions);

        $middleRole = Role::firstOrCreate(['name' => 'Middle Management']);
        $allPermissionsExceptMember = Permission::whereNotIn('name', ['member-myprofile', 'member.myaccount'])->get();
        $middleRole->syncPermissions($allPermissionsExceptMember);
    }
}
