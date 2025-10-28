<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountResource\Pages;
use App\Imports\AccountImport;
use App\Models\Account;
use App\Models\EndorsementType;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\Action as TableAction;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\{
    Section,
    Grid,
    TextInput,
    Select,
    DatePicker,
    FileUpload,
    Placeholder,
    Toggle
};

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static ?string $navigationLabel = 'Accounts';
    protected static ?string $navigationGroup = 'Accounts & Members';
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Account Information')
                    ->schema([
                        TextInput::make('company_name')
                            ->label('Company Name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('policy_code')
                            ->label('Policy Code')
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->maxLength(50),

                        TextInput::make('hip')
                            ->label('HIP')
                            ->maxLength(255),

                        TextInput::make('card_used')
                            ->label('Card Used')
                            ->maxLength(255),
                    ])->columns(2),

                Section::make('Contract Information')
                    ->schema([
                        DatePicker::make('effective_date')->label('Effective Date'),
                        DatePicker::make('expiration_date')->label('Expiration Date'),
                        Select::make('endorsement_type')
                            ->label('Endorsement Type')
                            ->options(EndorsementType::pluck('name', 'name'))
                            ->required(),
                    ])->columns(3),

                // BASIC SERVICES
                Section::make('Basic Dental Services')
                    ->schema(function (Forms\Get $get, $operation, $record) {
                        $services = Service::where('type', 'basic')->get();

                        return $services->map(function ($service) use ($record) {
                            return Grid::make(12)->schema([
                                Placeholder::make("label_{$service->id}")
                                    ->label('')
                                    ->content($service->name)
                                    ->columnSpan(4),

                                TextInput::make("services.basic.{$service->id}.quantity")
                                    ->label('Quantity')
                                    ->numeric()
                                    ->columnSpan(2)
                                    ->reactive()
                                    ->disabled(
                                        fn(Forms\Get $get) =>
                                        $get("services.basic.{$service->id}.is_unlimited") === true
                                    )
                                    ->dehydrated(true)
                                    ->formatStateUsing(function ($state, $record) use ($service) {
                                        if (! $record) {
                                            return $state;
                                        }
                                        return $record->services()
                                            ->where('service_id', $service->id)
                                            ->value('quantity');
                                    }),

                                TextInput::make("services.basic.{$service->id}.remarks")
                                    ->label('Remarks')
                                    ->columnSpan(4)
                                    ->maxLength(255)
                                    ->formatStateUsing(function ($state, $record) use ($service) {
                                        if (! $record) {
                                            return $state;
                                        }
                                        return $record->services()
                                            ->where('service_id', $service->id)
                                            ->value('remarks');
                                    }),

                                Toggle::make("services.basic.{$service->id}.is_unlimited")
                                    ->label('Unlimited')
                                    ->columnSpan(2)
                                    ->inline(false)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) use ($service) {
                                        if ($state === true) {
                                            // Clear quantity when unlimited is turned ON
                                            $set("services.basic.{$service->id}.quantity", null);
                                        }
                                    })
                                    ->formatStateUsing(function ($state, $record) use ($service) {
                                        if (! $record) {
                                            return $state;
                                        }
                                        return $record->services()
                                            ->where('service_id', $service->id)
                                            ->value('is_unlimited');
                                    }),
                            ])->columns(12);
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
                                    ->columnSpan(4),

                                TextInput::make("services.enhancement.{$enhancement->id}.quantity")
                                    ->label('Quantity')
                                    ->numeric()
                                    ->columnSpan(2)
                                    ->reactive()
                                    ->disabled(
                                        fn(Forms\Get $get) =>
                                        $get("services.enhancement.{$enhancement->id}.is_unlimited") === true
                                    )
                                    ->dehydrated(true)
                                    ->formatStateUsing(function ($state, $record) use ($enhancement) {
                                        if (! $record) {
                                            return $state;
                                        }
                                        return $record->services()
                                            ->where('service_id', $enhancement->id)
                                            ->value('quantity');
                                    }),

                                TextInput::make("services.enhancement.{$enhancement->id}.remarks")
                                    ->label('Remarks')
                                    ->columnSpan(4)
                                    ->maxLength(255)
                                    ->formatStateUsing(function ($state, $record) use ($enhancement) {
                                        if (! $record) {
                                            return $state;
                                        }
                                        return $record->services()
                                            ->where('service_id', $enhancement->id)
                                            ->value('remarks');
                                    }),

                                Toggle::make("services.enhancement.{$enhancement->id}.is_unlimited")
                                    ->label('Unlimited')
                                    ->columnSpan(2)
                                    ->inline(false)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) use ($enhancement) {
                                        if ($state === true) {
                                            // Clear quantity when unlimited is turned ON
                                            $set("services.enhancement.{$enhancement->id}.quantity", null);
                                        }
                                    })
                                    ->formatStateUsing(function ($state, $record) use ($enhancement) {
                                        if (! $record) {
                                            return $state;
                                        }
                                        return $record->services()
                                            ->where('service_id', $enhancement->id)
                                            ->value('is_unlimited');
                                    }),
                            ])->columns(12);
                        })->toArray();
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company_name')->label('Company Name')->sortable()->searchable(),

                TextColumn::make('endorsement_type')
                    ->label('Endorsement')
                    ->badge()
                    ->colors([
                        'success' => fn($state) => $state === 'NEW',
                        'warning' => fn($state) => $state === 'RENEWAL',
                        'info'    => fn($state) => $state === 'AMENDMENT',
                    ]),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn($state) => $state === 1 ? 'Active' : 'Inactive')
                    ->badge()
                    ->colors([
                        'success' => fn($state) => $state === 1,
                        'warning' => fn($state) => $state === 0,
                    ]),

                TextColumn::make('effective_date')->label('Effective')->date(),
                TextColumn::make('expiration_date')->label('Expiration')->date(),
                TextColumn::make('created_at')->label('Created')->dateTime(),
            ])
            ->filters([
                SelectFilter::make('endorsement_type')
                    ->label('Endorsement Type')
                    ->options(EndorsementType::pluck('name', 'name')),
            ])
            ->headerActions([
                Action::make('importXls')
                    ->label('Import XLS')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->form([
                        FileUpload::make('file')
                            ->label('Upload Excel File')
                            ->acceptedFileTypes([
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $relativePath = $data['file'];
                        $disk = Storage::disk('public');
                        $absolutePath = $disk->path($relativePath);

                        if (!$disk->exists($relativePath)) {
                            throw new \Exception("File not found at: {$absolutePath}");
                        }

                        Excel::import(new AccountImport, $absolutePath);
                    }),
            ])
            ->actions([
                ViewAction::make()
                    ->modalHeading('Account Details')
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
                                    ->title('Account approved')
                                    ->send();
                            })
                            ->cancelParentActions(),
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
            && auth()->user()->hasAnyRole(['Super Admin', 'Account Manager', 'Upper Management']);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccounts::route('/'),
            'create' => Pages\CreateAccount::route('/create'),
            'edit' => Pages\EditAccount::route('/{record}/edit'),
        ];
    }
}
