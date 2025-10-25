<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Tables;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportsExport;
use App\Exports\ReportsPdfExport;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use App\Models\User;
use App\Models\Clinic;
use App\Models\Claim;
use App\Models\Procedure;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportsPage extends Page implements Forms\Contracts\HasForms, Tables\Contracts\HasTable
{
    use Forms\Concerns\InteractsWithForms;
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static string $view = 'filament.pages.reports-page';
    protected static ?string $navigationLabel = 'Reports & Analytics';
    protected static ?string $title = 'Reports Generation';
    protected static ?string $navigationGroup = 'Reports';


    public ?string $reportType = null;
    public ?string $status = null;
    public ?string $fromDate = null;
    public ?string $toDate = null;

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Grid::make(3)
                ->schema([
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
                            'APPROVED' => 'Approved',
                            'DENIED' => 'Denied',
                        ])
                        ->placeholder('All'),

                    Forms\Components\DatePicker::make('fromDate')
                        ->label('From Date')
                        ->reactive(),

                    Forms\Components\DatePicker::make('toDate')
                        ->label('To Date')
                        ->reactive(),
                ]),
        ];
    }

    protected function getTableQuery()
    {
        $query = match ($this->reportType) {
            'members' => User::query()->whereHas('roles', fn($q) => $q->where('name', 'Member')),
            'dentists' => User::query()->whereHas('roles', fn($q) => $q->where('name', 'Dentist')),
            'clinics' => Clinic::query(),
            'claims' => Claim::query(),
            'soa' => DB::table('statements'),
            'csr' => Procedure::query(),
            default => User::query()->whereNull('id'),
        };

        if ($this->status) {
            $query->where('status', $this->status)
                ->orWhere('accreditation_status', $this->status)
                ->orWhere('approval_status', $this->status);
        }

        if ($this->fromDate && $this->toDate) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->fromDate)->startOfDay(),
                Carbon::parse($this->toDate)->endOfDay(),
            ]);
        }

        return $query;
    }

    protected function getTableColumns(): array
    {
        return match ($this->reportType) {
            'members' => [
                Tables\Columns\TextColumn::make('name')->label('Member Name'),
                Tables\Columns\TextColumn::make('email'),
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
            'soa' => [
                Tables\Columns\TextColumn::make('statement_number'),
                Tables\Columns\TextColumn::make('total_amount')->money('PHP'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('created_at')->date(),
            ],
            'csr' => [
                Tables\Columns\TextColumn::make('procedure_name'),
                Tables\Columns\TextColumn::make('status')->badge(),
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
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    if (!$this->reportType) {
                        Notification::make()
                            ->title('Please select a report type first.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $filename = ucfirst($this->reportType) . '_Report_' . now()->format('Ymd_His') . '.xlsx';
                    return Excel::download(new ReportsExport($this->reportType, $this->status, $this->fromDate, $this->toDate), $filename);
                }),

            Tables\Actions\Action::make('exportPdf')
                ->label('Export to PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action(function () {
                    if (!$this->reportType) {
                        Notification::make()
                            ->title('Please select a report type first.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $data = (new ReportsExport($this->reportType, $this->status, $this->fromDate, $this->toDate))->collection();

                    $pdf = Pdf::loadView('pdf.report', [
                        'reportType' => ucfirst($this->reportType),
                        'data' => $data,
                        'fromDate' => $this->fromDate,
                        'toDate' => $this->toDate,
                    ]);

                    $filename = ucfirst($this->reportType) . '_Report_' . now()->format('Ymd_His') . '.pdf';
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
