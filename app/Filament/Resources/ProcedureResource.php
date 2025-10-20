<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProcedureResource\Pages;
use App\Models\Procedure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProcedureResource extends Resource
{
    protected static ?string $model = Procedure::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationGroup = 'Dentist';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $user = auth()->user();

        if ($user && $user->clinic) {
            $clinicId = $user->clinic->id;

            return parent::getEloquentQuery()
                ->whereHas('service', fn($q) => $q->where('clinic_id', $clinicId))
                ->orderByDesc('availment_date');
        }

        return parent::getEloquentQuery()->whereNull('id');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Select::make('member_id')
                        ->relationship('member', 'name')
                        ->label('Member Name')
                        ->disabled(),
                    Forms\Components\Select::make('service_id')
                        ->relationship('service', 'name')
                        ->label('Service Claimed')
                        ->disabled(),
                    Forms\Components\DatePicker::make('availment_date')
                        ->label('Date of Availment')
                        ->disabled(),
                    Forms\Components\TextInput::make('status')
                        ->label('Status')
                        ->disabled(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Units Involved')
                ->description('Units linked to this procedure')
                ->schema([
                    Forms\Components\Repeater::make('units')
                        ->schema([
                            Forms\Components\TextInput::make('unit.name')
                                ->label('Unit Name')
                                ->disabled(),
                            Forms\Components\TextInput::make('unitType.name')
                                ->label('Unit Type')
                                ->disabled(),
                            Forms\Components\TextInput::make('quantity')
                                ->label('Quantity')
                                ->disabled(),
                        ])
                        ->columns(3)
                        ->disableItemCreation()
                        ->disableItemDeletion()
                        ->disableItemMovement(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('member.name')->label('Member')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('service.name')->label('Service')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('clinic_name')->label('Clinic'),
                Tables\Columns\TextColumn::make('availment_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'approved' => 'success',
                        'denied' => 'danger',
                        default => 'warning',
                    }),
            ])
            ->defaultSort('availment_date', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Procedure Details')
                    ->modalWidth('4xl')
                    ->mutateRecordDataUsing(function (Procedure $record, array $data) {
                        $record->load(['member', 'service.clinic', 'units.unit', 'units.unitType']);
                        return [
                            ...$record->toArray(),
                            // 'dentist_name' => $dentist ? $dentist->first_name . ' ' . $dentist->last_name : '—',
                            'clinic_name' => $clinic->clinic_name ?? '—',
                            'units' => $record->units->map(fn ($unit) => [
                                'quantity' => $unit->quantity,
                                'unit' => ['name' => $unit->unit->name ?? '-'],
                                'unitType' => ['name' => $unit->unitType->name ?? '-'],
                            ])->toArray(),
                        ];
                    })
                    ->form([
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('member.name')
                                    ->label('Member Name')
                                    ->disabled(),
                                Forms\Components\TextInput::make('service.name')
                                    ->label('Service Claimed')
                                    ->disabled(),
                                Forms\Components\TextInput::make('clinic_name')
                                    ->label('Clinic Name')
                                    ->disabled(),
                                Forms\Components\DatePicker::make('availment_date')
                                    ->label('Date of Availment')
                                    ->disabled(),
                                Forms\Components\TextInput::make('status')
                                    ->label('Current Status')
                                    ->disabled(),
                                Forms\Components\Section::make('Units Involved')
                                    ->schema([
                                        Forms\Components\Repeater::make('units')
                                            ->schema([
                                                Forms\Components\TextInput::make('unit.name')
                                                    ->label('Unit Name')
                                                    ->disabled(),
                                                Forms\Components\TextInput::make('unitType.name')
                                                    ->label('Unit Type')
                                                    ->disabled(),
                                                Forms\Components\TextInput::make('quantity')
                                                    ->label('Quantity')
                                                    ->disabled(),
                                            ])
                                            ->columns(3)
                                            ->disableItemCreation()
                                            ->disableItemDeletion()
                                            ->disableItemMovement(),
                                    ]),
                            ])
                            ->columns(2),
                    ]),
            ]);
    }
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check()
            && auth()->user()->hasAnyRole(['Super Admin', 'Dentist']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProcedures::route('/'),
        ];
    }
}
