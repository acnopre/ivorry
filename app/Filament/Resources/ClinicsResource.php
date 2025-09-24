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

class ClinicsResource extends Resource
{
    protected static ?string $model = Clinics::class;

    protected static ?string $navigationGroup = 'Dental Management';
    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $navigationLabel = 'Clinic Details';
    protected static ?int $navigationSort = 1;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Clinic Information')
                    ->schema([
                        TextInput::make('clinic_name')
                            ->label('Clinic Name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('registered_name')
                            ->label('Registered Name (per DTI & BIR 2303)')
                            ->maxLength(255),

                        TextInput::make('clinic_owner_last')
                            ->label('Owner Last Name')
                            ->maxLength(255),
                        TextInput::make('clinic_owner_first')
                            ->label('Owner Given Name')
                            ->maxLength(255),
                        TextInput::make('clinic_owner_middle')
                            ->label('Owner Middle Initial')
                            ->maxLength(50),

                        TextInput::make('specializations')
                            ->label('Specialization/s')
                            ->maxLength(255),

                        TextInput::make('prc_license_no')
                            ->label('PRC License No.')
                            ->maxLength(50),
                        DatePicker::make('prc_expiration_date')
                            ->label('Expiration Date'),

                        TextInput::make('ptr_no')
                            ->label('PTR No.')
                            ->maxLength(50),
                        DatePicker::make('ptr_date_issued')
                            ->label('Date Issued'),

                        TextInput::make('other_hmo_accreditation')
                            ->label('Other HMO Accreditation')
                            ->maxLength(255),

                        TextInput::make('tax_identification_no')
                            ->label('Taxpayer’s Identification Number')
                            ->maxLength(50),

                        Select::make('tax_type')
                            ->label('Tax Type')
                            ->options([
                                'vat' => 'VAT',
                                'non_vat' => 'Non-VAT',
                            ]),

                        Select::make('business_type')
                            ->label('Business Type')
                            ->options([
                                'sole_proprietor' => 'Sole Proprietor',
                                'partnership' => 'Partnership',
                                'corporation' => 'Corporation',
                            ]),

                        TextInput::make('sec_registration_no')
                            ->label('SEC Registration No. (For Corporation/Partnership)')
                            ->maxLength(100),

                        Textarea::make('clinic_address')
                            ->label('Clinic Address')
                            ->rows(2)
                            ->maxLength(500),

                        TextInput::make('clinic_landline')
                            ->label('Clinic Landline')
                            ->maxLength(50),

                        TextInput::make('clinic_mobile')
                            ->label('Clinic Mobile Number/s')
                            ->maxLength(100),

                        TextInput::make('viber_no')
                            ->label('Viber No.')
                            ->maxLength(50),

                        TextInput::make('clinic_email')
                            ->label('Clinic Email Address')
                            ->email()
                            ->maxLength(255),
                    ])->columns(2),

                Section::make('Alternative Contact Information')
                    ->schema([
                        Textarea::make('alt_address')
                            ->label('Residence / Alternative Address (Required)')
                            ->rows(2)
                            ->maxLength(500),
                    ]),

                Section::make('Dentist Information')
                    ->schema([
                        TextInput::make('dentist_personal_no')
                            ->label('Dentist Personal No. (Not to be shared to members)')
                            ->maxLength(50),

                        TextInput::make('dentist_email')
                            ->label('Dentist Email Address')
                            ->email()
                            ->maxLength(255),

                        Select::make('clinic_schedule')
                            ->label('Clinic Schedule')
                            ->options([
                                'first_come' => 'First come first serve',
                                'by_appointment' => 'By Appointment only',
                            ]),

                        TextInput::make('schedule_days')
                            ->label('Indicate days to accept cardholders')
                            ->maxLength(255),

                        TextInput::make('number_of_chairs')
                            ->label('Number of Chairs')
                            ->numeric(),

                        Checkbox::make('dental_xray_periapical')
                            ->label('Periapical Xray'),
                        Checkbox::make('dental_xray_panoramic')
                            ->label('Panoramic Xray'),
                    ])->columns(2),

                Section::make('Associate Dentist/s')
                    ->schema([
                        Repeater::make('associate_dentists')
                            ->schema([
                                TextInput::make('last_name')->label('Last Name')->maxLength(255),
                                TextInput::make('first_name')->label('Given Name')->maxLength(255),
                                TextInput::make('middle_initial')->label('M.I.')->maxLength(10),
                                TextInput::make('prc_lic_no')->label('PRC Lic #')->maxLength(50),
                                DatePicker::make('expiry_date')->label('Expiry Date'),
                                TextInput::make('specialization')->label('Specialization')->maxLength(255),
                            ])->columns(3)
                            ->collapsible(),
                    ]),

                Section::make('Clinic Staff')
                    ->schema([
                        TextInput::make('clinic_staff_name')
                            ->label('Name')
                            ->maxLength(255),
                        TextInput::make('clinic_staff_mobile')
                            ->label('Mobile No.')
                            ->maxLength(50),
                        TextInput::make('clinic_staff_viber')
                            ->label('Viber No.')
                            ->maxLength(50),
                        TextInput::make('clinic_staff_email')
                            ->label('Email Address')
                            ->email()
                            ->maxLength(255),
                    ])->columns(2),

                Section::make('Bank Information')
                    ->schema([
                        TextInput::make('bank_account_name')
                            ->label('Bank Account Name')
                            ->maxLength(255),
                        TextInput::make('bank_account_number')
                            ->label('Bank Account Number')
                            ->maxLength(50),
                        TextInput::make('bank_name')
                            ->label('Name of Bank')
                            ->maxLength(255),
                        TextInput::make('bank_branch')
                            ->label('Branch')
                            ->maxLength(255),
                        Select::make('account_type')
                            ->label('Account Type')
                            ->options([
                                'savings' => 'Savings',
                                'current' => 'Current',
                            ]),
                    ])->columns(2),

                Section::make('Status')
                    ->schema([
                        Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                            ])
                            ->default('active'),
                    ]),
            ]);
    }



    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Code')->sortable()->searchable(),
                TextColumn::make('name')->label('Clinic Name')->sortable()->searchable(),
                TextColumn::make('contact_person')->label('Contact'),
                TextColumn::make('email')->label('Email')->toggleable(),
                TextColumn::make('phone')->label('Phone')->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ])
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),
                TextColumn::make('created_at')->dateTime()->label('Created'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
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


    public static function getRelations(): array
    {
        return [
            //
        ];
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
