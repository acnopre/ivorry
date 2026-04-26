<?php

namespace App\Filament\Resources\MemberResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ProceduresRelationManager extends RelationManager
{
    protected static string $relationship = 'procedures';

    public function table(Table $table): Table
    {
        $isCSR   = Auth::user()->hasRole('CSR');

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

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn($state) => match ($state) {
                        'pending'   => 'Pending',
                        'signed'    => 'Signed',
                        'valid'     => 'Valid',
                        'invalid'   => 'Rejected',
                        'returned'  => 'Returned',
                        'processed' => 'Processed',
                        'cancelled' => 'Cancelled',
                        default     => ucfirst($state),
                    })
                    ->color(fn($state) => match ($state) {
                        'pending'   => 'warning',
                        'signed'    => 'info',
                        'valid'     => 'success',
                        'invalid'   => 'danger',
                        'returned'  => 'gray',
                        'processed' => 'primary',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    }),

                Tables\Columns\TextColumn::make('units.name')
                    ->label('Units')
                    ->formatStateUsing(fn($record) => $record->units->pluck('name')->join(', '))
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('applied_fee')
                    ->label('Fee')
                    ->visible($isCSR)
                    ->money('PHP'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
