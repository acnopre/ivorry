<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ImportLogResource\Pages;
use App\Filament\Resources\ImportLogResource\RelationManagers;
use App\Models\ImportLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ImportLogResource\RelationManagers\ImportLogItemsRelationManager;

class ImportLogResource extends Resource
{
    protected static ?string $model = ImportLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Import Logs';
    protected static ?string $navigationGroup = 'Imports';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('filename')->searchable(),
                TextColumn::make('user.name')->label('Imported By')->searchable(),
                BadgeColumn::make('status')
                    ->colors([
                        'primary' => 'processing',
                        'success' => 'sign',
                        'warning' => 'partial',
                        'danger'  => 'failed',
                    ]),
                TextColumn::make('total_rows'),
                TextColumn::make('success_rows')->color('success'),
                TextColumn::make('skipped_rows')->color('warning'),
                TextColumn::make('error_rows')->color('danger'),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->visible(auth()->user()->can('import-logs.details.view')),
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
        return auth()->user()->can('import-logs.view');
    }
}
