<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UnitResource\Pages;
use App\Models\Role;
use App\Models\Unit;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;

class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;
    protected static ?string $navigationIcon = 'heroicon-o-cube-transparent';
    public static ?string $navigationGroup = 'Lookup Tables';

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
            Forms\Components\Select::make('unit_type_id')
                ->relationship('unitType', 'name')
                ->label('Unit Type')
                ->required()
                ->searchable(),
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(50),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('unitType.name')
                    ->label('Unit Type')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Unit Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->can('lookup_tables.view');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUnits::route('/'),
            'create' => Pages\CreateUnit::route('/create'),
            'edit' => Pages\EditUnit::route('/{record}/edit'),
        ];
    }
}
