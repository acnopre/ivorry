<?php

namespace App\Filament\Resources\ImportLogResource\Pages;

use App\Filament\Resources\ImportLogResource;
use App\Imports\ProcedureImport;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListImportLogs extends ListRecords
{
    protected static string $resource = ImportLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('importProcedures')
                ->label('Import Procedures')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->visible(fn() => auth()->user()->can('procedure.import'))
                ->form([
                    FileUpload::make('file')
                        ->label('Excel File')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                        ->required(),
                    Toggle::make('migration_mode')
                        ->label('Migration Mode (Auto-process & deduct)')
                        ->helperText('Enable this only for initial data migration. Procedures will be set to PROCESSED and deductions will be applied.')
                        ->default(false)
                        ->visible(fn() => auth()->user()->can('procedure.import.migration-mode')),
                ])
                ->action(function (array $data) {
                    $file = storage_path('app/public/' . $data['file']);
                    $migrationMode = $data['migration_mode'] ?? false;
                    $import = new ProcedureImport(basename($data['file']), $migrationMode);
                    
                    Excel::import($import, $file);
                    
                    if (count($import->failed) > 0) {
                        Notification::make()
                            ->title('Import completed with errors')
                            ->body("{$import->imported} procedures imported, " . count($import->failed) . " failed")
                            ->warning()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Import successful')
                            ->body("{$import->imported} procedures imported")
                            ->success()
                            ->send();
                    }
                }),
        ];
    }
}
