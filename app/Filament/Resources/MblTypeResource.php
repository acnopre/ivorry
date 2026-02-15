<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MblTypeResource\Pages;
use App\Models\MblType;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MblTypeResource extends Resource
{
    protected static ?string $model = MblType::class;
    public static ?string $navigationGroup = 'Lookup Tables';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?int $navigationSort = 6;

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
            && auth()->user()->hasAnyRole([Role::SUPER_ADMIN, Role::UPPER_MANAGEMENT]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMblTypes::route('/'),
        ];
    }
}
