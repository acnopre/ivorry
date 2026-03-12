<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UpdateInfo1903TypeResource\Pages;
use App\Filament\Resources\UpdateInfo1903TypeResource\RelationManagers;
use App\Models\Role;
use App\Models\UpdateInfo1903Type;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UpdateInfo1903TypeResource extends Resource
{
    protected static ?string $model = UpdateInfo1903Type::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    public static ?string $navigationGroup = 'Lookup Tables';
    protected static ?int $navigationSort = 6;

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->can('lookup_tables.view');
    }

    public static function canCreate(): bool
    {
        return auth()->check() && auth()->user()->can('lookup_tables.create');
    }

    public static function canEdit($record): bool
    {
        return auth()->check() && auth()->user()->can('lookup_tables.edit');
    }

    public static function canDelete($record): bool
    {
        return auth()->check() && auth()->user()->can('lookup_tables.delete');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->unique(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->searchable(),
            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->label('Created'),
            Tables\Columns\TextColumn::make('updated_at')
                ->dateTime()
                ->label('Updated'),
        ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->can('lookup_tables.view');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUpdateInfo1903Types::route('/'),
            'create' => Pages\CreateUpdateInfo1903Type::route('/create'),
            'edit' => Pages\EditUpdateInfo1903Type::route('/{record}/edit'),
        ];
    }
}
