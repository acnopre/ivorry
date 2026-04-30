<?php

namespace App\Filament\Pages;

use App\Support\ImportTemplates;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Illuminate\Support\Facades\Response;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ImportDocumentation extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Import Documentation';
    protected static ?string $navigationGroup = 'Help & Documentation';
    protected static ?int $navigationSort = 99;
    protected static string $view = 'filament.pages.import-documentation';

    public static function canAccess(): bool
    {
        return auth()->user()->can('documentation.view')
            || auth()->user()->hasRole(\App\Models\Role::ACCREDITATION);
    }

    public function exportPdf(): mixed
    {
        $pdf = Pdf::loadView('pdf.import-documentation', [
            'generatedAt' => now()->format('F d, Y h:i A'),
        ])->setPaper('a4', 'portrait');

        return response()->streamDownload(
            fn() => print($pdf->output()),
            'import-documentation-' . now()->format('Y-m-d') . '.pdf'
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->action('exportPdf'),

            ActionGroup::make([
                Action::make('downloadDocs')
                    ->label('Documentation (.md)')
                    ->icon('heroicon-o-document-text')
                    ->action(fn() => $this->downloadFile('docs')),

                Action::make('downloadAccount')
                    ->label('Account Template')
                    ->icon('heroicon-o-table-cells')
                    ->action(fn() => $this->downloadTemplate('account')),

                Action::make('downloadMember')
                    ->label('Member Template')
                    ->icon('heroicon-o-table-cells')
                    ->action(fn() => $this->downloadTemplate('member')),

                Action::make('downloadProcedure')
                    ->label('Procedure Template')
                    ->icon('heroicon-o-table-cells')
                    ->action(fn() => $this->downloadFile('procedure')),

                Action::make('downloadClinic')
                    ->label('Clinic Template')
                    ->icon('heroicon-o-table-cells')
                    ->action(fn() => $this->downloadTemplate('clinic')),

                Action::make('downloadClinicBarangay')
                    ->label('Download Import Clinic with Barangay')
                    ->icon('heroicon-o-table-cells')
                    ->action(fn() => $this->downloadFile('clinic_barangay')),
            ])
            ->label('Download Templates')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->button(),
        ];
    }

    public function downloadTemplate(string $type): mixed
    {
        if ($type === 'clinic') {
            return (new \App\Exports\ClinicImportTemplateExport())->download();
        }

        $row = match ($type) {
            'account' => ImportTemplates::account(),
            'member'  => ImportTemplates::member(),
            'clinic'  => ImportTemplates::clinic(),
            default   => null,
        };

        if (!$row) return null;

        $export = new class($row) implements FromArray, WithHeadings {
            public function __construct(private array $row) {}
            public function array(): array { return [array_values($this->row)]; }
            public function headings(): array { return array_keys($this->row); }
        };

        return Excel::download($export, 'import-' . $type . '-template.xlsx');
    }

    public function downloadFile(string $type)
    {
        $files = [
            'docs' => [
                'path' => base_path('IMPORT_DOCUMENTATION.md'),
                'name' => 'HPDAI_Import_Documentation.md',
            ],
            'procedure' => [
                'path' => storage_path('app/templates/import-procedure-template.xlsx'),
                'name' => 'import-procedure-template.xlsx',
            ],
            'clinic_barangay' => [
                'path' => public_path('templates/import_clinic_with_barangay.zip'),
                'name' => 'import_clinic_with_barangay.zip',
            ],
        ];

        if (!isset($files[$type]) || !file_exists($files[$type]['path'])) {
            return;
        }

        return Response::download($files[$type]['path'], $files[$type]['name']);
    }
}
