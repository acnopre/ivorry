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
                    'account.import.migration-mode',
                    'member.view',
                    'member.create',
                    'member.update',
                    'member.delete',
                    'member.import',
                    'import-logs.view',
                    'import-logs.details.view',
                ],
                'users' => [
                    [
                        'name' => 'Account Manager',
                        'email' => 'account@example.com',
                        'password' => 'password',
                    ],
                    [
                        'name' => 'Ivory Account Manager',
                        'email' => 'ivory.account@example.com',
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
                    'import-logs.details.view',
                ],
                'users' => [
                    [
                        'name' => 'Accreditation Officer',
                        'email' => 'accreditation@example.com',
                        'password' => 'password',
                    ],
                    [
                        'name' => 'Ivory Accreditation',
                        'email' => 'ivory.accreditation@example.com',
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
                    'generated_adc.print_original'
                ],
                'users' => [
                    [
                        'name' => 'Claims Processor',
                        'email' => 'claims@example.com',
                        'password' => 'password',
                    ],
                    [
                        'name' => 'Ivory Claims',
                        'email' => 'ivory.claims@example.com',
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
                        'name' => 'Ivory CSR',
                        'email' => 'ivory.csr@example.com',
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
                    //     'name' => 'Ivory Dentist',
                    //     'email' => 'ivory.dentist@example.com',
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
                        'name' => 'Ivory Member',
                        'email' => 'ivory.member@example.com',
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
                        'name' => 'Ivory Middle Manager',
                        'email' => 'ivory.middle@example.com',
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
                        'name' => 'Ivory Upper Manager',
                        'email' => 'ivory.upper@example.com',
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
