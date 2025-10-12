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
                    Forms\Components\Select::make('specializations')
                    ->label('Specializations')
                    ->multiple()
                    ->relationship('specializations', 'name')
                    ->preload()
                    ->searchable(),
                ])->columns(2),
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
                Tables\Columns\TextColumn::make('is_owner')
                    ->label('Is Owner')
                    ->formatStateUsing(fn($state) => $state == 1 ? 'Owner' : '')
                    ->badge()
                    ->colors([
                        'success' => fn($state) => $state == 1,
                    ]),
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

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check()
            && auth()->user()->hasAnyRole(['Super Admin', 'Accreditation', 'Upper Management']);
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
