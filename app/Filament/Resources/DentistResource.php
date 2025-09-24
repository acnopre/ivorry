<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DentistResource\Pages;
use App\Models\Dentist;
use App\Models\BasicDentalService;
use App\Models\PlanEnhancement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;

class DentistResource extends Resource
{
    protected static ?string $model = Dentist::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationGroup = 'Dental Management';
    protected static ?string $navigationLabel = 'Dentists';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Dentist Info')->schema([
                    Forms\Components\Select::make('clinic_id')
                        ->relationship('clinic', 'clinic_name')
                        ->label('Clinic')
                        ->required()
                        ->searchable(),

                    Forms\Components\TextInput::make('last_name')->required(),
                    Forms\Components\TextInput::make('first_name')->required(),
                    Forms\Components\TextInput::make('middle_initial')->maxLength(3),
                    Forms\Components\TextInput::make('prc_license_number')->label('PRC License No.'),
                    Forms\Components\DatePicker::make('prc_expiration_date')->label('PRC Expiration Date'),

                    Forms\Components\Toggle::make('is_owner')->label('Is Owner'),
                ])->columns(2),

                // Basic Dental Services
                Section::make('Basic Dental Services Rate')->schema(function (Forms\Get $get, $operation, $record) {
                    $services = BasicDentalService::all();

                    return $services->map(function ($service) use ($record) {
                        $fee = null;

                        if ($record) {
                            $pivot = $record->basicDentalServices()
                                ->where('basic_dental_service_id', $service->id)
                                ->first();
                            $fee = $pivot?->pivot?->fee;
                        }

                        return Grid::make(12)->schema([
                            Placeholder::make("label_{$service->id}")
                                ->label('')
                                ->content($service->name)
                                ->columnSpan(6),

                            Placeholder::make("current_fee_{$service->id}")
                                ->label('Current Fee')
                                ->content($fee ? "₱" . number_format($fee, 2) : '—')
                                ->visible((bool) $record)
                                ->columnSpan(3),

                            TextInput::make("basic_dental_services.{$service->id}")
                                ->label('Fee')
                                ->numeric()
                                ->prefix('₱')
                                ->default($fee)
                                ->columnSpan($record ? 3 : 6),
                        ]);
                    })->toArray();
                }),

                // Plan Enhancements
                Section::make('Plan Enhancements Rate')->schema(function (Forms\Get $get, $operation, $record) {
                    $enhancements = PlanEnhancement::all();

                    return $enhancements->map(function ($enhancement) use ($record) {
                        $fee = null;

                        if ($record) {
                            $pivot = $record->planEnhancements()
                                ->where('plan_enhancement_id', $enhancement->id)
                                ->first();
                            $fee = $pivot?->pivot?->fee;
                        }

                        return Grid::make(12)->schema([
                            Placeholder::make("label_{$enhancement->id}")
                                ->label('')
                                ->content($enhancement->name)
                                ->columnSpan(6),

                            Placeholder::make("current_fee_{$enhancement->id}")
                                ->label('Current Fee')
                                ->content($fee ? "₱" . number_format($fee, 2) : '—')
                                ->visible((bool) $record)
                                ->columnSpan(3),

                            TextInput::make("plan_enhancements.{$enhancement->id}")
                                ->label('Fee')
                                ->numeric()
                                ->prefix('₱')
                                ->default($fee)
                                ->columnSpan($record ? 3 : 6),
                        ]);
                    })->toArray();
                }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('clinic.clinic_name')->label('Clinic')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('last_name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('first_name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('prc_license_number')->label('PRC No.'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDentists::route('/'),
            'create' => Pages\CreateDentist::route('/create'),
            'edit' => Pages\EditDentist::route('/{record}/edit'),
        ];
    }
}
