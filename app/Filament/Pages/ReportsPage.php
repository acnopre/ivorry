<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportsExport;
use App\Models\Account;
use App\Models\AccreditationStatus;
use App\Models\BusinessType;

/**
 * MODELS
 */

use App\Models\Member;
use App\Models\Dentist;
use App\Models\Clinic;
use App\Models\Hip;
use App\Models\Municipality;
use App\Models\Procedure;
use App\Models\Province;
use App\Models\Region;
use App\Models\Specializations;
use App\Models\VatType;

class ReportsPage extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string $view = 'filament.pages.reports-page';
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?string $title = 'Reports';

    public ?array $reportFilters = [];
    public bool $hasGenerated = false;

    public function mount(): void
    {
        $this->form->fill();
    }

    /* -------------------------------------------------
     | FORM ACTION
     |-------------------------------------------------*/
    public function generate(): void
    {
        $filters = $this->form->getState();

        $hasInput = collect($filters)
            ->filter(fn($v) => filled($v))
            ->isNotEmpty();

        if (! $hasInput) {
            Notification::make()
                ->title('No Filters Applied')
                ->body('Please enter at least one search filter before generating the report.')
                ->warning()
                ->send();

            $this->hasGenerated = false;
            return;
        }

        $this->reportFilters = $filters;
        $this->hasGenerated = true;
    }

    /* -------------------------------------------------
     | FORM
     |-------------------------------------------------*/
    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Search Reports')
                ->schema([
                    Forms\Components\Grid::make(4)->schema([
                        Forms\Components\Select::make('reportType')
                            ->label('Select Report Type')
                            ->options([
                                'members'       => 'Member Status Report',
                                'dentists'      => 'Dentist List Report',
                                'clinics'       => 'Clinic Accrediation Status Report',
                                'procedures'    => 'Availment Report',
                                'accounts'      => 'Account Status Report',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function () {
                                $this->hasGenerated = false;
                            }),



                        Forms\Components\Select::make('plan_type')
                            ->label('Filter by Plan Type')
                            ->options([
                                'INDIVIDUAL'   => 'Individual',
                                'SHARED' => 'Shared',

                            ])
                            ->placeholder('All')
                            ->visible(fn($get) => $get('reportType') === 'accounts'),

                        Forms\Components\Select::make('coverage_period_type')
                            ->label('Filter by Coverage Period Type')
                            ->options([
                                'ACCOUNT'   => 'Account',
                                'MEMBER' => 'Member',

                            ])
                            ->placeholder('All')
                            ->visible(fn($get) => $get('reportType') === 'accounts'),

                        Forms\Components\Select::make('endorsement_type')
                            ->label('Filter by Endorsement Type')
                            ->options([
                                'NEW' => 'New',
                                'RENEWAL' => 'Renewal',
                                'RENEWED' => 'Renewed',
                                'AMENDMENT' => 'Amendment',
                                'AMENDED' => 'Amended',
                            ])
                            ->placeholder('All')
                            ->visible(fn($get) => $get('reportType') === 'accounts'),



                        Forms\Components\Select::make('status')
                            ->label('Filter by Accrediation Status')
                            ->options([
                                'ACTIVE'   => 'Active',
                                'INACTIVE' => 'Inactive',
                            ])
                            ->placeholder('All')
                            ->visible(fn($get) => $get('reportType') === 'members'),


                        Forms\Components\Select::make('accreditation_status')
                            ->label('Filter by Accreditation Status')
                            ->options(AccreditationStatus::pluck('name', 'name'))
                            ->searchable()
                            ->reactive()
                            ->placeholder('All')
                            ->visible(fn($get) => $get('reportType') === 'clinics'),

                        Forms\Components\Select::make('hip')
                            ->label('Select HIP')
                            ->options(Hip::pluck('name', 'name'))
                            ->searchable()
                            ->reactive()
                            ->placeholder('All')
                            ->visible(
                                fn($get) => ($get('reportType') === 'clinics' && trim(strtoupper($get('accreditation_status'))) === 'SPECIFIC HIP')
                                    || $get('reportType') === 'accounts' || $get('reportType') === 'members' || $get('reportType') === 'procedures'
                            ),

                        Forms\Components\Select::make('account_id')
                            ->label('Select Account')
                            ->options(function () {
                                return \App\Models\Account::pluck('company_name', 'id');
                            })
                            ->searchable()
                            ->placeholder('All Accounts')
                            ->visible(fn($get) => $get('reportType') === 'members' || $get('accreditation_status') === 'SPECIFIC ACCOUNT' || $get('reportType') === 'procedures'),


                        Forms\Components\Select::make('vat_type')
                            ->label('Filter by Vat Type')
                            ->options(VatType::pluck('name', 'name'))
                            ->searchable()
                            ->placeholder('All')
                            ->visible(fn($get) => $get('reportType') === 'clinics'),

                        Forms\Components\Select::make('business_type')
                            ->label('Filter by Business Type')
                            ->options(BusinessType::pluck('name', 'name'))
                            ->searchable()
                            ->placeholder('All')
                            ->visible(fn($get) => $get('reportType') === 'clinics'),


                        Forms\Components\Select::make('procedure_status')
                            ->label('Filter by Procedure Status')
                            ->options([
                                \App\Models\Procedure::STATUS_PENDING   => 'Pending',
                                \App\Models\Procedure::STATUS_SIGN => 'Signed',
                                \App\Models\Procedure::STATUS_VALID     => 'Valid',
                                \App\Models\Procedure::STATUS_REJECT    => 'Invalid',
                                \App\Models\Procedure::STATUS_RETURN    => 'Returned',
                                \App\Models\Procedure::STATUS_PROCESSED => 'Processed',
                            ])
                            ->placeholder('All')
                            ->visible(fn($get) => $get('reportType') === 'procedures'),



                        Forms\Components\Select::make('memberType')
                            ->label('Member Type')
                            ->options([
                                'PRINCIPAL' => 'Principal',
                                'DEPENDENT' => 'Dependent',
                            ])
                            ->visible(fn($get) => $get('reportType') === 'members'),

                        Forms\Components\Select::make('import_source')
                            ->label('Filter by Source')
                            ->options([
                                'manual'          => 'Manual',
                                'import_active'   => 'Imported (Active)',
                                'import_inactive' => 'Imported (Inactive)',
                            ])
                            ->placeholder('All')
                            ->visible(fn($get) => $get('reportType') === 'members'),


                        Forms\Components\Select::make('clinic_id')
                            ->label('Select Clinic')
                            ->options(function () {
                                return \App\Models\Clinic::pluck('clinic_name', 'id');
                            })
                            ->searchable()
                            ->placeholder('All Clinics')
                            ->visible(fn($get) => $get('reportType') === 'dentists' || $get('reportType') === 'procedures'),

                        Forms\Components\Select::make('specialization_id')
                            ->label('Select Specialization')
                            ->options(function () {
                                return \App\Models\Specializations::pluck('name', 'id');
                            })
                            ->searchable()
                            ->placeholder('All Clinics')
                            ->visible(fn($get) => $get('reportType') === 'dentists'),


                        Forms\Components\Select::make('region_id')
                            ->label('Select Region')
                            ->options(\App\Models\Region::pluck('name', 'id'))
                            ->searchable()
                            ->placeholder('All Region')

                            ->visible(
                                fn($get) => ($get('reportType') === 'dentists'
                                    || $get('reportType') === 'clinics')
                            ),

                        Forms\Components\Select::make('province_id')
                            ->label('Select Province')
                            ->options(function (callable $get) {
                                $regionId = $get('region_id');
                                return $regionId
                                    ? \App\Models\Province::where('region_id', $regionId)->pluck('name', 'id')
                                    : \App\Models\Province::pluck('name', 'id');
                            })
                            ->searchable()
                            ->placeholder('All Province')
                            ->visible(
                                fn($get) => ($get('reportType') === 'dentists'
                                    || $get('reportType') === 'clinics')
                            ),

                        Forms\Components\Select::make('municipality_id')
                            ->label('Select Municipality')
                            ->options(function (callable $get) {
                                $provinceId = $get('province_id');
                                return $provinceId
                                    ? \App\Models\Municipality::where('province_id', $provinceId)->pluck('name', 'id')
                                    : \App\Models\Municipality::pluck('name', 'id');
                            })
                            ->searchable()
                            ->placeholder('All Municipality')
                            ->visible(
                                fn($get) => ($get('reportType') === 'dentists'
                                    || $get('reportType') === 'clinics')
                            ),



                        Forms\Components\Toggle::make('filter_inactive_date')
                            ->label('Filter by Inactive Date')
                            ->reactive()
                            ->visible(fn($get) => $get('reportType') === 'members'),

                        Forms\Components\DatePicker::make('inactive_date_from')
                            ->label('Inactive Date From')
                            ->visible(
                                fn($get) =>
                                $get('reportType') === 'members' &&
                                    $get('filter_inactive_date')
                            ),

                        Forms\Components\DatePicker::make('inactive_date_to')
                            ->label('Inactive Date To')
                            ->visible(
                                fn($get) =>
                                $get('reportType') === 'members' &&
                                    $get('filter_inactive_date')
                            ),

                        Forms\Components\DatePicker::make('availment_from')
                            ->label('Availment From')
                            ->visible(
                                fn($get) =>
                                $get('reportType') === 'procedures'
                            ),


                        Forms\Components\DatePicker::make('availment_to')
                            ->label('Availment To')
                            ->visible(
                                fn($get) =>
                                $get('reportType') === 'procedures'
                            ),
                        Forms\Components\DatePicker::make('fromDate')->label('Created From Date')->visible(
                            fn($get) =>
                            $get('reportType') != 'procedures'
                        ),
                        Forms\Components\DatePicker::make('toDate')->label('Created To Date')->visible(
                            fn($get) =>
                            $get('reportType') != 'procedures'
                        ),

                    ]),
                ])
                ->footerActions([
                    Forms\Components\Actions\Action::make('clear')
                        ->label('Clear Filters')
                        ->icon('heroicon-o-x-circle')
                        ->color('gray')
                        ->action(function () {
                            // Reset all form fields
                            $this->form->fill([]);

                            // Optional: reset flags
                            $this->hasGenerated = false;
                        }),
                    Forms\Components\Actions\Action::make('generate')
                        ->label('Generate Report')
                        ->icon('heroicon-o-magnifying-glass')
                        ->color('primary')
                        ->action('generate'),
                ]),
        ];
    }

    protected function getFormStatePath(): ?string
    {
        return 'reportFilters';
    }

    /* -------------------------------------------------
     | TABLE
     |-------------------------------------------------*/
    public function table(Table $table): Table
    {

        return $table
            ->query(function () {
                if (! $this->hasGenerated) {
                    return Member::query()->whereRaw('1 = 0'); // EMPTY TABLE
                }
                return match ($this->reportFilters['reportType']) {
                    'members'    => $this->membersQuery(),
                    'dentists'   => $this->dentistsQuery(),
                    'clinics'    => $this->clinicsQuery(),
                    'procedures' => $this->proceduresQuery(),
                    'accounts'   => $this->accountsQuery(),
                    default    => Member::query()->whereRaw('1 = 0'),
                };
            })

            ->columns($this->columns())
            ->headerActions([
                Tables\Actions\Action::make('export_xls')
                    ->label('Export XLS')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->disabled(fn() => ! $this->hasGenerated)
                    ->action(fn() => $this->exportXls()),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public function exportXls()
    {
        if (! $this->hasGenerated) {
            Notification::make()
                ->title('Generate Report First')
                ->warning()
                ->send();
            return;
        }

        $type = $this->reportFilters['reportType'] ?? null;

        $query = match ($type) {
            'members'    => $this->membersQuery(),
            'dentists'   => $this->dentistsQuery(),
            'clinics'    => $this->clinicsQuery(),
            'procedures' => $this->proceduresQuery(),
            'accounts'   => $this->accountsQuery(),
            default      => null,
        };

        if (! $query) {
            Notification::make()
                ->title('Invalid Report')
                ->danger()
                ->send();
            return;
        }

        $filters = [];
        $filters = match ($type) {
            'members' => (function () {
                $account = Account::find($this->reportFilters['account_id']);

                return [
                    'account_name'  => $account?->company_name ?? 'All',
                    'from_date'     => $this->reportFilters['fromDate'] ?? '-',
                    'to_date'       => $this->reportFilters['toDate'] ?? '-',
                    'member_status' => $this->reportFilters['status'] ?? 'All',
                    'member_type'   => $this->reportFilters['memberType'] ?? 'All',
                    'source'        => match($this->reportFilters['import_source'] ?? null) {
                        'import_inactive' => 'Imported (Inactive)',
                        'import_active'   => 'Imported (Active)',
                        'manual'          => 'Manual',
                        default           => 'All',
                    },
                ];
            })(),

            'dentists' => (function () {
                $clinic = Clinic::find($this->reportFilters['clinic_id']);
                $specialization = Specializations::where('id', $this->reportFilters['specialization_id'])->first();
                $region = Region::find($this->reportFilters['region_id']);
                $province = Province::find($this->reportFilters['province_id']);
                $municipality = Municipality::find($this->reportFilters['municipality_id']);

                return [
                    'clinic_name'   => $clinic?->clinic_name ?? 'All',
                    'from_date'      => $this->reportFilters['fromDate'] ?? '-',
                    'to_date'        => $this->reportFilters['toDate'] ?? '-',
                    'specializations' => $specialization['name'] ?? 'All',
                    'region' => $region?->name,
                    'province' => $province?->name,
                    'municipality' => $municipality?->name

                ];
            })(),

            'clinics' => (function () {
                $region = Region::find($this->reportFilters['region_id']);
                $province = Province::find($this->reportFilters['province_id']);
                $municipality = Municipality::find($this->reportFilters['municipality_id']);
                return [
                    'from_date'      => $this->reportFilters['fromDate'] ?? '-',
                    'to_date'        => $this->reportFilters['toDate'] ?? '-',
                    'accreditation_status' => $this->reportFilters['accreditation_status'] ?? 'All',
                    'vat_type' => $this->reportFilters['vat_type'] ?? 'All',
                    'business_type' => $this->reportFilters['business_type'] ?? 'All',
                    'region' => $region?->name,
                    'province' => $province?->name,
                    'municipality' => $municipality?->name,
                ];
            })(),

            'procedures' => (function () {
                return [
                    'from_date'      => $this->reportFilters['fromDate'] ?? '-',
                    'to_date'        => $this->reportFilters['toDate'] ?? '-',
                    'procedure_status' => $this->reportFilters['procedure_status'] ?? 'All',
                ];
            })(),


            'accounts' => (function () {
                return [
                    'from_date'      => $this->reportFilters['fromDate'] ?? '-',
                    'to_date'        => $this->reportFilters['toDate'] ?? '-',
                    'hip' => $this->reportFilters['hip'] ?? 'All',
                    'plan_type' => $this->reportFilters['plan_type'] ?? 'All',
                    'coverage_period_type' => $this->reportFilters['coverage_period_type'] ?? 'All',
                    'endorsement_type' => $this->reportFilters['endorsement_type'] ?? 'All',

                ];
            })(),

            default => [],
        };


        $filename = strtoupper($type) . '_REPORT_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new \App\Exports\ReportsExport($query, $type, $filters),
            $filename
        );
    }



    /* -------------------------------------------------
     | QUERY BUILDERS
     |-------------------------------------------------*/
    protected function membersQuery(): Builder
    {
        $f = $this->reportFilters;

        return Member::query()
            ->with('account') // eager load account
            ->when($f['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->when($f['memberType'] ?? null, fn($q, $v) => $q->where('member_type', $v))
            ->when($f['import_source'] ?? null, fn($q, $v) => $q->where('import_source', $v))
            ->when($f['account_id'] ?? null, fn($q, $v) => $q->where('account_id', $v))
            ->when(
                $f['hip'] ?? null,
                fn($q, $v) =>
                $q->whereHas(
                    'account',
                    fn($q) =>
                    $q->where('hip', $v)
                )
            )

            ->when(
                !empty($f['inactive_date_from']) && !empty($f['inactive_date_to']),
                fn($q) =>
                $q->whereBetween('inactive_date', [$f['inactive_date_from'], $f['inactive_date_to']])
            )
            ->when(
                !empty($f['fromDate']) && !empty($f['toDate']),
                fn($q) =>
                $q->whereBetween('created_at', [$f['fromDate'], $f['toDate']])
            );

        $this->hasGenerated = false;
    }

    protected function dentistsQuery(): Builder
    {
        $f = $this->reportFilters;

        return Dentist::query()
            ->with('specializations')
            ->with('clinic')
            ->when($f['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->when($f['clinic_id'] ?? null, fn($q, $v) => $q->where('clinic_id', $v))
            ->when(
                $f['region_id'] ?? null,
                fn($q, $v) =>
                $q->whereHas(
                    'clinic',
                    fn($q) =>
                    $q->where('region_id', $v)
                )
            )

            ->when(
                $f['province_id'] ?? null,
                fn($q, $v) =>
                $q->whereHas(
                    'clinic',
                    fn($q) =>
                    $q->where('province_id', $v)
                )
            )

            ->when(
                $f['municipality_id'] ?? null,
                fn($q, $v) =>
                $q->whereHas(
                    'clinic',
                    fn($q) =>
                    $q->where('municipality_id', $v)
                )
            )
            ->when(
                !empty($f['fromDate']) && !empty($f['toDate']),
                fn($q) => $q->whereBetween('created_at', [$f['fromDate'], $f['toDate']])
            )
            ->when(
                $f['specialization_id'] ?? null,
                fn($q, $v) => $q->whereHas('specializations', fn($q2) => $q2->where('specializations.id', $v))
            );
        $this->hasGenerated = false;
    }


    protected function clinicsQuery(): Builder
    {
        $f = $this->reportFilters;
        $query = Clinic::query()
            ->when($f['accreditation_status'] ?? null, fn($q, $v) => $q->where('accreditation_status', $v))
            ->when($f['account_id'] ?? null, fn($q, $v) => $q->where('account_id', $v))
            ->when($f['hip_id'] ?? null, fn($q, $v) => $q->where('hip_id', $v))
            ->when($f['vat_type'] ?? null, fn($q, $v) => $q->where('vat_type', $v))
            ->when($f['business_type'] ?? null, fn($q, $v) => $q->where('business_type', $v))

            ->when($f['region_id'] ?? null, fn($q, $v) => $q->where('region_id', $v))
            ->when($f['province_id'] ?? null, fn($q, $v) => $q->where('province_id', $v))
            ->when($f['municipality_id'] ?? null, fn($q, $v) => $q->where('municipality_id', $v))

            ->when(
                !empty($f['fromDate']) && !empty($f['toDate']),
                fn($q) =>
                $q->whereBetween('created_at', [$f['fromDate'], $f['toDate']])
            );
        return $query;
    }

    protected function proceduresQuery(): Builder
    {
        $f = $this->reportFilters;

        return Procedure::query()
            ->with(['member.account', 'clinic', 'service', 'units.unitType'])
            ->when($f['procedure_status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->when($f['clinic_id'] ?? null, fn($q, $v) => $q->where('clinic_id', $v))
            ->when($f['hip'] ?? null, function ($query, $hip) {
                $query->whereHas('member.account', function ($q) use ($hip) {
                    $q->where('hip', $hip);
                });
            })
            ->when($f['account_id'] ?? null, function ($query, $account_id) {
                $query->whereHas('member', function ($q) use ($account_id) {
                    $q->where('account_id', $account_id);
                });
            })
            ->when(
                !empty($f['availment_from']) && !empty($f['availment_to']),
                fn($q) =>
                $q->whereBetween('availment_date', [$f['availment_from'], $f['availment_to']])
            );
    }

    protected function accountsQuery(): Builder
    {
        $f = $this->reportFilters;

        return Account::query()
            ->when($f['hip'] ?? null, fn($q, $v) => $q->where('hip', $v))
            ->when($f['status'] ?? null, fn($q, $v) => $q->where('account_status', $v))
            ->when($f['plan_type'] ?? null, fn($q, $v) => $q->where('plan_type', $v))
            ->when($f['coverage_period_type'] ?? null, fn($q, $v) => $q->where('coverage_period_type', $v))
            ->when($f['endorsement_type'] ?? null, fn($q, $v) => $q->where('endorsement_type', $v))
            ->when($f['endorsement_status'] ?? null, fn($q, $v) => $q->where('endorsement_status', $v))

            ->when(
                !empty($f['fromDate']) && !empty($f['toDate']),
                fn($q) => $q->whereBetween('created_at', [$f['fromDate'], $f['toDate']])
            );
    }



    /* -------------------------------------------------
     | DYNAMIC COLUMNS
     |-------------------------------------------------*/
    protected function columns(): array
    {
        return match ($this->reportFilters['reportType'] ?? null) {
            'members' => [
                Tables\Columns\TextColumn::make('account.company_name') // use the relationship
                    ->label('Account'),
                Tables\Columns\TextColumn::make('account.hip')
                    ->label('HIP'),
                Tables\Columns\TextColumn::make('full_name')->label('Member'),
                Tables\Columns\TextColumn::make('member_type')->label('Member Type'),
                Tables\Columns\TextColumn::make('card_number')->label('Card Number'),
                Tables\Columns\TextColumn::make('gender'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('import_source')
                    ->label('Source')
                    ->badge()
                    ->color(fn($state) => match($state) {
                        'import_inactive' => 'danger',
                        'import_active'   => 'success',
                        default           => 'gray',
                    })
                    ->formatStateUsing(fn($state) => match($state) {
                        'import_inactive' => 'Imported (Inactive)',
                        'import_active'   => 'Imported (Active)',
                        default           => 'Manual',
                    }),
                Tables\Columns\TextColumn::make('account.effective_date')
                    ->label('Account Effective Date')
                    ->date('M d, Y'),

                Tables\Columns\TextColumn::make('account.expiration_date')
                    ->label('Account Expiration Date')
                    ->date('M d, Y'),

                Tables\Columns\TextColumn::make('inactive_date')
                    ->label('Inactive Date'),
                Tables\Columns\TextColumn::make('created_at')->date()->label('Date Created'),
            ],

            'dentists' => [
                Tables\Columns\TextColumn::make('clinic.clinic_name') // use the relationship
                    ->label('Clinic Name'),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Dentist Name')
                    ->getStateUsing(fn($record) => $record->first_name . ' ' . $record->last_name),

                Tables\Columns\TextColumn::make('specialization')
                    ->label('Specialization')
                    ->getStateUsing(function ($record) {
                        return $record->specializations->pluck('name')->implode(', ');
                    }),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('created_at')->date()->label('Date Added'),
            ],

            'clinics' => [
                Tables\Columns\TextColumn::make('clinic_name')->label('Clinic Name'),
                Tables\Columns\TextColumn::make('registered_name')->label('Registered Name'),
                Tables\Columns\TextColumn::make('complete_address')->label('Address'),
                Tables\Columns\TextColumn::make('is_branch')
                    ->label('Branch')
                    ->getStateUsing(fn($record) => $record->is_branch ? 'YES' : 'NO'),
                Tables\Columns\TextColumn::make('business_type')->label('Business Type'),
                Tables\Columns\TextColumn::make('vat_type')->label('Vat Type'),
                Tables\Columns\TextColumn::make('witholding_tax')->label('Witholding Tax'),
                Tables\Columns\TextColumn::make('accreditation_status')->badge(),
                Tables\Columns\TextColumn::make('created_at')->date()->label('Date Added'),
            ],

            'procedures' => [
                Tables\Columns\TextColumn::make('availment_date')
                    ->label('Availment Date')
                    ->date('M d, Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Member Name')
                    ->getStateUsing(fn($record) => $record->member->first_name . ' ' . $record->member->last_name)
                    ->searchable(),
                Tables\Columns\TextColumn::make('member.account.company_name')
                    ->label('Account')
                    ->searchable(),
                Tables\Columns\TextColumn::make('member.account.hip')
                    ->label('HIP'),
                Tables\Columns\TextColumn::make('clinic.clinic_name')
                    ->label('Clinic Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Procedure Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('units')
                    ->label('Units')
                    ->getStateUsing(function ($record) {
                        return $record->units
                            ->map(function ($unit) {
                                $unitTypeName = $unit->unitType->name ?? 'N/A';
                                $unitName = $unit->name ?? 'N/A';
                                $surface = isset($unit->pivot->surface) ? ' — Surface: ' . $unit->pivot->surface->name : '';
                                return $unitTypeName . ': ' . $unitName . $surface;
                            })
                            ->join(', ');
                    }),
                Tables\Columns\TextColumn::make('applied_fee')
                    ->label('Applied Fee')
                    ->money('php', true),
                Tables\Columns\TextColumn::make('approval_code')
                    ->label('Approval Code')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date Added')
                    ->date('M d, Y')
                    ->sortable(),
            ],

            'accounts' => [
                Tables\Columns\TextColumn::make('company_name')->label('Account Name'),
                Tables\Columns\TextColumn::make('policy_code')->label('Policy Code'),
                Tables\Columns\TextColumn::make('hip')->label('HIP'),
                Tables\Columns\TextColumn::make('effective_date')->date()->label('Effective Date'),
                Tables\Columns\TextColumn::make('expiration_date')->date()->label('Expiration Date'),
                Tables\Columns\TextColumn::make('plan_type')->label('Plan Type'),
                Tables\Columns\TextColumn::make('coverage_period_type')->label('Coverage Period Type'),
                Tables\Columns\TextColumn::make('account_status')->badge(),
                Tables\Columns\TextColumn::make('created_at')->date()->label('Date Created'),
            ],

            default => [],
        };
    }


    public static function canAccess(): bool
    {
        return auth()->user()->can('reports.view');
    }
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check()
            && auth()->user()->can('reports.view');
    }
}
