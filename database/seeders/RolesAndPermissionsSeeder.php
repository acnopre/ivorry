<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Member
            'member.view',
            'member.create',
            'member.update',
            'member.delete',
            'member.import',
            'import-logs.view',
            'import-logs.details.view',

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

            // Dentists
            'dentist.search',
            'dentist.view',
            'dentist.list',
            'dentist.add-procedure',
            'dentist.my-procedure',
            'dentist.update',

            // System
            'system.logs',
            'system.audit',
            'system.export',
            'system.manage_users',
            'system.manage_permissions',

            'reports.view',

            // Dashboard & Reports
            'dashboard.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $this->command->info('✅ All permissions seeded.');
    }
}
