<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Faker\Factory as Faker;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // Create the "Member" role and assign permissions
        $role = Role::firstOrCreate(['name' => 'Member']);
        $permissions = ['member.view', 'chatbot.use'];
        $role->syncPermissions(Permission::whereIn('name', $permissions)->get());

        // Get all account IDs
        $accountIds = DB::table('accounts')->pluck('id');

        foreach ($accountIds as $accountId) {
            for ($i = 1; $i <= 3; $i++) {
                // Generate a random full name
                $memberName = $faker->name;

                // Create a new user for this member
                $user = User::create([
                    'name'     => $memberName,
                    'email'    => $faker->unique()->safeEmail,
                    'password' => Hash::make('password'),
                ]);

                $user->assignRole($role);

                // Create the member linked to this account and user
                DB::table('members')->insert([
                    'account_id'  => $accountId,
                    'user_id'     => $user->id,
                    'name'        => $memberName,
                    'first_name'  => 'Test First name',
                    'last_name'   => 'Test Last name',
                    'member_type' => $i === 1 ? 'PRINCIPAL' : 'DEPENDENT',
                    'card_number' => 'CARD-' . rand(1000, 9999),
                    'birthdate'   => $faker->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
                    'gender'      => $faker->randomElement(['Male', 'Female']),
                    'email'       => $user->email,
                    'phone'       => '+639' . $faker->numerify('#########'),
                    'address'     => $faker->address,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }

        $this->command->info('✅ Members and users seeded successfully with random names!');
    }
}
