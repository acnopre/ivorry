<?php

namespace App\Filament\Resources\MemberResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProceduresRelationManager extends RelationManager
{
    protected static string $relationship = 'procedures';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('clinic.clinic_name')
                    ->label('Clinic')
                    ->searchable(),

                Tables\Columns\TextColumn::make('availment_date')
                    ->label('Availment Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('approval_code')
                    ->label('Approval Code')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'signed',
                        'success' => 'valid',
                        'danger' => 'invalid',
                        'secondary' => 'returned',
                        'primary' => 'processed',
                    ]),

                Tables\Columns\TextColumn::make('applied_fee')
                    ->label('Fee')
                    ->money('PHP'),

                Tables\Columns\TextColumn::make('units.name')
                    ->label('Units')
                    ->formatStateUsing(fn($record) => $record->units->pluck('name')->join(', '))
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
