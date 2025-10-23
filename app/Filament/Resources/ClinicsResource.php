<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClinicsResource\Pages;
use App\Filament\Resources\ClinicsResource\RelationManagers;
use App\Models\Clinic;
use Filament\Forms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
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
use App\Models\Service;
use App\Imports\ClinicImport;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\VatType;

class ClinicsResource extends Resource
{
    protected static ?string $model = Clinic::class;

    protected static ?string $navigationGroup = 'Dental Management';
    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $navigationLabel = 'Clinic Details';
    protected static ?int $navigationSort = 1;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Clinic Information')
                    ->schema([
                        Forms\Components\TextInput::make('clinic_name')
                            ->label('Name on signage')
                            ->required(),

                        Forms\Components\TextInput::make('registered_name'),

                        // REGION
                        Forms\Components\Select::make('region_id')
                            ->label('Region')
                            ->options(\App\Models\Region::pluck('name', 'id'))
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(
                                fn($state, callable $set) =>
                                $set('province_id', null)
                            )
                            ->required(),

                        // PROVINCE (depends on region)
                        Forms\Components\Select::make('province_id')
                            ->label('Province')
                            ->options(
                                fn(callable $get) =>
                                $get('region_id')
                                    ? \App\Models\Province::where('region_id', $get('region_id'))
                                    ->pluck('name', 'id')
                                    : collect()
                            )
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(
                                fn($state, callable $set) =>
                                $set('municipality_id', null)
                            )
                            ->required(),

                        // MUNICIPALITY / CITY (depends on province)
                        Forms\Components\Select::make('municipality_id')
                            ->label('City / Municipality')
                            ->options(
                                fn(callable $get) =>
                                $get('province_id')
                                    ? \App\Models\Municipality::where('province_id', $get('province_id'))
                                    ->pluck('name', 'id')
                                    : collect()
                            )
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(
                                fn($state, callable $set) =>
                                $set('barangay_id', null)
                            )
                            ->required(),

                        // BARANGAY (depends on municipality)
                        Forms\Components\Select::make('barangay_id')
                            ->label('Barangay')
                            ->options(
                                fn(callable $get) =>
                                $get('municipality_id')
                                    ? \App\Models\Barangay::where('municipality_id', $get('municipality_id'))
                                    ->pluck('name', 'id')
                                    : collect()
                            )
                            ->searchable()
                            ->required(),

                        // STREET / HOUSE NO.
                        Forms\Components\TextInput::make('street')
                            ->label('Street / House No.')
                            ->placeholder('e.g., 123 Mabini St.')
                            ->required(),

                        Forms\Components\TextInput::make('clinic_landline')->label('Landline'),
                        Forms\Components\TextInput::make('clinic_mobile')->label('Mobile'),
                        Forms\Components\TextInput::make('viber_no')->label('Viber No.'),
                        Forms\Components\TextInput::make('clinic_email')->email(),
                        Forms\Components\Textarea::make('alt_address')->label('Alternative Address'),
                    ])
                    ->columns(2),


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
                        Forms\Components\Textarea::make('remarks')
                            ->label('Remarks')
                            ->rows(3)
                            ->placeholder('Enter any remarks related to the bank information...'),

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
                                    ->maxLength(1),

                                Forms\Components\TextInput::make('prc_license_number')
                                    ->label('PRC Lic #'),

                                Forms\Components\DatePicker::make('prc_expiration_date')
                                    ->label('Expiry Date'),

                                Forms\Components\Toggle::make('is_owner')
                                    ->label('Clinic Owner')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        if ($state) {
                                            $dentists = $get('../../dentists') ?? [];

                                            // Find the current item's data
                                            $currentItem = $get(); // gets this dentist’s data
                                            $currentIndex = collect($dentists)
                                                ->search(
                                                    fn($dentist) =>
                                                    $dentist['last_name'] === $currentItem['last_name'] &&
                                                        $dentist['first_name'] === $currentItem['first_name']
                                                );

                                            if ($currentIndex !== false) {
                                                foreach ($dentists as $index => $dentist) {
                                                    if ($index !== $currentIndex) {
                                                        $set("../../dentists.{$index}.is_owner", false);
                                                    }
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


                // BASIC SERVICES
                Section::make('Basic Dental Services')
                    ->schema(function (Forms\Get $get, $operation, $record) {
                        $services = Service::where('type', 'basic')->get();

                        return $services->map(function ($service) use ($record) {
                            return Grid::make(12)->schema([
                                Placeholder::make("label_{$service->id}")
                                    ->label('')
                                    ->content($service->name)
                                    ->columnSpan(6),

                                TextInput::make("services.basic.{$service->id}")
                                    ->label('Fee')
                                    ->numeric()
                                    ->columnSpan(6)
                                    ->formatStateUsing(function ($state, $record) use ($service) {
                                        // Hydrate the current fee value from pivot table
                                        if (! $record) {
                                            return $state; // for create mode
                                        }

                                        return $record->services()
                                            ->where('service_id', $service->id)
                                            ->value('fee');
                                    }),
                            ]);
                        })->toArray();
                    }),

                // PLAN ENHANCEMENTS
                Section::make('Plan Enhancements')
                    ->schema(function (Forms\Get $get, $operation, $record) {
                        $enhancements = Service::where('type', 'enhancement')->get();

                        return $enhancements->map(function ($enhancement) use ($record) {
                            return Grid::make(12)->schema([
                                Placeholder::make("label_{$enhancement->id}")
                                    ->label('')
                                    ->content($enhancement->name)
                                    ->columnSpan(6),

                                TextInput::make("services.enhancement.{$enhancement->id}")
                                    ->label('Fee')
                                    ->numeric()
                                    ->columnSpan(6)
                                    ->formatStateUsing(function ($state, $record) use ($enhancement) {
                                        if (! $record) {
                                            return $state;
                                        }

                                        return $record->services()
                                            ->where('service_id', $enhancement->id)
                                            ->value('fee');
                                    }),
                            ]);
                        })->toArray();
                    }),


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
                Tables\Columns\TextColumn::make('accreditation_status')
                    ->badge()
                    ->colors([
                        'success' => 'ACTIVE',
                        'danger'  => 'INACTIVE',
                        'warning' => 'SILENT',
                        'info'    => 'SPECIFIC ACCOUNT',
                    ])
                    ->label('Accreditation Status'),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->headerActions([
                Action::make('importXls')
                    ->label('Import XLS')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->form([
                        \Filament\Forms\Components\FileUpload::make('file')
                            ->label('Upload Excel File')
                            ->required()
                            ->acceptedFileTypes(['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']),
                    ])
                    ->action(function (array $data): void {
                        $file = $data['file'];

                        // Use Laravel Excel to import
                        Excel::import(new ClinicImport, $file->getRealPath());

                        Notification::make()
                            ->title('Clinics Imported Successfully!')
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\ListClinics::route('/'),
            'create' => Pages\CreateClinics::route('/create'),
            'edit' => Pages\EditClinics::route('/{record}/edit'),
        ];
    }
}
