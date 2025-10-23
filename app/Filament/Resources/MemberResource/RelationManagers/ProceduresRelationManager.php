<?php

namespace App\Filament\Resources\MemberResource\RelationManagers;

use App\Models\BasicDentalService;
use App\Models\PlanEnhancement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\{DatePicker, Repeater, Select, TextInput};

class ProceduresRelationManager extends RelationManager
{
    protected static string $relationship = 'procedures';

    public function form(Form $form): Form
    {
        return $form
            ->schema([

                // ---------------------------
                // Basic Dental Services
                // ---------------------------
                Forms\Components\Section::make('Basic Dental Services')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Repeater::make('basicDentalServices')
                            ->relationship('basicDentalServices')
                            ->defaultItems(0)
                            ->createItemButtonLabel('Add Basic Dental Service')
                            ->schema([
                                Select::make('basic_dental_service_id')
                                    ->label('Basic Dental Service')
                                    ->options(
                                        \App\Models\BasicDentalService::pluck('name', 'id')->toArray()
                                    )
                                    ->searchable()
                                    ->required(),

                                TextInput::make('tooth_number')
                                    ->label('Tooth #')
                                    ->maxLength(10),

                                TextInput::make('extract_number')
                                    ->label('Extract #')
                                    ->maxLength(10),
                            ])
                            ->columns(3),
                    ]),

                // ---------------------------
                // Plan Enhancements
                // ---------------------------
                Forms\Components\Section::make('Plan Enhancements')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Repeater::make('planEnhancements')
                            ->relationship('planEnhancements')
                            ->defaultItems(0)
                            ->createItemButtonLabel('Add Plan Enhancement')
                            ->schema([
                                Select::make('plan_enhancement_id')
                                    ->label('Plan Enhancement')
                                    ->options(
                                        \App\Models\PlanEnhancement::pluck('name', 'id')->toArray()
                                    )
                                    ->searchable()
                                    ->required(),

                                TextInput::make('tooth_number')
                                    ->label('Tooth #')
                                    ->maxLength(10),

                                TextInput::make('extract_number')
                                    ->label('Extract #')
                                    ->maxLength(10),
                            ])
                            ->columns(3),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                // Basic Dental Services
                Tables\Columns\TextColumn::make('basicDentalServices.name')
                    ->label('Basic Dental Service')
                    ->formatStateUsing(
                        fn($state, $record) =>
                        $record->basicDentalServices->pluck('name')->implode('<br>')
                    )
                    ->html()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('basicDentalServices.pivot.tooth_number')
                    ->label('Tooth #')
                    ->formatStateUsing(
                        fn($state, $record) =>
                        $record->basicDentalServices->pluck('pivot.tooth_number')->implode('<br>')
                    )
                    ->html(),

                Tables\Columns\TextColumn::make('basicDentalServices.pivot.extract_number')
                    ->label('Extract #')
                    ->formatStateUsing(
                        fn($state, $record) =>
                        $record->basicDentalServices->pluck('pivot.extract_number')->implode('<br>')
                    )
                    ->html(),

                // Plan Enhancements
                Tables\Columns\TextColumn::make('planEnhancements.name')
                    ->label('Plan Enhancement')
                    ->formatStateUsing(
                        fn($state, $record) =>
                        $record->planEnhancements->pluck('name')->implode('<br>')
                    )
                    ->html()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('planEnhancements.pivot.tooth_number')
                    ->label('Tooth #')
                    ->formatStateUsing(
                        fn($state, $record) =>
                        $record->planEnhancements->pluck('pivot.tooth_number')->implode('<br>')
                    )
                    ->html(),

                Tables\Columns\TextColumn::make('planEnhancements.pivot.extract_number')
                    ->label('Extract #')
                    ->formatStateUsing(
                        fn($state, $record) =>
                        $record->planEnhancements->pluck('pivot.extract_number')->implode('<br>')
                    )
                    ->html(),

                // Procedure Date
                Tables\Columns\TextColumn::make('procedure_date')
                    ->label('Procedure Date')
                    ->date()
                    ->sortable()
                    ->default('-'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
