<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DentistResource\Pages;
use Filament\Forms;
use App\Models\Dentist;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Actions\ViewAction;
use App\Models\BasicDentalService;
use App\Models\PlanEnhancement;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use App\Models\AccreditationStatus;
use Illuminate\Database\Eloquent\Builder;

class DentistResource extends Resource
{
    protected static ?string $model = Dentist::class;

    protected static ?string $navigationGroup = 'Accounts & Members';
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Dentists';

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Section::make('Dentist Information')
                ->schema([
                    TextInput::make('last_name')->required(),
                    TextInput::make('first_name')->required(),
                    TextInput::make('middle_initial'),
                    TextInput::make('suffix'),
                    TextInput::make('clinic_name'),
                    TextInput::make('branch_code'),
                    TextInput::make('tin_number'),
                    TextInput::make('clinic_address'),
                    TextInput::make('barangay'),
                    TextInput::make('city'),
                    TextInput::make('province'),
                    TextInput::make('region'),
                    TextInput::make('landline'),
                    TextInput::make('mobile_number'),
                    TextInput::make('alternative_number'),

                    // Specializations Multi-Select
                    Forms\Components\Select::make('specializations')
                    ->label('Specializations')
                    ->multiple()
                    ->relationship('specializations', 'name')
                    ->preload(),


                   // Accreditation Status Dropdown (store name, not ID)
                   Forms\Components\Select::make('accreditation_status') // virtual field
                   ->label('Accreditation Status')
                   ->options(AccreditationStatus::pluck('name', 'name')->toArray())
                   ->required(),

                ])->columns(2),

            Section::make('Bank & Tax Information')
                ->schema([
                    TextInput::make('bank_account_name'),
                    TextInput::make('bank_branch'),
                    TextInput::make('bank_account_number'),
                    Forms\Components\Select::make('tax_registration')
                        ->options([
                            'VAT' => 'VAT',
                            'NON-VAT' => 'NON-VAT',
                            '0%' => '0%',
                        ])
                        ->default('NON-VAT'),
                    TextInput::make('withholding_tax'),
                ])->columns(2),
                    
                    
                Section::make('Basic Dental Services Rate')->schema(function (\Filament\Forms\Get $get, $operation, $record) {
                    $services = \App\Models\BasicDentalService::all();
                
                    return $services->map(function ($service) use ($record) {
                        $fee = null;
                
                        if ($record) {
                            $pivot = $record->basicDentalServices()
                                ->where('basic_dental_service_id', $service->id)
                                ->first();
                
                            $fee = $pivot?->pivot?->fee; // safe navigation
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

                Section::make('Plan Enhancements Rate')->schema(function (\Filament\Forms\Get $get, $operation, $record) {
                    $enhancements = \App\Models\PlanEnhancement::all();
                
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
                TextColumn::make('last_name')->sortable()->searchable(),
                TextColumn::make('first_name')->sortable()->searchable(),
                TextColumn::make('clinic_name')->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn($state) => $state === 1 ? 'Active' : 'Inactive')
                    ->badge()
                    ->colors([
                        'success' => fn($state) => $state === 'ACTIVE',
                        'warning' => fn($state) => $state === 'INACTIVE',
                    ]),
            ])
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make()
                ->modalHeading('Dentist Details')
                ->modalSubmitAction(false)
                ->extraModalFooterActions([
                    TableAction::make('approve')
                        ->label('Approve')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Model $record) {
                            $record->update(['status' => 1]);

                            Notification::make()
                                ->success()
                                ->title('Dentist approved')
                                ->send();
                        })
                        ->cancelParentActions(), // ← closes the view modal
                ]),

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
