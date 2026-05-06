<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Member;
use App\Models\MemberService;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        // INDIVIDUAL / DEFAULT — principal + dependents
        $this->seedIndividual('POL-MEDICARE', [
            ['first_name' => 'Juliana',  'last_name' => 'Santos',  'type' => 'PRINCIPAL', 'email' => 'member@example.com'],
            ['first_name' => 'Marco',    'last_name' => 'Santos',  'type' => 'DEPENDENT', 'email' => null],
            ['first_name' => 'Lia',      'last_name' => 'Santos',  'type' => 'DEPENDENT', 'email' => null],
        ]);

        // INDIVIDUAL / ALL_PRINCIPAL — principals only
        $this->seedIndividual('POL-CAREPLUS', [
            ['first_name' => 'Ivorry',   'last_name' => 'Reyes',   'type' => 'PRINCIPAL', 'email' => 'ivorry.member@example.com'],
            ['first_name' => 'Dante',    'last_name' => 'Cruz',    'type' => 'PRINCIPAL', 'email' => null],
        ]);

        // INDIVIDUAL / ALL_DEPENDENT — dependents only
        $this->seedIndividual('POL-WELLLIFE', [
            ['first_name' => 'Sofia',    'last_name' => 'Lim',     'type' => 'DEPENDENT', 'email' => null],
            ['first_name' => 'Noel',     'last_name' => 'Lim',     'type' => 'DEPENDENT', 'email' => null],
        ]);

        // SHARED / DEFAULT — principal + dependents per card
        $this->seedSharedFamily('POL-GLOBAL', 'CARD-GLOBAL01', [
            ['first_name' => 'Ramon',    'last_name' => 'Dela Cruz', 'type' => 'PRINCIPAL', 'email' => null],
            ['first_name' => 'Ana',      'last_name' => 'Dela Cruz', 'type' => 'DEPENDENT', 'email' => null],
            ['first_name' => 'Jose',     'last_name' => 'Dela Cruz', 'type' => 'DEPENDENT', 'email' => null],
        ]);
        $this->seedSharedFamily('POL-GLOBAL', 'CARD-GLOBAL02', [
            ['first_name' => 'Carla',    'last_name' => 'Tan',     'type' => 'PRINCIPAL', 'email' => null],
            ['first_name' => 'Luis',     'last_name' => 'Tan',     'type' => 'DEPENDENT', 'email' => null],
        ]);

        // SHARED / ALL_PRINCIPAL — principals only per card
        $this->seedSharedFamily('POL-PRIMECARE', 'CARD-PRIME01', [
            ['first_name' => 'Eduardo',  'last_name' => 'Flores',  'type' => 'PRINCIPAL', 'email' => null],
        ]);
        $this->seedSharedFamily('POL-PRIMECARE', 'CARD-PRIME02', [
            ['first_name' => 'Maricel',  'last_name' => 'Bautista', 'type' => 'PRINCIPAL', 'email' => null],
        ]);

        // SHARED / ALL_DEPENDENT — dependents only per card
        $this->seedSharedFamily('POL-SUNSHIELD', 'CARD-SUN01', [
            ['first_name' => 'Trisha',   'last_name' => 'Gomez',   'type' => 'DEPENDENT', 'email' => null],
            ['first_name' => 'Kevin',    'last_name' => 'Gomez',   'type' => 'DEPENDENT', 'email' => null],
        ]);
    }

    private function seedIndividual(string $policyCode, array $members): void
    {
        $account = Account::where('policy_code', $policyCode)->first();

        if (! $account) {
            $this->command->warn("Account {$policyCode} not found — skipping.");
            return;
        }

        foreach ($members as $data) {
            $cardNumber = 'CARD-' . strtoupper(Str::random(8));
            $user = $this->findOrCreateUser($data['first_name'], $data['last_name'], $data['email']);

            if ($user->member) continue;

            $member = Member::create([
                'account_id'      => $account->id,
                'user_id'         => $user->id,
                'first_name'      => $data['first_name'],
                'last_name'       => $data['last_name'],
                'member_type'     => $data['type'],
                'card_number'     => $cardNumber,
                'birthdate'       => now()->subYears(rand(25, 50)),
                'gender'          => collect(['Male', 'Female'])->random(),
                'email'           => $data['email'],
                'effective_date'  => $account->effective_date,
                'expiration_date' => $account->expiration_date,
                'status'          => 'ACTIVE',
                'mbl_balance'     => $account->mbl_type === 'Fixed' ? $account->mbl_amount : null,
            ]);

            MemberService::initializeForCard($cardNumber, $account->id);

            $this->command->info("  ✅ {$data['type']} {$data['first_name']} {$data['last_name']} → {$policyCode}");
        }
    }

    private function seedSharedFamily(string $policyCode, string $cardNumber, array $members): void
    {
        $account = Account::where('policy_code', $policyCode)->first();

        if (! $account) {
            $this->command->warn("Account {$policyCode} not found — skipping.");
            return;
        }

        $initialized = false;

        foreach ($members as $data) {
            $user = $this->findOrCreateUser($data['first_name'], $data['last_name'], $data['email']);

            if ($user->member) continue;

            Member::create([
                'account_id'      => $account->id,
                'user_id'         => $user->id,
                'first_name'      => $data['first_name'],
                'last_name'       => $data['last_name'],
                'member_type'     => $data['type'],
                'card_number'     => $cardNumber,
                'birthdate'       => now()->subYears(rand(25, 50)),
                'gender'          => collect(['Male', 'Female'])->random(),
                'email'           => $data['email'],
                'effective_date'  => $account->effective_date,
                'expiration_date' => $account->expiration_date,
                'status'          => 'ACTIVE',
                'mbl_balance'     => $account->mbl_type === 'Fixed' ? $account->mbl_amount : null,
            ]);

            $this->command->info("  ✅ {$data['type']} {$data['first_name']} {$data['last_name']} → {$policyCode} [{$cardNumber}]");
        }

        if (Member::where('card_number', $cardNumber)->where('account_id', $account->id)->exists()) {
            MemberService::initializeForCard($cardNumber, $account->id);
        }
    }

    private function findOrCreateUser(string $firstName, string $lastName, ?string $email): User
    {
        if ($email && $user = User::where('email', $email)->first()) {
            return $user;
        }

        $generatedEmail = $email ?? strtolower($firstName . '.' . $lastName . '.' . Str::random(4)) . '@example.com';

        return User::firstOrCreate(
            ['email' => $generatedEmail],
            [
                'name'                 => "{$firstName} {$lastName}",
                'password'             => Hash::make('password'),
                'must_change_password' => true,
            ]
        )->tap(fn($u) => $u->wasRecentlyCreated && $u->assignRole('Member'));
    }
}
