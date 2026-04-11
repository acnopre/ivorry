<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ImportLogResource\Pages;
use App\Filament\Resources\ImportLogResource\RelationManagers;
use App\Models\Account;
use App\Models\AccountService;
use App\Models\ImportLog;
use App\Models\Member;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ImportLogResource\RelationManagers\ImportLogItemsRelationManager;
use Illuminate\Support\Facades\Auth;

class ImportLogResource extends Resource
{
    protected static ?string $model = ImportLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Import Logs';
    protected static ?string $navigationGroup = 'Imports';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        // If user has the base permission but not all type permissions, filter
        $allowedTypes = collect([
            'account' => 'import-logs.view.account',
            'member' => 'import-logs.view.member',
            'clinic' => 'import-logs.view.clinic',
            'procedure' => 'import-logs.view.procedure',
        ])->filter(fn($perm) => $user->can($perm))->keys()->toArray();

        return $query->whereIn('import_type', $allowedTypes);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('filename')->searchable(),
                BadgeColumn::make('batch_status')
                    ->colors([
                        'success' => 'active',
                        'danger'  => 'deleted',
                    ])
                    ->formatStateUsing(fn($state) => ucfirst($state))
                    ->visible(fn() => true),
                TextColumn::make('user.name')->label('Imported By')->searchable(),
                BadgeColumn::make('status')
                    ->colors([
                        'primary' => 'processing',
                        'success' => 'signed',
                        'warning' => 'partial',
                        'danger'  => 'failed',
                    ]),
                TextColumn::make('total_rows'),
                TextColumn::make('success_rows')->color('success'),
                TextColumn::make('updated_rows')->color('info'),
                TextColumn::make('duplicate_rows')->color('warning'),
                TextColumn::make('skipped_rows')->color('warning'),
                TextColumn::make('error_rows')->color('danger'),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->visible(auth()->user()->can('import-logs.details.view')),
                Tables\Actions\Action::make('deleteBatch')
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
                Tables\Actions\Action::make('restoreBatch')
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
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            ImportLogItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListImportLogs::route('/'),
            'view'  => Pages\ViewImportLog::route('/{record}'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check()
            && auth()->user()->can('import-logs.view');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('import-logs.view') ?? false;
    }
}
