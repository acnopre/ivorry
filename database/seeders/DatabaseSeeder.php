<?php

use App\Models\BasicDentalService;
use App\Models\User;
use Database\Seeders\AccountManagerSeeder;
use Database\Seeders\AccountSeeder;
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
use Database\Seeders\UnitsSeeder;
use Database\Seeders\UpperManagementSeeder;
use Database\Seeders\ServicesSeeder;
use Database\Seeders\DropdownSeeder;
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
            CSRSeeder::class,
            ClaimsProcessorSeeder::class,
            AccountManagerSeeder::class,
            AccreditationSeeder::class,
            MiddleManagementSeeder::class,
            MemberSeeder::class,
            DentistAccountSeeder::class,
            UpperManagementSeeder::class,
            AccreditationStatusSeeder::class,
            SpecializationSeeder::class,
            EndorsementTypeSeeder::class,
            UnitsSeeder::class,
            AccountSeeder::class,
            ServicesSeeder::class,
            DropdownSeeder::class,
        ]);
    }
}
