<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaxTypeResource\Pages;
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
        return auth()->check()
            && auth()->user()->hasAnyRole(['Super Admin', 'Upper Management']);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTaxTypes::route('/'),
        ];
    }
}
