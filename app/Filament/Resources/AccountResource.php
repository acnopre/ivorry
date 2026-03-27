<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountResource\Pages;
use App\Imports\AccountImport;
use App\Models\Account;
use App\Models\AccountAmendment;
use App\Models\AccountService;
use App\Models\EndorsementType;
use App\Models\Hip;
use App\Models\ImportLog;
use App\Models\MblType;
use App\Models\Role;
use App\Models\Service;
use Carbon\Carbon;
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
    Hidden,
    Placeholder,
    Toggle
};

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;

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
            ->schema(function ($record) {

                // Determine if record is new or editing
                $isCreate = blank($record);
                // Allow fields only on create OR if record’s amendment_status == 1
                $isAmendment = fn(Forms\Get $get) =>  $isCreate ||  $get('endorsement_type') === 'AMENDMENT';

                return [
                    Section::make('Account Information')
                        ->schema([

                            TextInput::make('company_name')
                                ->label('Company Name')
                                ->required()
                                ->maxLength(255)
                                ->disabled(fn(Forms\Get $get) => ! $isAmendment($get)),


                            TextInput::make('policy_code')
                                ->label('Policy Code')
                                ->unique(
                                    table: 'accounts',
                                    column: 'policy_code',
                                    ignoreRecord: true,
                                    modifyRuleUsing: fn($rule) => $rule->whereNull('deleted_at')
                                )
                                ->required()
                                ->maxLength(50)
                                ->disabled(fn(Forms\Get $get) => ! $isAmendment($get)),

                            Select::make('hip_id')
                                ->label('HIP')
                                ->relationship('hip', 'name')
                                ->searchable()
                                ->preload()
                                ->disabled(fn(Forms\Get $get) => ! $isAmendment($get)),

                            TextInput::make('card_used')
                                ->label('Card Used')
                                ->maxLength(255)
                                ->disabled(fn(Forms\Get $get) => ! $isAmendment($get)),


                            Select::make('plan_type')
                                ->label('Plan Type')
                                ->options([
                                    'INDIVIDUAL' => 'Individual',
                                    'SHARED' => 'Shared',
                                ])
                                ->reactive()
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    if ($state !== 'SHARED') {
                                        $set('members', []);
                                    }
                                })
                                ->required()
                                ->disabled(fn(Forms\Get $get) => ! $isAmendment($get)),

                            Select::make('coverage_period_type')
                                ->label('Coverage Period Type')
                                ->default('ACCOUNT')
                                ->options([
                                    'ACCOUNT' => 'Account',
                                    'MEMBER' => 'Member',
                                ])
                                ->reactive()
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    if ($state !== 'SHARED') {
                                        $set('members', []);
                                    }
                                })
                                ->required()
                                ->disabled(fn(Forms\Get $get) => ! $isAmendment($get)),

                            Select::make('mbl_type')
                                ->label('MBL Type')
                                ->default('Procedural')
                                ->options(MblType::pluck('name', 'name'))
                                ->reactive()
                                ->required()
                                ->disabled(fn(Forms\Get $get) => ! $isAmendment($get)),

                            TextInput::make('mbl_amount')
                                ->label('MBL Amount (₱)')
                                ->numeric()
                                ->prefix('₱')
                                ->visible(fn(Forms\Get $get) => $get('mbl_type') === 'Fixed')
                                ->required(fn(Forms\Get $get) => $get('mbl_type') === 'Fixed')
                                ->disabled(fn(Forms\Get $get) => ! $isAmendment($get)),
                        ])->columns(2),



                    Section::make('Members')
                        ->visible(fn(Forms\Get $get) => $get('plan_type') === 'SHARED')
                        ->schema([
                            Forms\Components\Repeater::make('members')
                                ->label('Members')
                                ->relationship('members')
                                ->schema([
                                    Forms\Components\TextInput::make('first_name')
                                        ->label('First Name')
                                        ->required()
                                        ->maxLength(255),

                                    Forms\Components\TextInput::make('middle_name')
                                        ->label('Middle Name')
                                        ->maxLength(255),

                                    Forms\Components\TextInput::make('last_name')
                                        ->label('Last Name')
                                        ->required()
                                        ->maxLength(255),

                                    Forms\Components\TextInput::make('suffix')
                                        ->label('Suffix')
                                        ->maxLength(50),
                                    Forms\Components\DatePicker::make('birthdate')
                                        ->label('Birthdate')
                                        ->native(false),

                                    Forms\Components\Select::make('gender')
                                        ->label('Gender')
                                        ->options([
                                            'male' => 'Male',
                                            'female' => 'Female',
                                        ])
                                        ->native(false),

                                    Forms\Components\TextInput::make('email')
                                        ->label('Email')
                                        ->maxLength(255),

                                    Forms\Components\Toggle::make('is_principal')
                                        ->label('Principal')
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            if ($state) {
                                                $members = $get('../../members') ?? [];

                                                // Find the current item's data
                                                $currentItem = $get(); // gets this member data
                                                $currentIndex = collect($members)
                                                    ->search(
                                                        fn($member) =>
                                                        $member['last_name'] === $currentItem['last_name'] &&
                                                            $member['first_name'] === $currentItem['first_name']
                                                    );

                                                if ($currentIndex !== false) {
                                                    foreach ($members as $index => $member) {
                                                        if ($index !== $currentIndex) {
                                                            $set("../../members.{$index}.is_principal", false);
                                                        }
                                                    }
                                                }
                                            }
                                        }),

                                ])
                                ->columns(2)
                                ->collapsible()
                                ->createItemButtonLabel('Add Member')
                                ->disabled(
                                    fn(Forms\Get $get) =>
                                    ! ($isAmendment($get) || $get('endorsement_type') === 'RENEWAL')
                                ),
                        ]),


                    Section::make('Contract Information')
                        ->schema([
                            DatePicker::make('effective_date')
                                ->label('Effective Date')
                                ->native(false)
                                ->reactive()
                                ->disabled(fn(Forms\Get $get) => !($isAmendment($get) || $get('endorsement_type') === 'RENEWAL'))
                                ->rules([
                                    fn(Forms\Get $get) => function ($attribute, $value, $fail) use ($get, $record) {
                                        if ($get('endorsement_type') === 'RENEWAL' && $record) {
                                            if ($value === $record->effective_date) {
                                                $fail('For renewal, the effective date must be different from the current effective date.');
                                            }
                                        }
                                    },
                                ])
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                        $expiration = Carbon::parse($state)->addYear()->subDay();
                                        $set('expiration_date', $expiration->format('Y-m-d'));
                                    }
                                }),

                            DatePicker::make('expiration_date')
                                ->label('Valid Until')
                                ->native(false)
                                ->dehydrated(true),

                            Select::make('endorsement_type')
                                ->label('Endorsement Type')
                                // ->visible($record?->account_status == 'active')
                                ->hintAction(
                                    Forms\Components\Actions\Action::make('endorsementInfo')
                                        ->label('')
                                        ->icon('heroicon-m-information-circle')
                                        ->modalHeading('Endorsement Types')
                                        ->modalContent(view('filament.modals.endorsement-info'))
                                        ->modalSubmitAction(false)
                                        ->modalCancelActionLabel('Close')
                                )
                                ->options(function ($record) {

                                    if (blank($record)) {
                                        return [
                                            'NEW' => 'NEW',
                                        ];
                                    }

                                    // If editing → return full list
                                    return EndorsementType::pluck('name', 'name')->toArray();
                                })
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, Forms\Set $set, $record) {

                                    if ($state === 'RENEWAL') {
                                        // Existing renewal logic
                                    }

                                    if ($state === 'AMENDMENT') {
                                        // Enable all fields by setting necessary defaults if needed
                                        // For example, you could preload existing service values
                                    }

                                    if (!$record) {
                                        return;
                                    }

                                    // Load existing BASIC services from the account
                                    $basicServices = AccountService::where('account_id', $record->id)
                                        ->whereHas('service', fn($q) => $q->where('type', 'basic'))
                                        ->with('service')
                                        ->get();

                                    foreach ($basicServices as $service) {
                                        $set("services.basic.{$service->service_id}.is_unlimited", $service->is_unlimited);
                                        $set("services.basic.{$service->service_id}.quantity", $service->default_quantity);
                                        $set("services.basic.{$service->service_id}.remarks", $service->remarks);
                                    }

                                    // Load existing ENHANCEMENT services from the account
                                    $enhancementServices = AccountService::where('account_id', $record->id)
                                        ->whereHas('service', fn($q) => $q->where('type', 'enhancement'))
                                        ->with('service')
                                        ->get();
                                    foreach ($enhancementServices as $service) {
                                        $set("services.enhancement.{$service->service_id}.is_unlimited", $service->is_unlimited);
                                        $set("services.enhancement.{$service->service_id}.quantity", $service->default_quantity);
                                        $set("services.enhancement.{$service->service_id}.remarks", $service->remarks);
                                    }

                                    $specialServices = AccountService::where('account_id', $record->id)
                                        ->whereHas('service', fn($q) => $q->where('type', 'special'))
                                        ->with('service')
                                        ->get();
                                    foreach ($specialServices as $service) {
                                        $set("services.special.{$service->service_id}.is_unlimited", $service->is_unlimited);
                                        $set("services.special.{$service->service_id}.quantity", $service->default_quantity);
                                        $set("services.special.{$service->service_id}.remarks", $service->remarks);
                                    }
                                })

                                ->disabled(false),
                        ])->columns(3),

                    Section::make('Basic Dental Services')
                        ->icon(fn(Forms\Get $get) => match ($get('endorsement_type')) {
                            'RENEWAL' => 'heroicon-m-arrow-path',
                            'AMENDMENT' => 'heroicon-m-pencil-square',
                            default => null
                        })
                        ->iconColor(fn(Forms\Get $get) => match ($get('endorsement_type')) {
                            'RENEWAL' => 'warning',
                            'AMENDMENT' => 'info',
                            default => null
                        })
                        ->description(fn(Forms\Get $get) => match ($get('endorsement_type')) {
                            'RENEWAL' => 'Renewal will reset all service quantities to their default values.',
                            'AMENDMENT' => 'You can modify service quantities and settings for this amendment.',
                            default => null
                        })
                        ->schema(function () use ($record, $isAmendment) {
                            $services = Service::where('type', 'basic')->get();

                            return $services->map(function ($service) use ($record, $isAmendment) {
                                return Grid::make(12)->schema([
                                    Placeholder::make("label_{$service->id}")
                                        ->label('')
                                        ->content($service->name)
                                        ->columnSpan(4),

                                    TextInput::make("services.basic.{$service->id}.quantity")
                                        ->label('Quantity')
                                        ->numeric()
                                        ->columnSpan(2)
                                        ->disabled(true)
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
                                        ->disabled(
                                            fn(Forms\Get $get) => $get('endorsement_type') === 'RENEWAL' || !$isAmendment($get)
                                        )
                                        ->formatStateUsing(function ($state, $record) use ($service) {
                                            if (! $record) {
                                                return $state;
                                            }
                                            return $record->services()
                                                ->where('service_id', $service->id)
                                                ->value('remarks');
                                        }),

                                    Forms\Components\Hidden::make("services.basic.{$service->id}.default_quantity")
                                        ->default(fn(Forms\Get $get) => $get("services.basic.{$service->id}.quantity"))
                                        ->formatStateUsing(function ($state, $record) use ($service) {
                                            if (! $record) {
                                                return $state;
                                            }
                                            return $record->services()
                                                ->where('service_id', $service->id)
                                                ->value('default_quantity') ?? $record->services()
                                                ->where('service_id', $service->id)
                                                ->value('quantity');
                                        }),


                                    Hidden::make("services.basic.{$service->id}.is_unlimited")
                                        ->default(true)
                                        ->formatStateUsing(fn() => true),

                                    Toggle::make("services.basic.{$service->id}.unlimited_display")
                                        ->label('Unlimited')
                                        ->columnSpan(2)
                                        ->inline(false)
                                        ->default(true)
                                        ->disabled(true)
                                        ->dehydrated(false),
                                ])->columns(12);
                            })->toArray();
                        }),

                    Section::make('Plan Enhancements')
                        ->icon(fn(Forms\Get $get) => match ($get('endorsement_type')) {
                            'RENEWAL' => 'heroicon-m-arrow-path',
                            'AMENDMENT' => 'heroicon-m-pencil-square',
                            default => null
                        })
                        ->iconColor(fn(Forms\Get $get) => match ($get('endorsement_type')) {
                            'RENEWAL' => 'warning',
                            'AMENDMENT' => 'info',
                            default => null
                        })
                        ->description(fn(Forms\Get $get) => match ($get('endorsement_type')) {
                            'RENEWAL' => 'Renewal will reset all service quantities to their default values.',
                            'AMENDMENT' => 'You can modify service quantities and settings for this amendment.',
                            default => null
                        })
                        ->schema(function () use ($record, $isAmendment) {
                            $enhancements = Service::where('type', 'enhancement')->get();

                            return $enhancements->map(function ($enhancement) use ($record, $isAmendment) {
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
                                            $get("services.enhancement.{$enhancement->id}.is_unlimited") === true ||
                                                $get('endorsement_type') === 'RENEWAL' ||
                                                !$isAmendment($get)
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
                                        ->disabled(
                                            fn(Forms\Get $get) => $get('endorsement_type') === 'RENEWAL' || !$isAmendment($get)
                                        )
                                        ->formatStateUsing(function ($state, $record) use ($enhancement) {
                                            if (! $record) {
                                                return $state;
                                            }
                                            return $record->services()
                                                ->where('service_id', $enhancement->id)
                                                ->value('remarks');
                                        }),

                                    Hidden::make("services.enhancement.{$enhancement->id}.default_quantity")
                                        ->default(fn(Forms\Get $get) => $get("services.enhancement.{$enhancement->id}.quantity"))
                                        ->formatStateUsing(function ($state, $record) use ($enhancement) {
                                            if (! $record) {
                                                return $state;
                                            }
                                            return $record->services()
                                                ->where('service_id', $enhancement->id)
                                                ->value('default_quantity') ?? $record->services()
                                                ->where('service_id', $enhancement->id)
                                                ->value('quantity');
                                        }),

                                    Toggle::make("services.enhancement.{$enhancement->id}.is_unlimited")
                                        ->label('Unlimited')
                                        ->columnSpan(2)
                                        ->inline(false)
                                        ->reactive()
                                        ->default(false)
                                        ->disabled(
                                            fn(Forms\Get $get) => $get('endorsement_type') === 'RENEWAL' || !$isAmendment($get)
                                        )
                                        ->afterStateUpdated(function ($state, Forms\Set $set) use ($enhancement) {
                                            if ($state === true) {
                                                $set("services.enhancement.{$enhancement->id}.quantity", null);
                                            }
                                        })
                                        ->formatStateUsing(function ($state, $record) use ($enhancement) {
                                            if (! $record) {
                                                return false;
                                            }
                                            return $record->services()
                                                ->where('service_id', $enhancement->id)
                                                ->value('is_unlimited') ?? false;
                                        }),
                                ])->columns(12);
                            })->toArray();
                        }),

                    Section::make('Special Procedure')
                        ->icon(fn(Forms\Get $get) => match ($get('endorsement_type')) {
                            'RENEWAL' => 'heroicon-m-arrow-path',
                            'AMENDMENT' => 'heroicon-m-pencil-square',
                            default => null
                        })
                        ->iconColor(fn(Forms\Get $get) => match ($get('endorsement_type')) {
                            'RENEWAL' => 'warning',
                            'AMENDMENT' => 'info',
                            default => null
                        })
                        ->description(fn(Forms\Get $get) => match ($get('endorsement_type')) {
                            'RENEWAL' => 'Renewal will reset all service quantities to their default values.',
                            'AMENDMENT' => 'You can modify service quantities and settings for this amendment.',
                            default => null
                        })
                        ->schema(function () use ($record, $isAmendment) {
                            $specials = Service::where('type', 'special')->get();

                            return $specials->map(function ($special) use ($record, $isAmendment) {
                                return Grid::make(12)->schema([
                                    Placeholder::make("label_{$special->id}")
                                        ->label('')
                                        ->content($special->name)
                                        ->columnSpan(4),

                                    TextInput::make("services.special.{$special->id}.quantity")
                                        ->label('Quantity')
                                        ->numeric()
                                        ->columnSpan(2)
                                        ->reactive()
                                        ->disabled(
                                            fn(Forms\Get $get) =>
                                            $get("services.special.{$special->id}.is_unlimited") === true ||
                                                $get('endorsement_type') === 'RENEWAL' ||
                                                !$isAmendment($get)
                                        )
                                        ->dehydrated(true)
                                        ->formatStateUsing(function ($state, $record) use ($special) {
                                            if (! $record) {
                                                return $state;
                                            }
                                            return $record->services()
                                                ->where('service_id', $special->id)
                                                ->value('quantity');
                                        }),

                                    TextInput::make("services.special.{$special->id}.remarks")
                                        ->label('Remarks')
                                        ->columnSpan(4)
                                        ->maxLength(255)
                                        ->disabled(
                                            fn(Forms\Get $get) => $get('endorsement_type') === 'RENEWAL' || !$isAmendment($get)
                                        )
                                        ->formatStateUsing(function ($state, $record) use ($special) {
                                            if (! $record) {
                                                return $state;
                                            }
                                            return $record->services()
                                                ->where('service_id', $special->id)
                                                ->value('remarks');
                                        }),

                                    Forms\Components\Hidden::make("services.special.{$special->id}.default_quantity")
                                        ->default(fn(Forms\Get $get) => $get("services.special.{$special->id}.quantity"))
                                        ->formatStateUsing(function ($state, $record) use ($special) {
                                            if (! $record) {
                                                return $state;
                                            }
                                            return $record->services()
                                                ->where('service_id', $special->id)
                                                ->value('default_quantity') ?? $record->services()
                                                ->where('service_id', $special->id)
                                                ->value('quantity');
                                        }),

                                    Toggle::make("services.special.{$special->id}.is_unlimited")
                                        ->label('Unlimited')
                                        ->columnSpan(2)
                                        ->inline(false)
                                        ->reactive()
                                        ->default(false)
                                        ->disabled(
                                            fn(Forms\Get $get) => $get('endorsement_type') === 'RENEWAL' || !$isAmendment($get)
                                        )
                                        ->afterStateUpdated(function ($state, Forms\Set $set) use ($special) {
                                            if ($state === true) {
                                                $set("services.special.{$special->id}.quantity", null);
                                            }
                                        })
                                        ->formatStateUsing(function ($state, $record) use ($special) {
                                            if (! $record) {
                                                return false;
                                            }
                                            return $record->services()
                                                ->where('service_id', $special->id)
                                                ->value('is_unlimited') ?? false;
                                        }),
                                ])->columns(12);
                            })->toArray();
                        }),
                ];
            });
    }



    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('endorsement_type')
                    ->label('Endorsement Type')
                    ->badge()
                    ->colors([
                        'success' => fn($state) => $state === 'NEW',
                        'warning' => fn($state) => $state === 'RENEWAL',
                        'info'    => fn($state) => $state === 'AMENDMENT',
                    ]),

                TextColumn::make('endorsement_status')
                    ->label('Endorsement Status')
                    ->badge()
                    ->formatStateUsing(fn($state) => match ($state) {
                        'PENDING' => 'Pending',
                        'APPROVED' => 'Approved',
                        'REJECTED' => 'Rejected',
                        default => $state,
                    })
                    ->colors([
                        'warning' => fn($state) => $state === 'PENDING',
                        'success' => fn($state) => $state === 'APPROVED',
                        'danger' => fn($state) => $state === 'REJECTED',
                    ]),

                TextColumn::make('account_status')
                    ->label('Account Status')
                    ->badge()
                    ->formatStateUsing(fn($state) => ucfirst($state))
                    ->colors([
                        'warning' => 'inactive',
                        'success'   => 'active',
                        'danger'    => 'expired',
                    ]),

                TextColumn::make('plan_type')
                    ->label('Plan Type')
                    ->badge()
                    ->formatStateUsing(fn($state) => ucfirst($state))
                    ->colors([
                        'warning' => 'SHARED',
                        'info'   => 'INDIVIDUAL',
                    ]),

                TextColumn::make('effective_date')
                    ->label('Effective')
                    ->date(),

                TextColumn::make('expiration_date')
                    ->label('Expiration')
                    ->date(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->persistFiltersInSession()
            ->filters([
                SelectFilter::make('endorsement_type')
                    ->label('Endorsement Type')
                    ->multiple()
                    ->options(
                        EndorsementType::pluck('name', 'name')->toArray()
                    ),

                SelectFilter::make('endorsement_status')
                    ->label('Endorsement Status')
                    ->multiple()
                    ->options([
                        'PENDING' => 'Pending',
                        'APPROVED' => 'Approved',
                        'REJECTED' => 'Rejected',
                    ]),

                SelectFilter::make('account_status')
                    ->label('Account Status')
                    ->multiple()
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'expired' => 'Expired',
                    ]),

                SelectFilter::make('plan_type')
                    ->label('Plan Type')
                    ->options([
                        'INDIVIDUAL' => 'Individual',
                        'SHARED' => 'Shared',
                    ]),

                Filter::make('expiring_soon')
                    ->label('Expiring Soon (30 days)')
                    ->query(fn(Builder $query) => $query
                        ->where('account_status', 'active')
                        ->whereBetween('expiration_date', [now(), now()->addDays(30)])
                    )
                    ->toggle(),

                Filter::make('created_today')
                    ->label('Created Today')
                    ->query(fn(Builder $query) => $query->whereDate('created_at', today()))
                    ->toggle(),

                TrashedFilter::make(),
            ])

            ->headerActions([
                Action::make('importXls')
                    ->label('Import XLS')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->visible(fn() => auth()->user()->can('account.import'))
                    ->form([
                        FileUpload::make('file')
                            ->label('Upload Excel File')
                            ->acceptedFileTypes([
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->storeFileNamesIn('original_filename')
                            ->required(),
                        Toggle::make('migration_mode')
                            ->label('Migration Mode (Auto-approve accounts)')
                            ->helperText('Enable this only for initial data migration. Accounts will be set to ACTIVE and APPROVED.')
                            ->default(false)
                            ->visible(fn() => auth()->user()->can('account.import.migration-mode')),
                    ])
                    ->action(function (array $data): void {
                        $relativePath = $data['file'];
                        $disk = Storage::disk('public');
                        $absolutePath = $disk->path($relativePath);

                        if (!$disk->exists($relativePath)) {
                            throw new \Exception("File not found at: {$absolutePath}");
                        }

                        $originalFileName = $data['original_filename'] ?? pathinfo($data['file'], PATHINFO_BASENAME);


                        $migrationMode = $data['migration_mode'] ?? false;

                        $log = ImportLog::create([
                            'filename' => $originalFileName,
                            'disk' => 'public',
                            'status' => 'processing',
                            'user_id' => auth()->id(),
                            'import_type' => 'account',
                        ]);

                        $import = new AccountImport($log, auth()->id(), $migrationMode);
                        Excel::import($import, $absolutePath);

                        $message = "Accounts import completed! {$import->imported} imported.";
                        if (count($import->duplicates) > 0) {
                            $message .= ' ' . count($import->duplicates) . ' duplicates skipped.';
                        }
                        if (count($import->failed) > 0) {
                            $message .= ' ' . count($import->failed) . ' rows failed.';
                        }

                        Notification::make()
                            ->title($message)
                            ->success()
                            ->send();
                    }),

            ])
            ->actions([
                ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn() => auth()->user()?->hasAnyRole(Role::SUPER_ADMIN, Role::ACCOUNT_MANAGER)),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => auth()->user()?->hasAnyRole(Role::SUPER_ADMIN)),

                Tables\Actions\RestoreAction::make()
                    ->visible(fn() => auth()->user()?->hasAnyRole(Role::SUPER_ADMIN)),

                Tables\Actions\ForceDeleteAction::make()
                    ->visible(fn() => auth()->user()?->hasAnyRole(Role::SUPER_ADMIN)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Approve Selected Accounts')
                        ->modalDescription('This will set Endorsement Status to Approved and Account Status to Active for all selected accounts.')
                        ->visible(fn() => auth()->user()?->hasAnyRole(Role::SUPER_ADMIN, Role::UPPER_MANAGEMENT, Role::MIDDLE_MANAGEMENT))
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $eligible = $records->filter(fn($r) => $r->endorsement_type === 'NEW');

                            $eligible->each(fn($r) => $r->update([
                                'endorsement_status' => 'APPROVED',
                                'account_status'     => 'active',
                            ]));

                            $skipped = $records->count() - $eligible->count();

                            Notification::make()
                                ->title('Accounts Approved')
                                ->body($eligible->count() . ' account(s) approved.' . ($skipped > 0 ? " {$skipped} skipped (Renewal/Amendment must be approved individually)." : ''))
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()?->hasRole(Role::SUPER_ADMIN)),
                ]),
            ]);
    }


    public static function getNavigationBadge(): ?string
    {
        if (! auth()->user()?->can('account.approve')) {
            return null;
        }

        $pendingCount = Account::where('endorsement_status', 'PENDING')->count();

        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('account.view');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('account.create');
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->can('account.update');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->can('account.delete');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check()
            && auth()->user()->can('account.view');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAccounts::route('/'),
            'create' => Pages\CreateAccount::route('/create'),
            'edit'   => Pages\EditAccount::route('/{record}/edit'),
            'view'   => Pages\ViewAccount::route('/{record}'),
        ];
    }
}
