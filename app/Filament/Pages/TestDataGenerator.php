<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Str;

class TestDataGenerator extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.pages.test-data-generator';
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(['count' => 5, 'members_per_account' => 3]);
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Generate Test Data')
                    ->schema([
                        Forms\Components\TextInput::make('count')
                            ->label('Number of Accounts')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(500)
                            ->required()
                            ->default(5),

                        Forms\Components\TextInput::make('members_per_account')
                            ->label('Members per Account')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(20)
                            ->required()
                            ->default(3)
                            ->helperText('Each account gets 1 principal + N-1 dependents'),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    private function buildAccountRows(int $count): array
    {
        $hips = [
            'ETIQA LIFE AND GENERAL ASSURANCE PHILIPPINES, INC.',
            'MARSH PHILIPPINES, INC.',
            'Magsaysay Houlder Insurance Brokers Inc.',
            'OMNI International Consultants, Inc.',
            'Generali Life Assurance Phils, Inc.',
            'KWIK CARE',
            'MM ROYAL CARE',
        ];

        $planTypes     = ['INDIVIDUAL', 'SHARED'];
        $coverageTypes = ['ACCOUNT', 'MEMBER'];
        $mblTypes      = ['Procedural', 'Fixed'];
        $cardUsed      = ['IVORRY', 'HMO'];
        $effectiveDate  = Carbon::today()->format('Y-m-d');
        $expirationDate = Carbon::today()->addYear()->subDay()->format('Y-m-d');

        $rows = [];
        for ($i = 0; $i < $count; $i++) {
            $mblType = $mblTypes[array_rand($mblTypes)];
            $rows[] = [
                'company_name'    => 'Test Company ' . strtoupper(Str::random(6)),
                'policy_code'     => 'POL-' . strtoupper(Str::random(8)),
                'hip'             => $hips[array_rand($hips)],
                'card_used'       => $cardUsed[array_rand($cardUsed)],
                'effective_date'  => $effectiveDate,
                'expiration_date' => $expirationDate,
                'plan_type'       => $planTypes[array_rand($planTypes)],
                'coverage_type'   => $coverageTypes[array_rand($coverageTypes)],
                'endorsement_type' => 'NEW',
                'mbl_type'        => $mblType,
                'mbl_amount'      => $mblType === 'Fixed' ? rand(5000, 50000) : '',
                'consultation'                                               => 'unlimited',
                'treatment_of_sores_blisters'                               => 'unlimited',
                'temporary_fillings'                                         => 'unlimited',
                'simple_tooth_extraction'                                    => 'unlimited',
                'recementation_of_fixed_bridges_crowns_jackets_inlays_onlays' => 'unlimited',
                'adjustment_of_dentures'                                     => 'unlimited',
                'oral_prophylaxis'                                           => rand(1, 4),
                'permanent_filling_per_tooth'                                => rand(1, 4),
                'permanent_filling_per_surface'                              => rand(1, 6),
                'desensitization_of_hypersensitive_teeth'                    => rand(1, 3),
                'fluoride_brushing'                                          => rand(1, 2),
                'incision_and_drainage'                                      => rand(1, 2),
                'peri_apical_xray'                                           => rand(1, 4),
                'panoramic_xray'                                             => rand(1, 2),
                'complicated_difficult_extraction'                           => rand(1, 2),
                'odontectomy_removal_of_impacted_tooth'                      => rand(1, 2),
                'root_canal_treatment_per_canal'                             => rand(1, 4),
                'root_canal_treatment_per_tooth'                             => rand(1, 2),
                'jacket_crowns'                                              => rand(1, 2),
                'dentures'                                                   => rand(1, 2),
                'pit_and_fissure_sealants'                                   => rand(1, 3),
                'topical_fluoride_application'                               => rand(1, 2),
                'minor_soft_tissue_surgery'                                  => rand(1, 2),
            ];
        }

        return $rows;
    }

    private function buildMemberRows(array $accounts, int $membersPerAccount): array
    {
        $firstNames  = ['James', 'Maria', 'John', 'Ana', 'Carlos', 'Rosa', 'Miguel', 'Elena', 'Jose', 'Carmen',
                        'Luis', 'Sofia', 'Pedro', 'Isabel', 'Antonio', 'Laura', 'Manuel', 'Patricia', 'David', 'Sandra'];
        $lastNames   = ['Santos', 'Reyes', 'Cruz', 'Garcia', 'Torres', 'Flores', 'Ramos', 'Mendoza', 'Lopez', 'Gonzales',
                        'Dela Cruz', 'Bautista', 'Aquino', 'Villanueva', 'Castillo', 'Morales', 'Navarro', 'Diaz', 'Lim', 'Tan'];
        $middleNames = ['Mae', 'Joy', 'Ann', 'Grace', 'Rose', 'Lee', 'Marie', 'Jane', 'Faith', 'Hope'];
        $genders     = ['male', 'female'];

        $rows = [];
        foreach ($accounts as $account) {
            $accountName = is_array($account) ? $account['company_name'] : $account;
            $planType    = is_array($account) ? strtoupper($account['plan_type'] ?? 'INDIVIDUAL') : 'INDIVIDUAL';

            // SHARED: all members share 1 card number
            // INDIVIDUAL: each member gets their own card number
            $sharedCardNumber = str_pad(rand(0, 999999999999), 12, '0', STR_PAD_LEFT);

            for ($j = 0; $j < $membersPerAccount; $j++) {
                $firstName  = $firstNames[array_rand($firstNames)];
                $lastName   = $lastNames[array_rand($lastNames)];
                $memberType = $j === 0 ? 'PRINCIPAL' : 'DEPENDENT';
                $cardNumber = $planType === 'SHARED'
                    ? $sharedCardNumber
                    : str_pad(rand(0, 999999999999), 12, '0', STR_PAD_LEFT);

                $rows[] = [
                    'account_name'    => $accountName,
                    'first_name'      => $firstName,
                    'last_name'       => $lastName,
                    'middle_name'     => $middleNames[array_rand($middleNames)],
                    'suffix'          => '',
                    'member_type'     => $memberType,
                    'card_number'     => $cardNumber,
                    'birthdate'       => Carbon::now()->subYears(rand(20, 55))->subDays(rand(0, 365))->format('Y-m-d'),
                    'gender'          => $genders[array_rand($genders)],
                    'email'           => strtolower($firstName . '.' . $lastName . rand(10, 999)) . '@example.com',
                    'phone'           => '09' . rand(100000000, 999999999),
                    'address'         => rand(1, 999) . ' ' . $lastNames[array_rand($lastNames)] . ' St., Manila',
                    'status'          => 'ACTIVE',
                    'inactive_date'   => '',
                    'effective_date'  => '',
                    'expiration_date' => '',
                ];
            }
        }

        return $rows;
    }

    private function makeExport(array $rows): object
    {
        return new class($rows) implements FromArray, WithHeadings {
            public function __construct(private array $rows) {}
            public function array(): array { return $this->rows; }
            public function headings(): array { return array_keys($this->rows[0]); }
        };
    }

    public function generateBoth(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->form->validate();
        $count             = (int) $this->data['count'];
        $membersPerAccount = (int) $this->data['members_per_account'];

        $accountRows  = $this->buildAccountRows($count);
        $memberRows   = $this->buildMemberRows($accountRows, $membersPerAccount);

        $timestamp   = now()->format('Ymd_His');
        $accountPath = tempnam(sys_get_temp_dir(), 'accounts_') . '.xlsx';
        $memberPath  = tempnam(sys_get_temp_dir(), 'members_') . '.xlsx';
        $zipPath     = tempnam(sys_get_temp_dir(), 'test_data_') . '.zip';

        // Write XLS files to temp
        file_put_contents($accountPath, Excel::raw($this->makeExport($accountRows), \Maatwebsite\Excel\Excel::XLSX));
        file_put_contents($memberPath, Excel::raw($this->makeExport($memberRows), \Maatwebsite\Excel\Excel::XLSX));

        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFile($accountPath, "accounts_{$timestamp}.xlsx");
        $zip->addFile($memberPath, "members_{$timestamp}.xlsx");
        $zip->close();

        register_shutdown_function(function () use ($accountPath, $memberPath, $zipPath) {
            @unlink($accountPath);
            @unlink($memberPath);
            @unlink($zipPath);
        });

        return response()->download($zipPath, "test_data_{$timestamp}.zip");
    }

    public function generateAccounts(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->form->validate();
        $rows = $this->buildAccountRows((int) $this->data['count']);
        return Excel::download($this->makeExport($rows), 'test_accounts_' . now()->format('Ymd_His') . '.xlsx');
    }

    public function generateMembers(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->form->validate();

        $accounts = \App\Models\Account::select('company_name', 'plan_type')->get()->toArray();
        if (empty($accounts)) {
            Notification::make()->title('No accounts found in the database.')->danger()->send();
            return back();
        }

        $rows = $this->buildMemberRows($accounts, (int) $this->data['members_per_account']);
        return Excel::download($this->makeExport($rows), 'test_members_' . now()->format('Ymd_His') . '.xlsx');
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }
}
