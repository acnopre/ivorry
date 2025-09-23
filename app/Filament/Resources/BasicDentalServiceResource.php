<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BasicDentalServiceResource\Pages;
use App\Filament\Resources\BasicDentalServiceResource\RelationManagers;
use App\Models\BasicDentalService;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BasicDentalServiceResource extends Resource
{
    protected static ?string $model = BasicDentalService::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Service Name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Created'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->label('Updated'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
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
            'index' => Pages\ListBasicDentalServices::route('/'),
            'create' => Pages\CreateBasicDentalService::route('/create'),
            'edit' => Pages\EditBasicDentalService::route('/{record}/edit'),
        ];
    }
}
