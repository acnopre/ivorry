<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DentalPlanBenefitResource\Pages;
use App\Models\DentalPlanBenefit;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DentalPlanBenefitResource extends Resource
{
    protected static ?string $model = DentalPlanBenefit::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Dental Plans';
    protected static ?string $navigationLabel = 'Dental Plan Benefits';
    protected static ?string $pluralModelLabel = 'Dental Plan Benefits';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('service_name')
                    ->label('Service Name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('unit')
                    ->label('Unit')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('limits')
                    ->label('Limits')
                    ->wrap(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Category')
                    ->options([
                        'BASIC DENTAL SERVICES' => 'Basic Dental Services',
                        'PLAN ENHANCEMENTS' => 'Plan Enhancements',
                    ]),
            ])
            ->defaultSort('category')
            ->searchPlaceholder('Search benefits...')
            ->striped(); // adds zebra rows
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDentalPlanBenefits::route('/'),
        ];
    }
}
