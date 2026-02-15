<?php

namespace Database\Seeders;

use App\Models\BasicDentalService;
use App\Models\User;
use Database\Seeders\AccountManagerSeeder;
use Database\Seeders\AccountSeeder;
use Database\Seeders\AccountServicesSeeder;
use Database\Seeders\AccreditationSeeder;
use Database\Seeders\AccreditationStatusSeeder;
use Database\Seeders\BasicDentalServicesSeeder;
use Database\Seeders\BenefitsSeeder;
use Database\Seeders\ClaimsProcessorSeeder;
use Database\Seeders\ClinicSeeder;
use Database\Seeders\CSRSeeder;
use Database\Seeders\DentalPlanBenefitsSeeder;
use Database\Seeders\DentistAccountSeeder;
use Database\Seeders\DentistSeeder;
use Database\Seeders\EndorsementTypeSeeder;
use Database\Seeders\MemberSeeder;
use Database\Seeders\MiddleManagementSeeder;
use Database\Seeders\PlanEnhancementsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SpecializationSeeder;
use Database\Seeders\SuperAdminSeeder;
use Database\Seeders\UpperManagementSeeder;
use Database\Seeders\ServicesSeeder;
use Database\Seeders\DropdownSeeder;
use Database\Seeders\UnitSeeder;
use Database\Seeders\UnitTypeSeeder;
use Database\Seeders\AddressSeeder;
use Database\Seeders\ProcedureSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            SuperAdminSeeder::class,
            AccreditationStatusSeeder::class,
            SpecializationSeeder::class,
            EndorsementTypeSeeder::class,
            AccountSeeder::class,
            MemberSeeder::class,
            ServicesSeeder::class,
            DropdownSeeder::class,
            UnitTypeSeeder::class,
            UnitSeeder::class,
            // ClinicSeeder::class,
            DentistSeeder::class,
            AddressSeeder::class,
            AccountServicesSeeder::class,
            ProcedureSeeder::class,
            ReturnReasonsSeeder::class,
            InvalidReasonsSeeder::class,
            HipSeeder::class,
            MblTypeSeeder::class,
        ]);
    }
}
