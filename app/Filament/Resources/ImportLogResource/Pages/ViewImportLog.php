<?php

namespace App\Filament\Resources\ImportLogResource\Pages;

use App\Filament\Resources\ImportLogResource;
use App\Filament\Widgets\ImportLogStats;
use App\Models\Account;
use App\Models\AccountService;
use App\Models\ImportLog;
use App\Models\Member;
use Filament\Actions;
use Filament\Notifications\Notification;
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
            Actions\Action::make('deleteBatch')
                ->label('Delete Batch')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Delete Import Batch')
                ->modalDescription('This will soft delete all accounts/members created from this import. They can be restored later.')
                ->visible(fn(ImportLog $record) => auth()->user()->can('import.batch.delete') && in_array($record->import_type, ['account', 'member']))
                ->action(function (ImportLog $record) {
                    if ($record->import_type === 'account') {
                        $accountIds = Account::where('import_id', $record->id)->pluck('id');
                        AccountService::whereIn('account_id', $accountIds)->delete();
                        Account::where('import_id', $record->id)->delete();
                    } else {
                        Member::where('import_id', $record->id)->delete();
                    }

                    $record->update(['batch_status' => 'deleted']);

                    Notification::make()
                        ->title('Import batch deleted')
                        ->body('All records from this import have been soft deleted.')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('restoreBatch')
                ->label('Restore Batch')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Restore Import Batch')
                ->modalDescription('This will restore all soft deleted accounts/members from this import.')
                ->visible(fn(ImportLog $record) => auth()->user()->can('import.batch.restore') &&
                    in_array($record->import_type, ['account', 'member']) &&
                    match ($record->import_type) {
                        'account' => Account::withTrashed()->where('import_id', $record->id)->whereNotNull('deleted_at')->exists(),
                        'member'  => Member::withTrashed()->where('import_id', $record->id)->whereNotNull('deleted_at')->exists(),
                    }
                )
                ->action(function (ImportLog $record) {
                    if ($record->import_type === 'account') {
                        $accountIds = Account::withTrashed()->where('import_id', $record->id)->pluck('id');
                        AccountService::withTrashed()->whereIn('account_id', $accountIds)->restore();
                        Account::withTrashed()->where('import_id', $record->id)->restore();
                    } else {
                        Member::withTrashed()->where('import_id', $record->id)->restore();
                    }

                    $record->update(['batch_status' => 'active']);

                    Notification::make()
                        ->title('Import batch restored')
                        ->body('All records from this import have been restored.')
                        ->success()
                        ->send();
                }),

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
                ->action(fn() => $this->redirect($this->getUrl())),
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
