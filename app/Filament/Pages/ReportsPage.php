<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Notifications\Notification;
use App\Models\User;
use App\Models\Clinic;
use App\Models\Claim;
use App\Models\Procedure;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportsExport;
use App\Models\Member;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportsPage extends Page implements Forms\Contracts\HasForms, Tables\Contracts\HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static string $view = 'filament.pages.reports-page';
    protected static ?string $navigationLabel = 'Reports & Analytics';
    protected static ?string $title = 'Reports Generation';
    protected static ?string $navigationGroup = 'Reports';

    // Store form data here
    public ?array $reportFilters = [];
    public bool $hasSearched = false;

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Search Reports')
                ->schema([
                    Forms\Components\Grid::make(4)->schema([
                        Forms\Components\Select::make('reportType')
                            ->label('Select Report Type')
                            ->options([
                                'members' => 'Member Enrollment Report - Status',
                                'dentists' => 'Dentist Specialist Enrollment Report - Status',
                                'clinics' => 'Clinics Enrollment Report - Status',
                                'claims' => 'Claims Reports - Approved / Denied',
                                'soa' => 'List of SOA - Generate Statements List',
                                'csr' => 'CSR Reports - Approved / Denied Procedures',
                            ])
                            ->required()
                            ->reactive(),

                        Forms\Components\Select::make('status')
                            ->label('Filter by Status')
                            ->options([
                                'ACTIVE' => 'Active',
                                'INACTIVE' => 'Inactive',
                            ])
                            ->placeholder('All'),

                        Forms\Components\Select::make('memberType')
                            ->label('Member Type')
                            ->options([
                                'PRINCIPAL' => 'Principal',
                                'DEPENDENT' => 'Dependent',
                            ])
                            ->visible(fn($get) => $get('reportType') === 'members'),

                        Forms\Components\DatePicker::make('fromDate')
                            ->label('From Date'),

                        Forms\Components\DatePicker::make('toDate')
                            ->label('To Date'),
                    ]),
                ])
                ->footerActions([
                    Forms\Components\Actions\Action::make('generate')
                        ->label('Generate Report')
                        ->icon('heroicon-o-magnifying-glass')
                        ->color('primary')
                        ->action('search'),
                ]),
        ];
    }

    protected function getFormModel(): string
    {
        return Procedure::class; // optional placeholder
    }

    protected function getFormStatePath(): ?string
    {
        return 'reportFilters';
    }

    public function mount(): void
    {
        $this->form->fill(); // ensure form state is initialized
    }

    public function search(): void
    {
        $formData = $this->form->getState();

        // Only check fields that matter for input
        $fieldsToCheck = ['reportType', 'status', 'memberType', 'fromDate', 'toDate'];

        $hasInput = collect($formData)
            ->only($fieldsToCheck)
            ->filter(fn($value) => !empty($value))
            ->isNotEmpty();

        if (! $hasInput) {
            Notification::make()
                ->title('No Filters Applied')
                ->body('Please enter at least one search filter before generating the report.')
                ->warning()
                ->send();

            $this->hasSearched = false;
            return;
        }

        // Save form state and flag search
        $this->reportFilters = $formData;
        $this->hasSearched = true;

        // Refresh table
        $this->dispatch('$refresh');
    }

    protected function getTableQuery()
    {
        if (empty($this->reportFilters['reportType'] ?? null)) {
            return User::query()->whereRaw('1 = 0');
        }

        $filters = $this->reportFilters;

        $query = match ($filters['reportType']) {
            'members' => User::query()
                ->whereHas('roles', fn($q) => $q->where('name', 'Member')),

            'dentists' => User::query()
                ->whereHas('roles', fn($q) => $q->where('name', 'Dentist')),

            'clinics' => Clinic::query(),

            'claims' => Claim::query(),

            default => User::query()->whereRaw('1 = 0'),
        };

        /**
         * ✅ STATUS FILTER (RELATIONAL)
         */
        if (!empty($filters['status'])) {

            match ($filters['reportType']) {
                // User has status directly
                'members' => $query->whereHas(
                    'member',
                    fn($q) =>
                    $q->where('status', $filters['status'])->where('member_type', $filters['member_type'])
                ),

                // Clinic → member relation
                'clinics' =>
                $query->whereHas(
                    'member',
                    fn($q) =>
                    $q->where('status', $filters['status'])
                ),

                // Claim → member relation
                'claims' =>
                $query->whereHas(
                    'member',
                    fn($q) =>
                    $q->where('status', $filters['status'])
                ),

                default => null,
            };
        }

        /**
         * ✅ DATE FILTER
         */
        if (!empty($filters['fromDate']) && !empty($filters['toDate'])) {
            $query->whereBetween('created_at', [
                Carbon::parse($filters['fromDate'])->startOfDay(),
                Carbon::parse($filters['toDate'])->endOfDay(),
            ]);
        }

        return $query;
    }


    protected function getTableColumns(): array
    {
        return match ($this->reportFilters['reportType'] ?? null) {
            'members' => [
                Tables\Columns\TextColumn::make('name')->label('Member Name'),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('member_type'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('created_at')->date(),
            ],
            'dentists' => [
                Tables\Columns\TextColumn::make('name')->label('Dentist Name'),
                Tables\Columns\TextColumn::make('specialization'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('created_at')->date(),
            ],
            'clinics' => [
                Tables\Columns\TextColumn::make('clinic_name'),
                Tables\Columns\TextColumn::make('registered_name'),
                Tables\Columns\TextColumn::make('accreditation_status')->badge(),
                Tables\Columns\TextColumn::make('created_at')->date(),
            ],
            'claims' => [
                Tables\Columns\TextColumn::make('claim_number'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('amount')->money('PHP'),
                Tables\Columns\TextColumn::make('created_at')->date(),
            ],
            default => [],
        };
    }

    protected function getTableHeaderActions(): array
    {
        return [
            Tables\Actions\Action::make('exportExcel')
                ->label('Export to Excel')
                ->action(function () {
                    $filters = $this->reportFilters;
                    $filename = ucfirst($filters['reportType'] ?? 'report') . '_Report_' . now()->format('Ymd_His') . '.xlsx';
                    return Excel::download(new ReportsExport(...array_values($filters)), $filename);
                }),

            Tables\Actions\Action::make('exportPdf')
                ->label('Export to PDF')
                ->action(function () {
                    $filters = $this->reportFilters;
                    $data = (new ReportsExport(...array_values($filters)))->collection();
                    $pdf = Pdf::loadView('pdf.report', [
                        'reportType' => ucfirst($filters['reportType'] ?? ''),
                        'data' => $data,
                        'fromDate' => $filters['fromDate'] ?? null,
                        'toDate' => $filters['toDate'] ?? null,
                    ]);

                    $filename = ucfirst($filters['reportType'] ?? 'report') . '_Report_' . now()->format('Ymd_His') . '.pdf';
                    return response()->streamDownload(fn() => print($pdf->output()), $filename);
                }),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check()
            && auth()->user()->hasAnyRole(['Super Admin', 'Upper Management']);
    }
}
