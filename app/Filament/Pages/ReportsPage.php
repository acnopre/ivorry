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

/**
 * MODELS
 */

use App\Models\Member;
use App\Models\Dentist;
use App\Models\Clinic;
use App\Models\Procedure;

class ReportsPage extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string $view = 'filament.pages.reports-page';
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
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
                                'procedures'    => 'Procedure Status Report',
                                'accounts'      => 'Account Status Report',
                            ])
                            ->required()
                            ->reactive(),

                        Forms\Components\Select::make('status')
                            ->label('Filter by Accrediation Status')
                            ->options([
                                'ACTIVE'   => 'Active',
                                'INACTIVE' => 'Inactive',
                            ])
                            ->placeholder('All')
                            ->visible(fn($get) => $get('reportType') === 'members'),


                        Forms\Components\Select::make('accreditation_status')
                            ->label('Filter by Status')
                            ->options([
                                'ACTIVE'   => 'Active',
                                'INACTIVE' => 'Inactive',
                                'SPECIFIC ACCOUNT' => 'Specific Account',
                                'SILENT' => 'Silent',

                            ])
                            ->placeholder('All')
                            ->visible(fn($get) => $get('reportType') === 'clinics'),

                        Forms\Components\Select::make('procedure_status')
                            ->label('Filter by Procedure Status')
                            ->options([
                                \App\Models\Procedure::STATUS_PENDING   => 'Pending',
                                \App\Models\Procedure::STATUS_COMPLETED => 'Completed',
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

                        Forms\Components\Select::make('account_id')
                            ->label('Select Account')
                            ->options(function () {
                                return \App\Models\Account::pluck('company_name', 'id');
                            })
                            ->searchable()
                            ->placeholder('All Accounts')
                            ->visible(fn($get) => $get('reportType') === 'members'),

                        Forms\Components\Select::make('clinic_id')
                            ->label('Select Clinic')
                            ->options(function () {
                                return \App\Models\Clinic::pluck('clinic_name', 'id');
                            })
                            ->searchable()
                            ->placeholder('All Clinics')
                            ->visible(fn($get) => $get('reportType') === 'dentists'),

                        Forms\Components\Select::make('specialization_id')
                            ->label('Select Specialization')
                            ->options(function () {
                                return \App\Models\Specializations::pluck('name', 'id');
                            })
                            ->searchable()
                            ->placeholder('All Clinics')
                            ->visible(fn($get) => $get('reportType') === 'dentists'),

                        Forms\Components\DatePicker::make('fromDate')->label('From Date'),
                        Forms\Components\DatePicker::make('toDate')->label('To Date'),
                    ]),
                ])
                ->footerActions([
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
                    // ->visible(function () {
                    //     if ($this->hasGenerated) {
                    //         return true;
                    //     }
                    // })
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

        $type = $this->reportFilters['reportType'];

        $query = match ($type) {
            'members'    => $this->membersQuery(),
            'dentists'   => $this->dentistsQuery(),
            'clinics'    => $this->clinicsQuery(),
            'procedures' => $this->proceduresQuery(),
            'accounts'   => $this->accountsQuery(),
            default    => null,
        };

        if (! $query) {
            Notification::make()
                ->title('Invalid Report')
                ->danger()
                ->send();
            return;
        }

        $filename = strtoupper($type) . '_REPORT_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new ReportsExport($query, $type),
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
            ->when($f['account_id'] ?? null, fn($q, $v) => $q->where('account_id', $v))
            ->when(
                !empty($f['fromDate']) && !empty($f['toDate']),
                fn($q) =>
                $q->whereBetween('created_at', [$f['fromDate'], $f['toDate']])
            );
    }

    protected function dentistsQuery(): Builder
    {
        $f = $this->reportFilters;

        return Dentist::query()
            ->with('specializations')
            ->when($f['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->when($f['clinic_id'] ?? null, fn($q, $v) => $q->where('clinic_id', $v))
            ->when(
                !empty($f['fromDate']) && !empty($f['toDate']),
                fn($q) => $q->whereBetween('created_at', [$f['fromDate'], $f['toDate']])
            )
            ->when(
                $f['specialization_id'] ?? null,
                fn($q, $v) => $q->whereHas('specializations', fn($q2) => $q2->where('specializations.id', $v))
            );
    }


    protected function clinicsQuery(): Builder
    {
        $f = $this->reportFilters;

        return Clinic::query()
            ->when($f['accreditation_status'] ?? null, fn($q, $v) => $q->where('accreditation_status', $v))
            ->when(
                !empty($f['fromDate']) && !empty($f['toDate']),
                fn($q) =>
                $q->whereBetween('created_at', [$f['fromDate'], $f['toDate']])
            );
    }

    protected function proceduresQuery(): Builder
    {
        $f = $this->reportFilters;

        return Procedure::query()
            ->when($f['procedure_status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->when(
                !empty($f['fromDate']) && !empty($f['toDate']),
                fn($q) =>
                $q->whereBetween('created_at', [$f['fromDate'], $f['toDate']])
            );
    }

    protected function accountsQuery(): Builder
    {
        $f = $this->reportFilters;

        return Account::query()
            ->when($f['status'] ?? null, fn($q, $v) => $q->where('account_status', $v)) // filter by account_status
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
                Tables\Columns\TextColumn::make('full_name')->label('Member'),
                Tables\Columns\TextColumn::make('member_type')->label('Member Type'),
                Tables\Columns\TextColumn::make('card_number')->label('Card Number'),
                Tables\Columns\TextColumn::make('gender'),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('inactive_date')->label('Inactive Date')->badge(),
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
                Tables\Columns\TextColumn::make('is_branch')->label('Branch'),
                Tables\Columns\TextColumn::make('business_type')->label('Business Type'),
                Tables\Columns\TextColumn::make('vat_type')->label('Vat Type'),
                Tables\Columns\TextColumn::make('witholding_tax')->label('Witholding Tax'),
                Tables\Columns\TextColumn::make('accreditation_status')->badge(),
                Tables\Columns\TextColumn::make('created_at')->date()->label('Date Added'),
            ],

            'procedures' => [
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Member Name')
                    ->getStateUsing(fn($record) => $record->member->first_name . ' ' . $record->member->last_name),

                Tables\Columns\TextColumn::make('clinic.clinic_name')->label('Clinic Name'),
                Tables\Columns\TextColumn::make('service.name')->label('Procedure Name'),
                Tables\Columns\TextColumn::make('units')->label('Units'),
                Tables\Columns\TextColumn::make('applied_fee')
                    ->label('Applied Fee')
                    ->money('php', true),

                Tables\Columns\TextColumn::make('availment_date')->label('Availment Date'),
                Tables\Columns\TextColumn::make('approval_code')->label('Approval Code'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('created_at')->date()->label('Date Added'),
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


    public static function canViewAny(): bool
    {
        return auth()->user()->can('reports.view');
    }
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check()
            && auth()->user()->can('reports.view');
    }
}
