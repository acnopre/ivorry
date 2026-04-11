<?php

namespace App\Filament\Resources\ImportLogResource\Pages;

use App\Filament\Resources\ImportLogResource;
use App\Imports\ProcedureImport;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class ListImportLogs extends ListRecords
{
    protected static string $resource = ImportLogResource::class;

    public function getTabs(): array
    {
        $tabs = [];

        if (auth()->user()->can('import-logs.view.account')) {
            $tabs['account'] = Tab::make('Account')
                ->icon('heroicon-o-building-office')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('import_type', 'account'));
        }

        if (auth()->user()->can('import-logs.view.member')) {
            $tabs['member'] = Tab::make('Member')
                ->icon('heroicon-o-users')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('import_type', 'member'));
        }

        if (auth()->user()->can('import-logs.view.clinic')) {
            $tabs['clinic'] = Tab::make('Clinic')
                ->icon('heroicon-o-building-storefront')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('import_type', 'clinic'));
        }

        if (auth()->user()->can('import-logs.view.procedure')) {
            $tabs['procedure'] = Tab::make('Procedure')
                ->icon('heroicon-o-clipboard-document-list')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('import_type', 'procedure'));
        }

        return $tabs;
    }

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
