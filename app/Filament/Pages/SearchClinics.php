<?php

namespace App\Filament\Pages;

use App\Models\AccreditationStatus;
use App\Models\Clinic;
use App\Models\Dentist;
use App\Models\Hip;
use App\Models\Region;
use App\Models\Province;
use App\Models\Municipality;
use App\Models\Role;
use App\Models\Specializations;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class SearchClinics extends Page
{
    protected static ?string $title = 'Search Clinics';
    protected static string $view = 'filament.pages.search-clinics';
    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';
    protected static ?string $navigationGroup = 'Search';
    protected static ?int $navigationSort = 3;

    public ?int $region = null;
    public ?int $province = null;
    public ?int $city = null;
    public ?string $clinic_name = null;
    public ?string $dentist_last_name = null;
    public array $specialization = [];
    public ?string $accreditation_status = null;
    public ?string $hip = null;
    public ?int $account_id = null;

    public Collection $clinics;
    public bool $hasSearched = false;

    public function mount(): void
    {
        $this->clinics = collect();

        // Auto-resolve member's account and HIP for filtering
        $member = \App\Models\Member::where('user_id', auth()->id())->first();
        if ($member) {
            $this->account_id = $member->account_id;
            $this->hip = $member->account?->hip_id;
        }
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Search Clinics')
                ->schema([
                    Forms\Components\Grid::make(3)->schema([
                        // REGION
                        Forms\Components\Select::make('region')
                            ->label('Region')
                            ->options(Region::pluck('name', 'id'))
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(fn($state) => $this->reset(['province', 'city'])),

                        // PROVINCE (depends on region)
                        Forms\Components\Select::make('province')
                            ->label('Province')
                            ->options(
                                fn(callable $get) =>
                                $get('region')
                                    ? Province::where('region_id', $get('region'))->pluck('name', 'id')
                                    : collect()
                            )
                            ->reactive()
                            ->searchable()
                            ->afterStateUpdated(fn($state) => $this->reset('city')),

                        // CITY (depends on province)
                        Forms\Components\Select::make('city')
                            ->label('City / Municipality')
                            ->options(
                                fn(callable $get) =>
                                $get('province')
                                    ? Municipality::where('province_id', $get('province'))->pluck('name', 'id')
                                    : collect()
                            )
                            ->searchable(),

                        Forms\Components\TextInput::make('clinic_name')
                            ->label('Clinic Name')
                            ->placeholder('Enter Clinic Name'),

                        // DENTIST LAST NAME
                        Forms\Components\TextInput::make('dentist_last_name')
                            ->label('Dentist Last Name')
                            ->placeholder('Enter Dentist Last Name'),

                        // SPECIALIZATION
                        Forms\Components\Select::make('specialization')
                            ->label('Specialization')
                            ->multiple()
                            ->options(Specializations::pluck('name', 'id'))
                            ->searchable(),

                        Forms\Components\Select::make('accreditation_status')
                            ->label('Accreditation Status')
                            ->options(AccreditationStatus::pluck('name', 'name'))
                            ->searchable()
                            ->reactive()
                            ->placeholder('All')
                            ->visible(auth()->user()->hasAnyRole([
                                Role::SUPER_ADMIN,
                                Role::CSR,
                            ])),

                        Forms\Components\Select::make('hip')
                            ->label('Select HIP')
                            ->options(Hip::pluck('name', 'id'))
                            ->searchable()
                            ->reactive()
                            ->placeholder('All')
                            ->visible(
                                fn($get) => trim(strtoupper($get('accreditation_status'))) === 'SPECIFIC HIP'
                                    && auth()->user()->hasAnyRole([Role::SUPER_ADMIN, Role::CSR])
                            ),

                        Forms\Components\Select::make('account_id')
                            ->label('Select Account')
                            ->options(function () {
                                return \App\Models\Account::where('account_status', 'active')
                                    ->get()
                                    ->mapWithKeys(fn($a) => [$a->id => "{$a->company_name} ({$a->policy_code})"]);
                            })
                            ->searchable()
                            ->placeholder('All Accounts')
                            ->visible(
                                fn($get) => $get('accreditation_status') === 'SPECIFIC ACCOUNT'
                                    && auth()->user()->hasAnyRole([Role::SUPER_ADMIN, Role::CSR])
                            ),


                    ]),
                ])
                ->footerActions([
                    Forms\Components\Actions\Action::make('search_action')
                        ->label('Search Clinics')
                        ->icon('heroicon-o-magnifying-glass')
                        ->color('primary')
                        ->action('search'),
                ]),
        ];
    }

    public function search(): void
    {
        // if (! $this->region && ! $this->province && ! $this->city && ! $this->dentist_last_name && empty($this->specialization)) {
        //     Notification::make()
        //         ->title('No Filters Applied')
        //         ->body('Please enter at least one search filter before searching.')
        //         ->warning()
        //         ->send();

        //     $this->clinics = collect();
        //     $this->hasSearched = false;
        //     return;
        // }

        $query = Clinic::query()
            ->with(['dentists.specializations', 'dentists', 'account'])
            ->where('accreditation_status', '!=', 'SILENT');
        if ($this->region) {
            $query->where('region_id', $this->region);
        }
        if ($this->province) {
            $query->where('province_id', $this->province);
        }
        if ($this->city) {
            $query->where('municipality_id', $this->city);
        }
        if ($this->clinic_name) {
            $query->where('clinic_name', 'like', "%{$this->clinic_name}%");
        }

        if ($this->dentist_last_name) {
            $query->whereHas(
                'dentists',
                fn($q) =>
                $q->where('last_name', 'like', "%{$this->dentist_last_name}%")
            );
        }
        if (!empty($this->specialization)) {
            $query->whereHas(
                'dentists.specializations',
                fn($q) =>
                $q->whereIn('id', $this->specialization)
            );
        }

        if ($this->accreditation_status) {
            $query->where('accreditation_status', $this->accreditation_status);

            if ($this->accreditation_status === 'SPECIFIC ACCOUNT' && $this->account_id) {
                $query->where('account_id', $this->account_id);
            }

            if ($this->accreditation_status === 'SPECIFIC HIP' && $this->hip) {
                $query->where('hip_id', $this->hip);
            }
        } elseif ($this->account_id || $this->hip) {
            // Member context: show ACTIVE clinics + SPECIFIC ACCOUNT for their account + SPECIFIC HIP for their HIP
            $query->where(function ($q) {
                $q->where('accreditation_status', 'ACTIVE');

                if ($this->account_id) {
                    $q->orWhere(function ($q2) {
                        $q2->where('accreditation_status', 'SPECIFIC ACCOUNT')
                           ->where('account_id', $this->account_id);
                    });
                }

                if ($this->hip) {
                    $q->orWhere(function ($q2) {
                        $q2->where('accreditation_status', 'SPECIFIC HIP')
                           ->where('hip_id', $this->hip);
                    });
                }
            });
        }

        $this->clinics = $query->get();
        $this->hasSearched = true;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check()
            && auth()->user()->can('member.search');
    }

    public static function canAccess(): bool
    {
        return auth()->user()->can('member.search');
    }
}
