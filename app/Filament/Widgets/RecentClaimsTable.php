<?php

namespace App\Filament\Widgets;

use App\Models\Procedure;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RecentClaimsTable extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Recent Procedures / Claims';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Procedure::query()
                    ->with(['member', 'clinic', 'service'])
                    ->latest('availment_date')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('member.name')
                    ->label('Member')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('clinic.clinic_name')
                    ->label('Clinic')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('dentist_name')
                    ->label('Dentist')
                    ->getStateUsing(fn ($record) => $record->dentist_name)
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => fn ($state) => in_array($state, ['approved', 'completed']),
                        'warning' => fn ($state) => in_array($state, ['pending', 'for approval']),
                        'danger'  => fn ($state) => in_array($state, ['denied', 'cancelled']),
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => fn ($state) => in_array($state, ['approved', 'completed']),
                        'heroicon-o-clock' => fn ($state) => in_array($state, ['pending', 'for approval']),
                        'heroicon-o-x-circle' => fn ($state) => in_array($state, ['denied', 'cancelled']),
                    ])
                    ->sortable()
                    ->label('Status'),

                Tables\Columns\TextColumn::make('approval_code')
                    ->label('Approval Code')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('availment_date')
                    ->label('Availment Date')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('remarks')
                    ->label('Remarks')
                    ->limit(30)
                    ->wrap()
                    ->toggleable(),
            ])
            ->defaultSort('availment_date', 'desc');
    }
}
