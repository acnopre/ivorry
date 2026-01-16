<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountResource\Pages;
use App\Imports\AccountImport;
use App\Models\Account;
use App\Models\AccountAmendment;
use App\Models\AccountService;
use App\Models\EndorsementType;
use App\Models\ImportLog;
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
                                ->unique(ignoreRecord: true)
                                ->required()
                                ->maxLength(50)
                                ->disabled(fn(Forms\Get $get) => ! $isAmendment($get)),

                            TextInput::make('hip')
                                ->label('HIP')
                                ->maxLength(255)
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
                                        ->label('Birthdate'),

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
                                ->reactive() // Make sure it's reactive
                                ->disabled(fn(Forms\Get $get) => !($isAmendment($get) || $get('endorsement_type') === 'RENEWAL'))
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                        // $state is already a date string in 'Y-m-d', so just parse it
                                        $expiration = Carbon::parse($state)->addYear();
                                        $set('expiration_date', $expiration->format('Y-m-d'));
                                    }
                                }),

                            DatePicker::make('expiration_date')
                                ->label('Expiration Date')
                                ->reactive() // Make it reactive
                                ->disabled(fn(Forms\Get $get) => !($isAmendment($get) || $get('endorsement_type') === 'RENEWAL')),

                            Select::make('endorsement_type')
                                ->label('Endorsement Type')
                                ->visible($record?->account_status == 'active')
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
                                })

                                ->disabled(false),
                        ])->columns(3),

                    Section::make('Basic Dental Services')
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
                                        ->reactive()
                                        ->disabled(function (Forms\Get $get) use ($isAmendment, $service) {
                                            // Always disable if unlimited
                                            if ($get("services.basic.{$service->id}.is_unlimited") === true) {
                                                return true;
                                            }

                                            // Otherwise, only enable for amendment or renewal
                                            return !($isAmendment($get) || $get('endorsement_type') === 'RENEWAL');
                                        })
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
                                            fn(Forms\Get $get) => ! ($isAmendment($get) || $get('endorsement_type') === 'RENEWAL')
                                        )
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
                                        ->default(true)   // ✅ New accounts default to TRUE
                                        ->disabled(
                                            fn(Forms\Get $get) => ! ($isAmendment($get) || $get('endorsement_type') === 'RENEWAL')
                                        )
                                        ->afterStateUpdated(function ($state, Forms\Set $set) use ($service) {
                                            if ($state === true) {
                                                $set("services.basic.{$service->id}.quantity", null);
                                            }
                                        })
                                        ->formatStateUsing(function ($state, $record) use ($service) {

                                            // 🔹 If creating → always TRUE
                                            if (! $record) {
                                                return true;
                                            }

                                            // 🔹 If editing → load value from pivot table
                                            return $record->services()
                                                ->where('service_id', $service->id)
                                                ->value('is_unlimited');
                                        }),
                                ])->columns(12);
                            })->toArray();
                        }),

                    Section::make('Plan Enhancements')
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
                                                ! ($isAmendment($get) || $get('endorsement_type') === 'RENEWAL')
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
                                            fn(Forms\Get $get) => ! ($isAmendment($get) || $get('endorsement_type') === 'RENEWAL')
                                        )
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
                                        ->disabled(
                                            fn(Forms\Get $get) => ! ($isAmendment($get) || $get('endorsement_type') === 'RENEWAL')
                                        )
                                        ->afterStateUpdated(function ($state, Forms\Set $set) use ($enhancement) {
                                            if ($state === true) {
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
                ];
            });
    }



    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company_name')->label('Company Name')->sortable()->searchable(),

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

                TextColumn::make('effective_date')->label('Effective')->date(),
                TextColumn::make('expiration_date')->label('Expiration')->date(),
                TextColumn::make('created_at')->label('Created')->dateTime(),
            ])
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
                        'PENDING'   => 'PENDING',
                        'APPROVED'  => 'APPROVED',
                        'REJECTED'  => 'REJECTED',
                        'RETURNED'  => 'RETURNED',
                    ]),
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
                        // Get the original filename (or just use basename of relative path)
                        $filename = basename($relativePath);
                        $log = ImportLog::create([
                            'filename' => $filename,
                            'status' => 'processing',
                        ]);

                        Excel::import(new AccountImport($log), $absolutePath);
                    }),
            ])
            ->actions([
                ViewAction::make()
                    ->modalHeading('Account Details')
                    ->modalSubmitAction(false)
                    ->extraModalFooterActions([
                        // ✅ For regular NEW or AMENDMENT approvals
                        TableAction::make('approveAccount')
                            ->label('Approve Account')
                            ->color('success')
                            ->icon('heroicon-o-check-circle')
                            ->visible(
                                fn(Model $record) =>
                                in_array($record->endorsement_type, ['NEW', 'AMENDMENT'])
                            )
                            ->requiresConfirmation()
                            ->action(function (Model $record) {
                                $record->update(['account_status' => 'active']);
                                Notification::make()
                                    ->success()
                                    ->title('Account Approved')
                                    ->body('The account has been approved successfully.')
                                    ->send();
                            }),

                        // ✅ For Renewal approvals (separate button)
                        TableAction::make('approveRenewal')
                            ->label('Approve Renewal')
                            ->color('info')
                            ->visible(
                                fn(Model $record) =>
                                $record->endorsement_type === 'RENEWAL'
                                    && auth()->user()?->hasAnyRole([Role::UPPER_MANAGEMENT])
                            )
                            ->requiresConfirmation()
                            ->action(function (Model $record) {
                                // Create a renewal request
                                $renewal = \App\Models\AccountRenewal::create([
                                    'account_id' => $record->id,
                                    'effective_date' => $record->effective_date,
                                    'expiration_date' => $record->expiration_date,
                                    'requested_by' => auth()->id(),
                                    'status' => 'PENDING',
                                ]);

                                // Save all services as part of the renewal
                                foreach (['basic', 'enhancement'] as $type) {
                                    $services = $record->services()->whereHas('service', fn($q) => $q->where('type', $type))->get();

                                    foreach ($services as $service) {
                                        $renewal->services()->create([
                                            'service_id' => $service->id,
                                            'quantity' => $service->pivot->quantity,
                                            'is_unlimited' => $service->pivot->is_unlimited,
                                            'remarks' => $service->pivot->remarks,
                                        ]);
                                    }
                                }

                                Notification::make()
                                    ->success()
                                    ->title('Renewal Created')
                                    ->body('Renewal request has been saved and awaits approval.')
                                    ->send();
                            }),

                        TableAction::make('approveAmendment')
                            ->label('Approve Amendment')
                            ->visible(
                                fn(Model $record) =>
                                $record->endorsement_type === 'AMENDMENT'
                                    && auth()->user()?->hasAnyRole([Role::UPPER_MANAGEMENT])
                            )
                            ->action(function (Account $record) {

                                $amendment = AccountAmendment::where('account_id', $record->id)
                                    ->where('endorsement_status', 'PENDING')
                                    ->latest()
                                    ->first();

                                // Update main account
                                $record->update([
                                    'company_name'     => $amendment->company_name,
                                    'policy_code'      => $amendment->policy_code,
                                    'hip'              => $amendment->hip,
                                    'card_used'        => $amendment->card_used,
                                    'effective_date'   => $amendment->effective_date,
                                    'expiration_date'  => $amendment->expiration_date,
                                    'endorsement_type' => 'AMENDED',
                                    'endorsement_status' => 'APPROVED',
                                ]);

                                // Clear old services & apply amended services
                                $record->services()->delete();

                                foreach ($amendment->services as $srv) {
                                    $record->services()->create([
                                        'service_id'        => $srv->service_id,
                                        'quantity'          => $srv->quantity,
                                        'default_quantity'  => $srv->default_quantity,
                                        'is_unlimited'      => $srv->is_unlimited,
                                        'remarks'           => $srv->remarks,
                                    ]);
                                }

                                $amendment->update(['endorsement_status' => 'APPROVED']);
                            }),



                    ]),
                Tables\Actions\EditAction::make()
                    ->visible(fn() => auth()->user()?->hasAnyRole(Role::SUPER_ADMIN, Role::ACCOUNT_MANAGER)),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => auth()->user()?->hasAnyRole(Role::SUPER_ADMIN)),


            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    // public static function getWidgets(): array
    // {
    //     return [
    //         AccountStatsWidget::class
    //     ];
    // }

    public static function getNavigationBadge(): ?string
    {
        if (! auth()->user()?->hasAnyRole([Role::SUPER_ADMIN, Role::UPPER_MANAGEMENT])) {
            return null; // Do NOT show badge for other roles
        }
        // Count accounts where account_status = 0 (pending)
        $pendingCount = Account::where('endorsement_status', 'PENDING')->count();

        // Only show badge if there's at least one pending
        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }


    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check()
            && auth()->user()->hasAnyRole([Role::SUPER_ADMIN, Role::ACCOUNT_MANAGER, Role::UPPER_MANAGEMENT]);
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
