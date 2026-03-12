<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BusinessTypeResource\Pages;
use App\Models\BusinessType;
use App\Models\Role;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;


class BusinessTypeResource extends Resource
{
    protected static ?string $model = BusinessType::class;
    public static ?string $navigationGroup = 'Lookup Tables';
    public static ?string $navigationIcon = 'heroicon-o-building-office';

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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBusinessTypes::route('/'),
        ];
    }
}
