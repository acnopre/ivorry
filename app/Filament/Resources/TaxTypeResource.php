<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaxTypeResource\Pages;
use App\Models\Role;
use App\Models\TaxType;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;


class TaxTypeResource extends Resource
{
    protected static ?string $model = TaxType::class;
    public static ?string $navigationGroup = 'Lookup Tables';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?int $navigationSort = 5;

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
            'index' => Pages\ListTaxTypes::route('/'),
        ];
    }
}
