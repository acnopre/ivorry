<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MemberResource\Pages;
use App\Imports\MembersImport;
use App\Models\Account;
use App\Models\Member;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\{DatePicker, FileUpload, Grid, Section, Select, Textarea, TextInput, Toggle};
use Filament\Notifications\Notification;
use Filament\Tables\Columns\{TextColumn, BadgeColumn};
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;

class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static ?string $navigationGroup = 'Accounts & Members';
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?int $navigationSort = 2;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Section::make('Member Information')
                    ->schema([
                        Select::make('account_id')
                            ->label('Account')
                            ->relationship(
                                name: 'account',
                                modifyQueryUsing: fn($query) => $query->where('account_status', 'active')
                            )
                            ->getOptionLabelFromRecordUsing(fn(Account $record) => "{$record->company_name} ({$record->policy_code})")
                            ->required()
                            ->searchable(['company_name', 'policy_code'])
                            ->reactive()
                            ->afterStateUpdated(fn(callable $set) => $set('dependents', [])),

                        // === INDIVIDUAL FIELDS ===
                        Grid::make(2)
                            ->schema([
                                TextInput::make('card_number')
                                    ->label('Card Number')
                                    ->required(fn(callable $get) => ! $get('use_coc_number'))
                                    ->hidden(fn(callable $get) => $get('use_coc_number'))
                                    ->dehydrated(true)
                                    ->unique(
                                        table: 'members',
                                        column: 'card_number',
                                        ignoreRecord: true,
                                        modifyRuleUsing: fn($rule) => $rule->whereNull('deleted_at')
                                    ),

                                TextInput::make('coc_number')
                                    ->label('COC Number')
                                    ->required(fn(callable $get) => $get('use_coc_number'))
                                    ->hidden(fn(callable $get) => ! $get('use_coc_number'))
                                    ->dehydrated(true)
                                    ->unique(
                                        table: 'members',
                                        column: 'coc_number',
                                        ignoreRecord: true,
                                        modifyRuleUsing: fn($rule) => $rule->whereNull('deleted_at')
                                    ),

                                Toggle::make('use_coc_number')
                                    ->label('Enable COC Number')
                                    ->default(false)
                                    ->reactive()
                                    ->hidden(fn(callable $get) => filled($get('card_number')))
                                    ->dehydrated(false)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        if ($state && empty($get('coc_number'))) {
                                            $set('coc_number', Member::generateCocNumber());
                                        }
                                        if ($state) $set('card_number', null);
                                    })
                                    ->columnStart(2)
                                    ->inline(false),
                            ])
                            ->columnSpan(1)
                            ->visible(fn(callable $get) => static::getAccountPlanType($get('account_id')) !== 'SHARED'),

                        TextInput::make('first_name')->required(fn(callable $get) => static::getAccountPlanType($get('account_id')) !== 'SHARED')->maxLength(255)
                            ->visible(fn(callable $get) => static::getAccountPlanType($get('account_id')) !== 'SHARED'),
                        TextInput::make('last_name')->required(fn(callable $get) => static::getAccountPlanType($get('account_id')) !== 'SHARED')->maxLength(255)
                            ->visible(fn(callable $get) => static::getAccountPlanType($get('account_id')) !== 'SHARED'),
                        TextInput::make('middle_name')->maxLength(255)
                            ->visible(fn(callable $get) => static::getAccountPlanType($get('account_id')) !== 'SHARED'),
                        TextInput::make('suffix')->maxLength(255)
                            ->visible(fn(callable $get) => static::getAccountPlanType($get('account_id')) !== 'SHARED'),

                        Select::make('member_type')
                            ->options([
                                'PRINCIPAL' => 'Principal',
                                'DEPENDENT' => 'Dependent',
                            ])
                            ->required(fn(callable $get) => static::getAccountPlanType($get('account_id')) !== 'SHARED')
                            ->visible(fn(callable $get) => static::getAccountPlanType($get('account_id')) !== 'SHARED'),

                        Select::make('status')
                            ->options([
                                'ACTIVE' => 'Active',
                                'INACTIVE' => 'Inactive',
                            ])
                            ->default('ACTIVE')
                            ->required(fn(callable $get) => static::getAccountPlanType($get('account_id')) !== 'SHARED')
                            ->reactive()
                            ->visible(fn(callable $get) => static::getAccountPlanType($get('account_id')) !== 'SHARED'),

                        DatePicker::make('inactive_date')
                            ->label('Inactive Date')
                            ->visible(fn(callable $get) => $get('status') === 'INACTIVE' && static::getAccountPlanType($get('account_id')) !== 'SHARED'),

                        DatePicker::make('birthdate')
                            ->visible(fn(callable $get) => static::getAccountPlanType($get('account_id')) !== 'SHARED'),

                        Select::make('gender')
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female',
                            ])
                            ->native(false)
                            ->visible(fn(callable $get) => static::getAccountPlanType($get('account_id')) !== 'SHARED'),
                    ])->columns(2),

                // === SHARED: Principal Section ===
                Section::make('Principal Member')
                    ->schema([
                        TextInput::make('shared_card_number')
                            ->label('Card Number')
                            ->required(fn(callable $get) => static::getAccountPlanType($get('account_id')) === 'SHARED'),
                        Grid::make(2)->schema([
                            TextInput::make('principal_first_name')->label('First Name')->required(fn(callable $get) => static::getAccountPlanType($get('account_id')) === 'SHARED'),
                            TextInput::make('principal_last_name')->label('Last Name')->required(fn(callable $get) => static::getAccountPlanType($get('account_id')) === 'SHARED'),
                            TextInput::make('principal_middle_name')->label('Middle Name'),
                            TextInput::make('principal_suffix')->label('Suffix'),
                            DatePicker::make('principal_birthdate')->label('Birthdate')->native(false),
                            Select::make('principal_gender')->label('Gender')
                                ->options(['male' => 'Male', 'female' => 'Female'])->native(false),
                            TextInput::make('principal_email')->label('Email')->email(),
                            TextInput::make('principal_phone')->label('Phone')->tel(),
                        ]),
                    ])
                    ->visible(fn(callable $get) => static::getAccountPlanType($get('account_id')) === 'SHARED'),

                // === SHARED: Dependents Section ===
                Section::make('Dependents')
                    ->schema([
                        Forms\Components\Repeater::make('dependents')
                            ->label('')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('first_name')->label('First Name')->required(),
                                    TextInput::make('last_name')->label('Last Name')->required(),
                                    TextInput::make('middle_name')->label('Middle Name'),
                                    TextInput::make('suffix')->label('Suffix'),
                                    DatePicker::make('birthdate')->label('Birthdate')->native(false),
                                    Select::make('gender')->label('Gender')
                                        ->options(['male' => 'Male', 'female' => 'Female'])->native(false),
                                    TextInput::make('email')->label('Email')->email(),
                                    TextInput::make('phone')->label('Phone')->tel(),
                                ]),
                            ])
                            ->collapsible()
                            ->itemLabel(fn(array $state) => ($state['first_name'] ?? '') . ' ' . ($state['last_name'] ?? '') ?: 'New Dependent')
                            ->addActionLabel('Add Dependent'),
                    ])
                    ->visible(fn(callable $get) => static::getAccountPlanType($get('account_id')) === 'SHARED'),

                // === Contact Details (INDIVIDUAL only) ===
                Section::make('Contact Details')
                    ->schema([
                        TextInput::make('email')->email(),
                        TextInput::make('phone')->tel(),
                    ])->columns(2)
                    ->visible(fn(callable $get) => static::getAccountPlanType($get('account_id')) !== 'SHARED'),

                // === Contract Information (MEMBER coverage period, INDIVIDUAL only) ===
                Section::make('Contract Information')
                    ->schema([
                        DatePicker::make('effective_date')
                            ->label('Effective Date')
                            ->reactive()
                            ->native(false)
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $set(
                                        'expiration_date',
                                        \Carbon\Carbon::parse($state)->addYear()->subDay()->format('Y-m-d')
                                    );
                                }
                            }),

                        DatePicker::make('expiration_date')
                            ->label('Expiration Date')
                            ->native(false),
                    ])
                    ->columns(2)
                    ->visible(
                        fn(callable $get) =>
                        static::getAccountPlanType($get('account_id')) !== 'SHARED'
                            && filled($get('account_id'))
                            && optional(Account::find($get('account_id')))->coverage_period_type === 'MEMBER'
                    ),
            ]);
    }

    public static function getAccountPlanType(?string $accountId): ?string
    {
        if (! $accountId) return null;
        return Account::where('id', $accountId)->value('plan_type');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('account.policy_code')
                    ->label('Policy Code')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('account.company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('full_name')
                    ->label('Full Name')
                    ->getStateUsing(fn($record) => "{$record->first_name} {$record->last_name}")
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(
                        query: fn($query, $direction) =>
                        $query->orderByRaw("CONCAT(first_name, ' ', last_name) {$direction}")
                    ),

                TextColumn::make('card_number')
                    ->label('Card Number')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('member_type')
                    ->label('Member Type')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'success' => fn($state) => $state === 'active',
                        'warning' => fn($state) => $state === 'inactive',
                    ])
                    ->formatStateUsing(fn($state) => ucfirst($state)),

                TextColumn::make('inactive_date')
                    ->date()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->date()
                    ->label('Created At'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),

                TrashedFilter::make(),
            ])
            ->deferLoading()
            ->defaultPaginationPageOption(25)
            ->headerActions([
                Action::make('importXls')
                    ->label('Import XLS')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->visible(auth()->user()->can('member.import'))
                    ->color('success')
                    ->form([
                        FileUpload::make('file')
                            ->label('Upload Excel File')
                            ->acceptedFileTypes([
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->storeFileNamesIn('original_filename')
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $relativePath = $data['file'];
                        $disk = Storage::disk('public');
                        $absolutePath = $disk->path($relativePath);

                        if (!$disk->exists($relativePath)) {
                            throw new \Exception("File not found at: {$absolutePath}");
                        }

                        $originalFileName = $data['original_filename'] ?? pathinfo($data['file'], PATHINFO_BASENAME);

                        $import = new MembersImport($originalFileName);
                        Excel::import($import, $absolutePath);

                        $message = "Members import completed! {$import->imported} imported.";
                        if (count($import->duplicates) > 0) {
                            $message .= " " . count($import->duplicates) . " duplicates skipped.";
                        }
                        if (count($import->failed) > 0) {
                            $message .= " " . count($import->failed) . " rows failed.";
                        }

                        Notification::make()
                            ->title($message)
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('member.view');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('member.create');
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->can('member.update');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->can('member.delete');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check()
            && auth()->user()->can('member.view');
    }

    public static function getRelations(): array
    {
        return [
            MemberResource\RelationManagers\ProceduresRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMembers::route('/'),
            'create' => Pages\CreateMember::route('/create'),
            'edit' => Pages\EditMember::route('/{record}/edit'),
            'view' => Pages\ViewMember::route('/{record}'),
        ];
    }
}
