<?php

namespace App\Filament\Resources\ImportLogResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ImportLogItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('row_number')
                    ->label('Row'),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'success',
                        'danger'  => 'error',
                    ]),

                Tables\Columns\TextColumn::make('message')
                    ->limit(50)
                    ->wrap()
                    ->toggleable(),
            ]);
    }
}
