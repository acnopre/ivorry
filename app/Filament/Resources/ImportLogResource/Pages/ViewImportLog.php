<?php

namespace App\Filament\Resources\ImportLogResource\Pages;

use App\Filament\Resources\ImportLogResource;
use App\Filament\Widgets\ImportLogStats;
use App\Models\ImportLog;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;

class ViewImportLog extends ViewRecord
{
    protected static string $resource = ImportLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\Action::make('retryFailed')
            //     ->label('Retry Failed Rows')
            //     ->icon('heroicon-o-arrow-path')
            //     ->color('warning')
            //     ->requiresConfirmation()
            //     ->visible(fn(ImportLog $record) => $record->error_rows > 0)
            //     ->action(fn(ImportLog $record) => $this->retryFailedRows($record)),

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
