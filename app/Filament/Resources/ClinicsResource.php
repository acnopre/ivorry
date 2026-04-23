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
use Filament\Forms\Components\Tabs;
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
use App\Models\ClinicServiceFeeHistory;
use App\Imports\ClinicImport;
use App\Models\Account;
use App\Models\Hip;
use App\Models\Role;
use App\Models\UpdateInfo1903Types;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\VatType;
use App\Models\ImportLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;

class ClinicsResource extends Resource
{
    protected static ?string $model = Clinic::class;

    protected static ?string $navigationGroup = 'Dental Management';
    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $navigationLabel = 'Clinic Details';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('clinic_tabs')->tabs([

                Tabs\Tab::make('Clinic Info')
                    ->icon('heroicon-o-building-storefront')
                    ->schema([
                        Section::make('Basic Information')
                            ->icon('heroicon-o-information-circle')
                            ->columns(2)
                            ->schema([
                                TextInput::make('clinic_name')
                                    ->label('Name on Signage')
                                    ->required()
                                    ->columnSpanFull(),

                                TextInput::make('clinic_landline')->label('Landline'),
                                TextInput::make('clinic_mobile')->label('Mobile'),
                                TextInput::make('viber_no')->label('Viber No.'),
                                TextInput::make('clinic_email')->label('Email')->email(),
                                Textarea::make('alt_address')
                                    ->label('Alternative Address')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Address')
                            ->icon('heroicon-o-map-pin')
                            ->columns(2)
                            ->schema([
                                Forms\Components\Select::make('region_id')
                                    ->label('Region')
                                    ->options(\App\Models\Region::pluck('name', 'id'))
                                    ->searchable()
                                    ->reactive()
                                    ->afterStateUpdated(fn($state, callable $set) => $set('province_id', null))
                                    ->required(),

                                Forms\Components\Select::make('province_id')
                                    ->label('Province')
                                    ->options(fn(callable $get) => $get('region_id')
                                        ? \App\Models\Province::where('region_id', $get('region_id'))->pluck('name', 'id')
                                        : collect())
                                    ->searchable()
                                    ->reactive()
                                    ->afterStateUpdated(fn($state, callable $set) => $set('municipality_id', null))
                                    ->required(),

                                Forms\Components\Select::make('municipality_id')
                                    ->label('City / Municipality')
                                    ->options(fn(callable $get) => $get('province_id')
                                        ? \App\Models\Municipality::where('province_id', $get('province_id'))->pluck('name', 'id')
                                        : collect())
                                    ->searchable()
                                    ->reactive()
                                    ->afterStateUpdated(fn($state, callable $set) => $set('barangay_id', null))
                                    ->required(),

                                Forms\Components\Select::make('barangay_id')
                                    ->label('Barangay')
                                    ->options(fn(callable $get) => $get('municipality_id')
                                        ? \App\Models\Barangay::where('municipality_id', $get('municipality_id'))->pluck('name', 'id')
                                        : collect())
                                    ->searchable()
                                    ->required(),

                                TextInput::make('street')
                                    ->label('Street / House No.')
                                    ->placeholder('e.g., 123 Mabini St.')
                                    ->required()
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Clinic Staff')
                            ->icon('heroicon-o-user-group')
                            ->columns(2)
                            ->collapsed()
                            ->schema([
                                TextInput::make('clinic_staff_name')->label('Staff Name'),
                                TextInput::make('clinic_staff_mobile')->label('Mobile'),
                                TextInput::make('clinic_staff_viber')->label('Viber'),
                                TextInput::make('clinic_staff_email')->label('Email')->email(),
                            ]),
                    ]),

                Tabs\Tab::make('Accreditation & Tax')
                    ->icon('heroicon-o-document-check')
                    ->schema([
                        Section::make('BIR & Tax Information')
                            ->icon('heroicon-o-receipt-percent')
                            ->columns(2)
                            ->schema([
                                TextInput::make('registered_name')
                                    ->label('Registered Name (per BIR 2303)')
                                    ->required()
                                    ->columnSpanFull(),

                                TextInput::make('tax_identification_no')
                                    ->label('TIN')
                                    ->required(),

                                Forms\Components\Toggle::make('is_branch')
                                    ->label('Is this a Branch?')
                                    ->inline(false),

                                Textarea::make('complete_address')
                                    ->label('Complete Address')
                                    ->rows(2)
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('update_info_1903')
                                    ->label('Update Information (BIR Form 2303)')
                                    ->options(UpdateInfo1903Types::pluck('name', 'name'))
                                    ->placeholder('Select update type'),

                                Forms\Components\Select::make('vat_type')
                                    ->label('VAT Type')
                                    ->options(VatType::pluck('name', 'name'))
                                    ->searchable()
                                    ->required(),

                                Forms\Components\Select::make('withholding_tax')
                                    ->label('Withholding Tax')
                                    ->options(TaxType::pluck('name', 'name'))
                                    ->searchable()
                                    ->required(),

                                Forms\Components\Select::make('business_type')
                                    ->label('Business Type')
                                    ->options(BusinessType::pluck('name', 'name'))
                                    ->searchable()
                                    ->required(),

                                TextInput::make('sec_registration_no')
                                    ->label('SEC Registration No.'),
                            ]),

                        Section::make('PTR Information')
                            ->icon('heroicon-o-identification')
                            ->columns(2)
                            ->schema([
                                TextInput::make('ptr_no')->label('PTR No.'),
                                DatePicker::make('ptr_date_issued')->label('Date Issued'),
                            ]),

                        Section::make('Accreditation Status')
                            ->icon('heroicon-o-shield-check')
                            ->columns(2)
                            ->schema([
                                Forms\Components\Select::make('accreditation_status')
                                    ->label('Accreditation Status')
                                    ->options(AccreditationStatus::pluck('name', 'name'))
                                    ->searchable()
                                    ->reactive()
                                    ->required(),

                                Select::make('hip_id')
                                    ->label('HIP')
                                    ->options(Hip::pluck('name', 'id'))
                                    ->searchable()
                                    ->visible(fn($get) => $get('accreditation_status') === 'SPECIFIC HIP'),

                                Select::make('account_id')
                                    ->label('Account')
                                    ->options(Account::pluck('company_name', 'id'))
                                    ->searchable()
                                    ->visible(fn($get) => $get('accreditation_status') === 'SPECIFIC ACCOUNT'),
                            ]),
                    ]),

                Tabs\Tab::make('Bank & Dentists')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Section::make('Bank Information')
                            ->icon('heroicon-o-building-library')
                            ->columns(2)
                            ->schema([
                                TextInput::make('bank_account_name')->label('Account Name'),
                                TextInput::make('bank_account_number')->label('Account Number'),
                                TextInput::make('bank_name')->label('Bank Name'),
                                TextInput::make('bank_branch')->label('Branch'),
                                Forms\Components\Select::make('account_type')
                                    ->label('Account Type')
                                    ->options(AccountType::pluck('name', 'name'))
                                    ->searchable()
                                    ->required(),
                                Textarea::make('remarks')
                                    ->label('Remarks')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Associate Dentists')
                            ->icon('heroicon-o-user-circle')
                            ->schema([
                                Forms\Components\Repeater::make('dentists')
                                    ->relationship('dentists')
                                    ->schema([
                                        TextInput::make('last_name')->label('Last Name')->required(),
                                        TextInput::make('first_name')->label('Given Name')->required(),
                                        TextInput::make('middle_initial')->label('M.I.')->maxLength(1),
                                        TextInput::make('prc_license_number')->label('PRC Lic #'),
                                        DatePicker::make('prc_expiration_date')->label('Expiry Date'),
                                        Forms\Components\Toggle::make('is_owner')
                                            ->label('Clinic Owner')
                                            ->inline(false)
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                if ($state) {
                                                    $dentists = $get('../../dentists') ?? [];
                                                    $currentItem = $get();
                                                    $currentIndex = collect($dentists)->search(
                                                        fn($d) => $d['last_name'] === $currentItem['last_name'] && $d['first_name'] === $currentItem['first_name']
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
                    ]),

                Tabs\Tab::make('Service Fees')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Section::make('Basic Dental Services')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->schema(function ($record) {
                                if (!$record) return [Placeholder::make('no_record')->label('')->content('Save the clinic first to manage fees.')];
                                return self::buildServiceFeeRows(Service::where('type', 'basic')->get(), $record, 'basic');
                            }),

                        Section::make('Plan Enhancements')
                            ->icon('heroicon-o-plus-circle')
                            ->schema(function ($record) {
                                if (!$record) return [];
                                return self::buildServiceFeeRows(Service::where('type', 'enhancement')->get(), $record, 'enhancement');
                            }),

                        Section::make('Special Procedures')
                            ->icon('heroicon-o-star')
                            ->schema(function ($record) {
                                if (!$record) return [];
                                return self::buildServiceFeeRows(Service::where('type', 'special')->get(), $record, 'special');
                            }),

                        Section::make('Fee History')
                            ->icon('heroicon-o-clock')
                            ->collapsed()
                            ->visible(fn($record) => $record !== null)
                            ->schema(function ($record) {
                                if (!$record) return [];

                                $histories = ClinicServiceFeeHistory::with(['service', 'approvedBy'])
                                    ->where('clinic_id', $record->id)
                                    ->orderByDesc('effective_date')
                                    ->orderByDesc('created_at')
                                    ->get();

                                if ($histories->isEmpty()) {
                                    return [Placeholder::make('no_history')->label('')->content('No fee history recorded yet.')];
                                }

                                $rows = $histories->map(function ($h) {
                                    $old      = $h->old_fee !== null ? '&#8369;' . number_format($h->old_fee, 2) : '&mdash;';
                                    $new      = '&#8369;' . number_format($h->new_fee, 2);
                                    $date     = \Carbon\Carbon::parse($h->effective_date)->format('M d, Y');
                                    $approver = e($h->approvedBy?->name ?? '&mdash;');
                                    $service  = e($h->service?->name ?? '&mdash;');
                                    return "<tr class='border-b border-gray-100 dark:border-white/5'>"
                                        . "<td class='px-3 py-2'>{$service}</td>"
                                        . "<td class='px-3 py-2 text-right text-gray-400'>{$old}</td>"
                                        . "<td class='px-3 py-2 text-right font-medium'>{$new}</td>"
                                        . "<td class='px-3 py-2'>{$date}</td>"
                                        . "<td class='px-3 py-2 text-gray-500'>{$approver}</td>"
                                        . '</tr>';
                                })->join('');

                                $html = '<div class="overflow-x-auto"><table class="w-full text-xs">'
                                    . '<thead><tr class="border-b border-gray-200 dark:border-white/10 text-gray-500">'
                                    . '<th class="px-3 py-2 text-left">Service</th>'
                                    . '<th class="px-3 py-2 text-right">Old Fee</th>'
                                    . '<th class="px-3 py-2 text-right">New Fee</th>'
                                    . '<th class="px-3 py-2 text-left">Effective Date</th>'
                                    . '<th class="px-3 py-2 text-left">Approved By</th>'
                                    . '</tr></thead><tbody>' . $rows . '</tbody></table></div>';

                                return [
                                    Placeholder::make('fee_history_table')
                                        ->label('')
                                        ->content(new \Illuminate\Support\HtmlString($html)),
                                ];
                            }),
                    ]),

            ])->columnSpanFull(),
        ]);
    }

    private static function buildServiceFeeRows($services, $record, string $type): array
    {
        return $services->map(function ($service) use ($record, $type) {
            return Grid::make(12)->schema([
                Placeholder::make("label_{$type}_{$service->id}")
                    ->label('')
                    ->content($service->name)
                    ->columnSpan(4),

                TextInput::make("services.{$type}.{$service->id}")
                    ->label('Current Fee')
                    ->numeric()
                    ->prefix('₱')
                    ->disabled()
                    ->columnSpan(3)
                    ->formatStateUsing(function ($state, $record) use ($service) {
                        return $record ? $record->services()->where('service_id', $service->id)->value('fee') : $state;
                    }),

                Forms\Components\View::make("fee_status_{$type}_{$service->id}")
                    ->view('filament.components.fee-status-badge')
                    ->viewData(function () use ($record, $service) {
                        $pivot = $record->services()->where('service_id', $service->id)->first()?->pivot;
                        return [
                            'new_fee'        => $pivot?->new_fee,
                            'effective_date' => $pivot?->effective_date,
                            'approved_at'    => $pivot?->approved_at,
                        ];
                    })
                    ->columnSpan(3),

                Forms\Components\Actions::make([
                    Forms\Components\Actions\Action::make("request_fee_{$type}_{$service->id}")
                        ->label('Request Update')
                        ->icon('heroicon-o-pencil-square')
                        ->color('gray')
                        ->size('sm')
                        ->modalHeading('Request Fee Update — ' . $service->name)
                        ->modalWidth('sm')
                        ->form([
                            TextInput::make('new_fee')
                                ->label('New Fee')
                                ->numeric()
                                ->required()
                                ->prefix('₱'),
                            DatePicker::make('effective_date')
                                ->label('Effective Date')
                                ->required()
                                ->default(now()->toDateString()),
                        ])
                        ->action(function (array $data) use ($record, $service) {
                            $record->services()->updateExistingPivot($service->id, [
                                'new_fee'        => $data['new_fee'],
                                'effective_date' => $data['effective_date'],
                            ]);
                            $record->update(['fee_approval' => 'pending']);
                            $approvalUrl = \App\Filament\Pages\ServiceFeeApproval::getUrl();
                            foreach (\App\Models\User::permission('fee.approval')->get() as $approver) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Fee Update Requested')
                                    ->body($record->clinic_name . ' — ' . $service->name . ': ₱' . number_format($data['new_fee'], 2) . ' effective ' . \Carbon\Carbon::parse($data['effective_date'])->format('M d, Y'))
                                    ->warning()
                                    ->actions([\Filament\Notifications\Actions\Action::make('view')->label('Review')->url($approvalUrl)])
                                    ->sendToDatabase($approver);
                            }
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Fee update requested')
                                ->body($service->name . ' fee update submitted for approval.')
                                ->send();
                        }),
                ])->columnSpan(2),
            ]);
        })->toArray();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('clinic_name')
                    ->label('Clinic Name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('complete_address')
                    ->label('Address')
                    ->limit(40)
                    ->toggleable(),

                TextColumn::make('clinic_email')
                    ->label('Email')
                    ->toggleable(),

                TextColumn::make('accreditation_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn($state) => match($state) {
                        'ACTIVE'           => 'success',
                        'INACTIVE'         => 'danger',
                        'SILENT'           => 'warning',
                        'SPECIFIC ACCOUNT' => 'info',
                        'SPECIFIC HIP'     => 'primary',
                        default            => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->date('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('accreditation_status')
                    ->label('Accreditation Status')
                    ->options([
                        'ACTIVE'           => 'Active',
                        'INACTIVE'         => 'Inactive',
                        'SILENT'           => 'Silent',
                        'SPECIFIC ACCOUNT' => 'Specific Account',
                        'SPECIFIC HIP'     => 'Specific HIP',
                    ]),
            ])
            ->headerActions([
                Action::make('importXls')
                    ->label('Import XLS')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->visible(fn() => auth()->user()->can('clinic.import'))
                    ->form([
                        Forms\Components\FileUpload::make('file')
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
                        $disk         = Storage::disk('public');
                        $absolutePath = $disk->path($relativePath);

                        if (!$disk->exists($relativePath)) {
                            throw new \Exception("File not found at: {$absolutePath}");
                        }

                        $filename = $data['original_filename'] ?? basename($relativePath);
                        $log = ImportLog::create([
                            'filename'    => $filename,
                            'disk'        => 'public',
                            'status'      => 'processing',
                            'user_id'     => auth()->id(),
                            'import_type' => 'clinic',
                        ]);

                        \App\Jobs\ProcessClinicImport::dispatch($absolutePath, $log->id);

                        Notification::make()
                            ->title('Import queued!')
                            ->body('Your clinic import is being processed. Check Import Logs for progress.')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->visible(auth()->user()->can('clinic.view')),
                Tables\Actions\EditAction::make()->visible(auth()->user()->can('clinic.update')),
                Tables\Actions\DeleteAction::make()->visible(auth()->user()->can('clinic.delete')),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function canViewAny(): bool  { return auth()->user()->can('clinic.view'); }
    public static function canCreate(): bool   { return auth()->user()->can('clinic.create'); }
    public static function canEdit(Model $record): bool   { return auth()->user()->can('clinic.update'); }
    public static function canDelete(Model $record): bool { return auth()->user()->can('clinic.delete'); }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->can('clinic.view');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListClinics::route('/'),
            'create' => Pages\CreateClinics::route('/create'),
            'edit'   => Pages\EditClinics::route('/{record}/edit'),
        ];
    }
}
