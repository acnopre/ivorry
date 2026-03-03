<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Response;

class ImportDocumentation extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Import Documentation';
    protected static ?string $navigationGroup = 'Help & Documentation';
    protected static ?int $navigationSort = 99;
    protected static string $view = 'filament.pages.import-documentation';

    public static function canAccess(): bool
    {
        return auth()->user()->can('documentation.view');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadDocs')
                ->label('Download Documentation')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->action(fn() => $this->downloadFile('docs')),
            
            Action::make('downloadAccount')
                ->label('Account Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->action(fn() => $this->downloadFile('account')),
            
            Action::make('downloadMember')
                ->label('Member Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn() => $this->downloadFile('member')),
            
            Action::make('downloadProcedure')
                ->label('Procedure Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->action(fn() => $this->downloadFile('procedure')),
            
            Action::make('downloadClinic')
                ->label('Clinic Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn() => $this->downloadFile('clinic')),
        ];
    }

    public function downloadFile(string $type)
    {
        $files = [
            'docs' => [
                'path' => base_path('IMPORT_DOCUMENTATION.md'),
                'name' => 'HPDAI_Import_Documentation.md'
            ],
            'account' => [
                'path' => storage_path('app/templates/import-account-template.xlsx'),
                'name' => 'import-account-template.xlsx'
            ],
            'member' => [
                'path' => storage_path('app/templates/import-member-template.xlsx'),
                'name' => 'import-member-template.xlsx'
            ],
            'procedure' => [
                'path' => storage_path('app/templates/import-procedure-template.xlsx'),
                'name' => 'import-procedure-template.xlsx'
            ],
            'clinic' => [
                'path' => storage_path('app/templates/clinic_import_template.xls'),
                'name' => 'clinic_import_template.xls'
            ],
        ];

        if (!isset($files[$type]) || !file_exists($files[$type]['path'])) {
            return;
        }

        return Response::download($files[$type]['path'], $files[$type]['name']);
    }
}
