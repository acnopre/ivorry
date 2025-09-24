<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClinicsResource\Pages;
use App\Filament\Resources\ClinicsResource\RelationManagers;
use App\Models\Clinics;
use Filament\Forms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\TaxType;
use App\Models\BusinessType;
use App\Models\AccountType;
use App\Models\AccreditationStatus;

class ClinicsResource extends Resource
{
    protected static ?string $model = Clinics::class;

    protected static ?string $navigationGroup = 'Dental Management';
    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $navigationLabel = 'Clinic Details';
    protected static ?int $navigationSort = 2;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Clinic Information')
                    ->schema([
                        Forms\Components\TextInput::make('clinic_name')->required(),
                        Forms\Components\TextInput::make('registered_name'),
                        Forms\Components\Textarea::make('clinic_address'),
                        Forms\Components\TextInput::make('clinic_landline'),
                        Forms\Components\TextInput::make('clinic_mobile'),
                        Forms\Components\TextInput::make('viber_no'),
                        Forms\Components\TextInput::make('clinic_email')->email(),
                        Forms\Components\Textarea::make('alt_address')->label('Alternative Address'),
                    ])->columns(2),

                Forms\Components\Section::make('PRC / PTR Information')
                    ->schema([
                        Forms\Components\TextInput::make('prc_license_no'),
                        Forms\Components\DatePicker::make('prc_expiration_date'),
                        Forms\Components\TextInput::make('ptr_no'),
                        Forms\Components\DatePicker::make('ptr_date_issued'),
                    ])->columns(2),

                Forms\Components\Section::make('Accreditation & Tax')
                    ->schema([
                        Forms\Components\TextInput::make('other_hmo_accreditation'),
                        Forms\Components\TextInput::make('tax_identification_no'),
                        Forms\Components\Select::make('tax_type')
                            ->label('Tax Type')
                            ->options(TaxType::pluck('name', 'name'))
                            ->searchable()
                            ->required(),
                    
                            Forms\Components\Select::make('business_type')
                                ->label('Business Type')
                                ->options(BusinessType::pluck('name', 'name'))
                                ->searchable()
                                ->required(),
                        Forms\Components\TextInput::make('sec_registration_no'),
                    ])->columns(2),

                Forms\Components\Section::make('Dentist Information')
                    ->schema([
                        Forms\Components\TextInput::make('dentist_personal_no'),
                        Forms\Components\TextInput::make('dentist_email')->email(),
                    ])->columns(2),

                Forms\Components\Section::make('Clinic Staff')
                    ->schema([
                        Forms\Components\TextInput::make('clinic_staff_name'),
                        Forms\Components\TextInput::make('clinic_staff_mobile'),
                        Forms\Components\TextInput::make('clinic_staff_viber'),
                        Forms\Components\TextInput::make('clinic_staff_email')->email(),
                    ])->columns(2),

                Forms\Components\Section::make('Bank Information')
                    ->schema([
                        Forms\Components\TextInput::make('bank_account_name'),
                        Forms\Components\TextInput::make('bank_account_number'),
                        Forms\Components\TextInput::make('bank_name'),
                        Forms\Components\TextInput::make('bank_branch'),
                        Forms\Components\Select::make('account_type')
                        ->label('Account Type')
                        ->options(AccountType::pluck('name', 'name')) 
                        ->searchable()
                        ->required(),
                    
                    ])->columns(2),

              

                Forms\Components\Section::make('Associate Dentist/s')
                    ->schema([
                        Forms\Components\Repeater::make('dentists')
                            ->relationship('dentists')
                            ->schema([
                                Forms\Components\TextInput::make('last_name')
                                    ->label('Last Name')
                                    ->required(),

                                Forms\Components\TextInput::make('first_name')
                                    ->label('Given Name')
                                    ->required(),

                                Forms\Components\TextInput::make('middle_initial')
                                    ->label('M.I.')
                                    ->maxLength(5),

                                Forms\Components\TextInput::make('prc_license_number')
                                    ->label('PRC Lic #'),

                                Forms\Components\DatePicker::make('prc_expiration_date')
                                    ->label('Expiry Date'),

                                Forms\Components\Toggle::make('is_owner')
                                    ->label('Clinic Owner')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, $get) {
                                        if ($state) {
                                            $items = $get('../../dentists') ?? [];
                                            $currentIndex = $get('../../_currentItem');

                                            foreach ($items as $index => $dentist) {
                                                if ($index !== $currentIndex) {
                                                    $set("../../dentists.{$index}.is_owner", false);
                                                }
                                            }
                                        }
                                    }),

                                Forms\Components\Select::make('specializations')
                                    ->label('Specializations')
                                    ->multiple()
                                    ->relationship('specializations', 'name')
                                    ->preload()
                                    ->searchable(),
                            ])
                            ->columns(3)
                            ->collapsible()
                            ->createItemButtonLabel('Add Associate Dentist'),
                    ]),

                    Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Select::make('accreditation_status')
                        ->label('Accreditation Status')
                        ->options(AccreditationStatus::pluck('name', 'name'))
                        ->searchable()
                        ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('clinic_name')->searchable(),
                Tables\Columns\TextColumn::make('registered_name'),
                Tables\Columns\TextColumn::make('clinic_mobile'),
                Tables\Columns\TextColumn::make('clinic_email'),
                Tables\Columns\TextColumn::make('accreditation_status')
                ->badge()
                ->colors([
                    'success' => 'ACTIVE',
                    'danger'  => 'INACTIVE',
                    'warning' => 'SILENT',
                    'info'    => 'SPECIFIC ACCOUNT',
                ])
                ->label('Accreditation'),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
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
            'index' => Pages\ListClinics::route('/'),
            'create' => Pages\CreateClinics::route('/create'),
            'edit' => Pages\EditClinics::route('/{record}/edit'),
        ];
    }
}
