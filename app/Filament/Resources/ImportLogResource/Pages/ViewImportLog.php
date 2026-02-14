<?php

namespace App\Filament\Resources\ImportLogResource\Pages;

use App\Filament\Resources\ImportLogResource;
use App\Filament\Widgets\ImportLogStats;
use App\Models\ImportLog;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ImportLogDetailsExport;

class ViewImportLog extends ViewRecord
{
    protected static string $resource = ImportLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('exportDetails')
                ->label('Export Details')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn(ImportLog $record) => Excel::download(
                    new ImportLogDetailsExport($record->id),
                    'import-log-' . $record->id . '-details.xlsx'
                )),

            Actions\Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn(ImportLog $record) => $record->status === 'processing')
                ->action(fn() => $this->refresh()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ImportLogStats::class,
        ];
    }

    protected function retryFailedRows(ImportLog $record): void
    {
        $failedRows = $record->items()
            ->where('status', 'error')
            ->get();

        foreach ($failedRows as $item) {
            // try {
            //     DB::transaction(function () use ($item, $record) {

            //         // 🔁 Shared row logic
            //         app(\App\Services\AccountRowProcessor::class)
            //             ->handle($item->raw_data);

            //         $item->update([
            //             'status'  => 'success',
            //             'message' => null,
            //         ]);

            //         $record->increment('success_rows');
            //         $record->decrement('error_rows');
            //     });
            // } catch (\Throwable $e) {
            //     $item->update([
            //         'message' => $e->getMessage(),
            //     ]);
            // }
        }

        $record->update([
            'status' => $record->error_rows > 0 ? 'partial' : 'completed',
        ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('import-logs.details.view');
    }
}
