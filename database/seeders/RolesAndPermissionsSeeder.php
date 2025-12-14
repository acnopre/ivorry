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
            'member.approve_procedure',
            'member.deny_procedure',
            'member.confirm_procedure',
            'member.search',
            'member.upload',

            // Claims
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

            // SOA
            'soa.view',
            'soa.generate',

            // Accounts
            'account.view',
            'account.create',
            'account.update',
            'account.delete',
            'account.upload',
            'account.renew',
            'account.expire',
            'account.activate',
            'account.deactivate',
            'account.override',

            // Clinics
            'clinic.view',
            'clinic.create',
            'clinic.update',
            'clinic.delete',
            'clinic.set_status',
            'clinic.upload',

            // Dentists
            'dentist.view',
            'dentist.create',
            'dentist.update',
            'dentist.delete',
            'dentist.upload',

            // System
            'system.logs',
            'system.audit',
            'system.export',
            'system.manage_users',
            'system.manage_permissions',

            // Dashboard & Reports
            'dashboard.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $this->command->info('✅ All permissions seeded.');
    }
}
