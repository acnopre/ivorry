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
            'import-logs.view.account',
            'import-logs.view.member',
            'import-logs.view.clinic',
            'import-logs.view.procedure',
            'import-logs.details.view',
            'import.batch.delete',
            'import.batch.restore',
            'member-myprofile',
            'member.myaccount',

            // Documentation
            'documentation.view',

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
            'claims.approve-fee',
            'claims.request-fee',

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
            'account.import.migration-mode',

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

            // Procedures
            'procedure.import',
            'procedure.import.migration-mode',

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
            'generated_adc.print_original',

            // Lookup Tables
            'lookup_tables.view',
            'lookup_tables.create',
            'lookup_tables.edit',
            'lookup_tables.delete',

            // Communications
            'communication.welcome-email',

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
                    'account.import.migration-mode',
                    'member.view',
                    'member.create',
                    'member.update',
                    'member.delete',
                    'member.import',
                    'import-logs.view',
                    'import-logs.view.account',
                    'import-logs.view.member',
                    'import-logs.view.procedure',
                    'import-logs.details.view',
                    'procedure.import',
                    'procedure.import.migration-mode',
                    'documentation.view',
                ],
                'users' => [
                    [
                        'name' => 'Account Manager',
                        'email' => 'account@example.com',
                        'password' => 'password',
                    ],
                    [
                        'name' => 'Ivorry Account Manager',
                        'email' => 'ivorry.account@example.com',
                        'password' => 'password',
                    ],
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
                    'import-logs.view.clinic',
                    'import-logs.details.view',
                    'communication.welcome-email',
                ],
                'users' => [
                    [
                        'name' => 'Accreditation Officer',
                        'email' => 'accreditation@example.com',
                        'password' => 'password',
                    ],
                    [
                        'name' => 'Ivorry Accreditation',
                        'email' => 'ivorry.accreditation@example.com',
                        'password' => 'password',
                    ],
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
                    'generated_adc.print_original',
                    'claims.request-fee',
                ],
                'users' => [
                    [
                        'name' => 'Claims Processor',
                        'email' => 'claims@example.com',
                        'password' => 'password',
                    ],
                    [
                        'name' => 'Ivorry Claims',
                        'email' => 'ivorry.claims@example.com',
                        'password' => 'password',
                    ],
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
                'users' => [
                    [
                        'name' => 'Customer Service Rep',
                        'email' => 'csr@example.com',
                        'password' => 'password',
                    ],
                    [
                        'name' => 'Ivorry CSR',
                        'email' => 'ivorry.csr@example.com',
                        'password' => 'password',
                    ],
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
                'users' => [
                    [
                        'name' => 'John Doe Dentist',
                        'email' => 'dentist@example.com',
                        'password' => 'password',
                    ],
                    // [
                    //     'name' => 'Ivorry Dentist',
                    //     'email' => 'ivorry.dentist@example.com',
                    //     'password' => 'password',
                    // ],
                ],
            ],

            'Member' => [
                'permissions' => [
                    'member.search',
                    'member-myprofile',
                    'member.myaccount',

                ],
                'users' => [
                    [
                        'name' => 'Juliana Saw',
                        'email' => 'member@example.com',
                        'password' => 'password',
                    ],
                    [
                        'name' => 'Ivorry Member',
                        'email' => 'ivorry.member@example.com',
                        'password' => 'password',
                    ],
                ],
            ],

            'Middle Management' => [
                'permissions' => [], // will inherit all permissions
                'users' => [
                    [
                        'name' => 'Middle Manager',
                        'email' => 'middle@example.com',
                        'password' => 'password',
                    ],
                    [
                        'name' => 'Ivorry Middle Manager',
                        'email' => 'ivorry.middle@example.com',
                        'password' => 'password',
                    ],
                ],
            ],

            'Upper Management' => [
                'permissions' => [], // will inherit all permissions
                'users' => [
                    [
                        'name' => 'Upper Manager',
                        'email' => 'upper@example.com',
                        'password' => 'password',
                    ],
                    [
                        'name' => 'Ivorry Upper Manager',
                        'email' => 'ivorry.upper@example.com',
                        'password' => 'password',
                    ],
                ],
            ],
        ];

        // First, create all roles except Upper Management
        foreach ($roles as $roleName => $roleData) {
            $role = Role::firstOrCreate(['name' => $roleName]);

            if ($roleName !== 'Upper Management' && !empty($roleData['permissions'])) {
                $role->syncPermissions(Permission::whereIn('name', $roleData['permissions'])->get());
            }

            foreach ($roleData['users'] as $userData) {
                $user = User::firstOrCreate(
                    ['email' => $userData['email']],
                    [
                        'name' => $userData['name'],
                        'password' => Hash::make($userData['password']),
                    ]
                );

                $user->assignRole($role);

                $this->command->info("✅ {$roleName} seeded ({$userData['email']} / {$userData['password']})");
            }
        }

        // Now, Upper Management inherits ALL permissions
        $upperRole = Role::firstOrCreate(['name' => 'Upper Management']);
        $allPermissionWithException = Permission::whereNotIn('name', ['member-myprofile', 'member.myaccount'])->get();
        $upperRole->syncPermissions($allPermissionWithException);

        $middleRole = Role::firstOrCreate(['name' => 'Middle Management']);
        $allPermissionWithException = Permission::whereNotIn('name', ['member-myprofile', 'member.myaccount'])->get();
        $middleRole->syncPermissions($allPermissionWithException);
    }
}
