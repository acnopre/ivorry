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

    public ?int $region = null;
    public ?int $province = null;
    public ?int $city = null;
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
                            ),

                        Forms\Components\Select::make('account_id')
                            ->label('Select Account')
                            ->options(function () {
                                return \App\Models\Account::pluck('company_name', 'id');
                            })
                            ->searchable()
                            ->placeholder('All Accounts')
                            ->visible(fn($get) => $get('accreditation_status') === 'SPECIFIC ACCOUNT'),


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
            ->with(['dentists.specializations', 'dentists', 'account']);
        if ($this->region) {
            $query->where('region_id', $this->region);
        }
        if ($this->province) {
            $query->where('province_id', $this->province);
        }
        if ($this->city) {
            $query->where('municipality_id', $this->city);
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
        }

        if ($this->hip) {
            $query->where('hip_id', $this->hip);
        }

        if ($this->account_id) {
            $query->where('account_id', $this->account_id);
        }

        $this->clinics = $query->get();
        $this->hasSearched = true;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check()
            && auth()->user()->can('member.search');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('member.search');
    }
}
