<?php

namespace App\Filament\Pages;

use App\Models\AccountService;
use App\Models\Clinic;
use App\Models\ClinicService;
use App\Models\Member;
use App\Models\Procedure;
use App\Models\ProcedureSurface;
use App\Models\ProcedureUnit;
use App\Models\Role;
use App\Models\Service;
use App\Models\Surface;
use App\Models\Unit;
use App\Models\UnitType;
use Filament\Forms;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class SearchMember extends Page
{
    protected static ?string $title = 'Search Member';
    protected static string $view = 'filament.pages.search-member';
    protected static ?string $navigationIcon = 'heroicon-o-users';

    public ?string $card_number = null;
    public ?string $first_name = null;
    public ?string $last_name = null;

    public Collection $members;

    public bool $showProcedureModal = false;
    public ?int $selectedMemberId = null;
    public array $procedureFormData = [];
    public bool $hasSearched = false;

    // 🆕 Modal state for approval confirmation
    public bool $showApprovalModal = false;
    public bool $showProcedureExistModal = false;
    public ?string $approvalCode = null;

    public function mount(): void
    {
        $this->members = collect();
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Search Members')
                ->schema([
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\TextInput::make('card_number')
                            ->label('Card Number')
                            ->placeholder('Enter Card Number'),

                        Forms\Components\TextInput::make('first_name')
                            ->label('First Name')
                            ->placeholder('Enter First Name'),

                        Forms\Components\TextInput::make('last_name')
                            ->label('Last Name')
                            ->placeholder('Enter Last Name'),
                    ]),
                ])
                ->footerActions([
                    Forms\Components\Actions\Action::make('search_action')
                        ->label('Search Members')
                        ->icon('heroicon-o-magnifying-glass')
                        ->color('primary')
                        ->action('search'),
                ]),
        ];
    }

    public function search(): void
    {
        if (! $this->card_number && ! $this->first_name && ! $this->last_name) {
            $this->members = collect();
            $this->hasSearched = false;
            $this->dispatch('open-notification', [
                'title' => 'No Filters Applied',
                'body' => 'Please enter at least one search filter before searching.',
                'type' => 'warning',
            ]);
            return;
        }

        $query = Member::query()
            ->whereHas('account', fn($q) => $q->where('account_status', 'active'))
            ->when($this->card_number, fn($q) => $q->where('card_number', 'like', "%{$this->card_number}%"))
            ->when($this->first_name, fn($q) => $q->where('first_name', 'like', "%{$this->first_name}%"))
            ->when($this->last_name, fn($q) => $q->where('last_name', 'like', "%{$this->last_name}%"));

        // Check if current user's clinic is 'SPECIFIC ACCOUNT'
        $clinic = Clinic::where('user_id', Auth::id())->first();
        if ($clinic && $clinic->accreditation_status === 'SPECIFIC ACCOUNT') {
            // Restrict members to this clinic's account_id
            $query->where('account_id', $clinic->account_id);
        }

        $this->members = $query->get();
        $this->hasSearched = true;
    }


    public function openProcedureModal(int $memberId): void
    {
        $this->selectedMemberId = $memberId;
        $this->procedureFormData = [
            'availment_date_display' => now()->format('F j, Y'),
            'availment_date' => now()->format('Y-m-d'),
            // 'quantity' => '1',
            'surface' => [],
            'quadrant' => [],
            'tooth' => [],
            'canal' => [],
            'arch' => [],
            'unit_id' => [],

        ];
        $this->showProcedureModal = true;
    }
    public function saveProcedure(): void
    {
        $data = $this->getProcedureForm()->getState();
        $clinicId = Auth::user()->clinic->id ?? null;
        $member = Member::where('id', $this->selectedMemberId)->first();
        $isServiceUnlimited = $member->account->services->find($data['service_id'])->pivot->is_unlimited;
        $serviceQuantity = $member->account->services->find($data['service_id'])->pivot->quantity;


        if (!$this->validateBusinessRules($data, $clinicId)) {
            return;
        }

        $approvalCode = strtoupper(Str::random(8));
        $appliedFee = ClinicService::where('clinic_id', $clinicId)
            ->where('service_id', $data['service_id'])
            ->value('fee') ?? 0;

        // Possible unit inputs
        $unitInputs = ['tooth', 'arch', 'quadrant', 'canal', 'surface'];
        $basicFields = ['service_id', 'quantity', 'availment_date'];
        $hasUnits = count(array_diff(array_keys($data), $basicFields)) > 0;
        if ($isServiceUnlimited) {
            $procedure = Procedure::create([
                'clinic_id'      => $clinicId,
                'member_id'      => $this->selectedMemberId,
                'service_id'     => $data['service_id'],
                'availment_date' => $data['availment_date'] ?? null,
                'status'         => Procedure::STATUS_PENDING,
                'quantity'       => 1,
                'approval_code'  => $approvalCode,
                'applied_fee'    => $appliedFee,
            ]);
        } else {
            if ($hasUnits) {
                foreach ($unitInputs as $input) {
                    if (! isset($data[$input])) {

                        continue;
                    }
                    foreach ($data[$input] as $value) {
                        $procedure = Procedure::create([
                            'clinic_id'      => $clinicId,
                            'member_id'      => $this->selectedMemberId,
                            'service_id'     => $data['service_id'],
                            'availment_date' => $data['availment_date'] ?? null,
                            'status'         => Procedure::STATUS_PENDING,
                            'quantity'       => $data['quantity'],
                            'approval_code'  => $approvalCode,
                            'applied_fee'    => $appliedFee,
                        ]);

                        ProcedureUnit::create([
                            'procedure_id'   => $procedure->id,
                            'unit_id'        => $input === 'surface' ? $data['tooth_surface'] : $value,
                            'quantity'       => 1,
                            'input_quantity' => $data['quantity'],
                            'surface_id'     => $input === 'surface' ? $value : null,
                        ]);
                    }
                }
            } else {
                $procedure = Procedure::create([
                    'clinic_id'      => $clinicId,
                    'member_id'      => $this->selectedMemberId,
                    'service_id'     => $data['service_id'],
                    'availment_date' => $data['availment_date'] ?? null,
                    'status'         => Procedure::STATUS_PENDING,
                    'quantity'       => 1,
                    'approval_code'  => $approvalCode,
                    'applied_fee'    => $appliedFee,
                ]);
            }
        }


        // UI updates
        $this->showProcedureModal = false;
        $this->approvalCode = $approvalCode;
        $this->showApprovalModal = true;

        $this->search();
    }


    private function validateBusinessRules($data, $clinicId): bool
    {
        $serviceName = \App\Models\Service::find($data['service_id'])->name;
        $availmentDate = $data['availment_date'];
        $memberId = $this->selectedMemberId;

        if (!$clinicId) {
            return $this->showError('Clinic not found', 'Please make sure you have a clinic assigned to your account.');
        }

        if (Procedure::where('service_id', $data['service_id'])
            ->where('member_id', $memberId)
            ->where('status', '!=', Procedure::STATUS_VALID)
            ->exists()
        ) {
            return $this->showError('Procedure Already Exist', 'This procedure already exists in other clinics and is currently pending. Please contact HPDAI for assistance.');
        }

        // Exclusive services (cannot be done with any other service on same date)
        $exclusiveServices = ['Consultation', 'Simple tooth extraction'];
        if (in_array($serviceName, $exclusiveServices)) {
            if ($this->hasOtherProcedures($memberId, $clinicId, $availmentDate)) {
                return $this->showError("{$serviceName} Restriction", "{$serviceName} cannot be done with other procedures on the same date.");
            }
        } else {
            foreach ($exclusiveServices as $exclusive) {
                if ($this->hasProcedure($memberId, $clinicId, $availmentDate, $exclusive)) {
                    return $this->showError("{$exclusive} Restriction", "No other procedures can be done on the same date as {$exclusive}.");
                }
            }
        }

        // Service pair restrictions
        $restrictions = [
            'Treatment of sores, blisters' => 'Oral Prophylaxis',
            'Desensitization of Hypersensitive teeth' => 'Oral Prophylaxis',
        ];

        if (isset($restrictions[$serviceName])) {
            if ($this->hasProcedure($memberId, $clinicId, $availmentDate, $restrictions[$serviceName])) {
                return $this->showError('Service Restriction', "{$serviceName} cannot be done with {$restrictions[$serviceName]} on the same date.");
            }
        }

        // Tooth-specific validations
        if (isset($data['tooth'])) {
            foreach ($data['tooth'] as $toothId) {
                // Temporary fillings vs Permanent filling
                if ($serviceName === 'Temporary fillings' && $this->hasToothProcedure($memberId, $availmentDate, $toothId, ['Permanent Filling (per tooth)', 'Permanent filling (per Surface)'])) {
                    return $this->showError('Temporary Filling Restriction', 'Temporary fillings cannot be done on the same tooth as permanent filling on the same date.');
                }

                // Desensitization vs Permanent filling
                if ($serviceName === 'Desensitization of Hypersensitive teeth' && $this->hasToothProcedure($memberId, $availmentDate, $toothId, ['Permanent Filling (per tooth)', 'Permanent filling (per Surface)'])) {
                    return $this->showError('Desensitization Restriction', 'Desensitization cannot be done on the same tooth with permanent filling on the same date.');
                }

                // Extraction can only be done once per tooth
                if ($serviceName === 'Simple tooth extraction' && $this->hasToothProcedure($memberId, null, $toothId, ['Simple tooth extraction'])) {
                    return $this->showError('Extraction Restriction', 'Simple tooth extraction can only be done once per tooth.');
                }

                // Cannot do other services on extracted tooth
                if ($serviceName !== 'Simple tooth extraction' && $this->hasToothProcedure($memberId, null, $toothId, ['Simple tooth extraction'])) {
                    return $this->showError('Extracted Tooth Restriction', 'Cannot perform other services on a tooth that has been extracted.');
                }
            }
        }

        return true;
    }

    private function showError(string $title, string $body): bool
    {
        Notification::make()->title($title)->body($body)->danger()->send();
        return false;
    }

    private function hasOtherProcedures(int $memberId, int $clinicId, string $date): bool
    {
        return Procedure::where('member_id', $memberId)
            ->where('clinic_id', $clinicId)
            ->where('availment_date', $date)
            ->where('status', '!=', Procedure::STATUS_VALID)
            ->exists();
    }

    private function hasProcedure(int $memberId, int $clinicId, string $date, string $serviceName): bool
    {
        return Procedure::where('member_id', $memberId)
            ->where('clinic_id', $clinicId)
            ->where('availment_date', $date)
            ->whereHas('service', fn($q) => $q->where('name', $serviceName))
            ->where('status', '!=', Procedure::STATUS_VALID)
            ->exists();
    }

    private function hasToothProcedure(int $memberId, ?string $date, int $toothId, array $serviceNames): bool
    {
        $query = Procedure::where('member_id', $memberId)
            ->whereHas('service', fn($q) => $q->whereIn('name', $serviceNames))
            ->whereHas('units', fn($q) => $q->where('unit_id', $toothId));

        if ($date) {
            $query->where('availment_date', $date)->where('status', '!=', Procedure::STATUS_VALID);
        }

        return $query->exists();
    }



    public function getProcedureForm(): Forms\Form
    {
        return $this->makeForm()
            ->schema([
                /*
            |--------------------------------------------------------------------------
            | SERVICE DROPDOWN
            |--------------------------------------------------------------------------
            */
                Forms\Components\Select::make('service_id')
                    ->label('Service')
                    ->options(function () {
                        $accountId = $this->members->first()->account_id ?? null;
                        if (! $accountId) return collect();

                        return AccountService::where('account_id', $accountId)
                            ->where(function ($query) {
                                $query->where('quantity', '>', 0)
                                    ->orWhere('is_unlimited', true);
                            })
                            ->with('service')
                            ->get()
                            ->pluck('service.name', 'service_id');
                    })
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {

                        $set('surface', []);
                        $set('qudrant', []);

                        // 1. Reset fields if Service is cleared
                        if (! $state) {
                            $set('unit_type_name', null);
                            $set('unit_type_id', null);
                            $set('quantity', null);
                            $set('unit_id', null);
                            return;
                        }

                        // 2. Load Unit Type
                        $service = Service::with('unitType')->find($state);

                        if ($service && $service->unitType) {
                            $set('unit_type_name', $service->unitType->name);
                            $set('unit_type_id', $service->unitType->id);
                        } else {
                            $set('unit_type_name', '—');
                            $set('unit_type_id', null);
                        }
                    }),


                /*
            |--------------------------------------------------------------------------
            | UNIT TYPE DISPLAY
            |--------------------------------------------------------------------------
            */
                Forms\Components\Placeholder::make('unit_type_display')
                    ->label('Unit Type')
                    ->visible(
                        fn(callable $get) =>
                        Service::find($get('service_id'))
                            ?->unitType?->units?->isNotEmpty()
                    )
                    ->content(
                        fn(callable $get) =>
                        Service::find($get('service_id'))?->unitType?->name ?? '—'
                    ),

                /*
            |--------------------------------------------------------------------------
            | QUANTITY
            |--------------------------------------------------------------------------
            */
                Forms\Components\TextInput::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->required()
                    ->visible(function (callable $get) {
                        $accountId = $this->members->first()->account_id ?? null;
                        $serviceId = $get('service_id');
                        if (!$accountId || !$serviceId) return false;

                        $accountService = AccountService::where('account_id', $accountId)
                            ->where('service_id', $serviceId)
                            ->first();

                        return $accountService && !$accountService->is_unlimited || Service::find($serviceId)?->unitType?->units?->isNotEmpty();
                    })
                    ->reactive()
                    ->afterStateUpdated(fn(callable $set) => $set('surface', []))
                    ->maxValue(function (callable $get) {
                        // Dynamic Max Value Logic
                        $accountId = $this->members->first()->account_id ?? null;
                        $serviceId = $get('service_id');

                        if (!$accountId || !$serviceId) return 3;

                        $accountService = AccountService::where('account_id', $accountId)
                            ->where('service_id', $serviceId)
                            ->first();

                        if ($accountService && !$accountService->is_unlimited) {
                            // Max is the lesser of Balance or 3
                            return min($accountService->quantity, 3);
                        }

                        return 3;
                    })
                    ->helperText(function (callable $get) {
                        // Helper text to show remaining balance
                        $accountId = $this->members->first()->account_id ?? null;
                        $serviceId = $get('service_id');

                        if (!$accountId || !$serviceId) return 'Enter a number between 1 and 3';

                        $accountService = AccountService::where('account_id', $accountId)
                            ->where('service_id', $serviceId)
                            ->first();

                        if ($accountService && !$accountService->is_unlimited) {
                            return "Max allowed: " . min($accountService->quantity, 3) . " (Balance: {$accountService->quantity})";
                        }

                        return 'Enter a number between 1 and 3';
                    })

                    ->rules(['integer']),

                /*
            |--------------------------------------------------------------------------
            | UNIT SELECT:  QUADRANT, TOOTH, ARCH, CANAL
            |--------------------------------------------------------------------------
            */

                Forms\Components\Select::make('quadrant')
                    ->label('Quadrant')
                    ->options(Unit::where('unit_type_id', 2)->pluck('name', 'id'))
                    ->multiple()
                    ->live()

                    ->visible(
                        fn(callable $get) =>
                        Service::find($get('service_id'))?->unitType?->name === 'Quadrant'
                            && filled($get('quantity'))
                    )
                    ->helperText(
                        fn(callable $get) =>
                        'You can select up to ' . ($get('quantity') ?? 0) . ' quadrant(s)'
                    )
                    ->maxItems(fn(callable $get) => (int) ($get('quantity') ?? 0)),



                Forms\Components\Select::make('tooth')
                    ->label('Tooth')
                    ->options(Unit::where('unit_type_id', 3)->pluck('name', 'id') ?? collect())
                    ->multiple()
                    ->live()
                    ->required(
                        fn(callable $get) =>
                        Service::find($get('service_id'))?->unitType?->name === 'Tooth'
                            && filled($get('quantity'))
                    )

                    ->visible(function (callable $get) {
                        return Service::find($get('service_id'))
                            ?->unitType
                            ?->name === 'Tooth'
                            && filled($get('quantity'));
                    })
                    ->helperText(
                        fn(callable $get) =>
                        'You can select up to ' . ($get('quantity') ?? 0) . ' tooth(s)'
                    )
                    ->maxItems(fn(callable $get) => (int) ($get('quantity') ?? 0)),

                Forms\Components\Select::make('arch')
                    ->label('Arch')
                    ->options(Unit::where('unit_type_id', 4)->pluck('name', 'id') ?? collect())
                    ->multiple()
                    ->live()
                    ->required(
                        fn(callable $get) =>
                        Service::find($get('service_id'))?->unitType?->name === 'Arch'
                            && filled($get('quantity'))
                    )

                    ->visible(function (callable $get) {
                        return Service::find($get('service_id'))
                            ?->unitType
                            ?->name === 'Arch'
                            && filled($get('quantity'));
                    })
                    ->helperText(
                        fn(callable $get) =>
                        'You can select up to ' . ($get('quantity') ?? 0) . ' arch(s)'
                    )
                    ->maxItems(fn(callable $get) => (int) ($get('quantity') ?? 0)),

                Forms\Components\Select::make('canal')
                    ->label('Canal')
                    ->options(Unit::where('unit_type_id', 6)->pluck('name', 'id') ?? collect())
                    ->multiple()
                    ->live()
                    ->required(
                        fn(callable $get) =>
                        Service::find($get('service_id'))?->unitType?->name === 'Canal'
                            && filled($get('quantity'))
                    )
                    ->visible(function (callable $get) {
                        return Service::find($get('service_id'))
                            ?->unitType
                            ?->name === 'Canal'
                            && filled($get('quantity'));
                    })
                    ->helperText(
                        fn(callable $get) =>
                        'You can select up to ' . ($get('quantity') ?? 0) . ' canal(s)'
                    )
                    ->maxItems(fn(callable $get) => (int) ($get('quantity') ?? 0)),
                /*
            |--------------------------------------------------------------------------
            | SURFACE SELECTION
            |--------------------------------------------------------------------------
            */

                Forms\Components\Select::make('tooth_surface')
                    ->label('Tooth')
                    ->options(Unit::where('unit_type_id', 3)->pluck('name', 'id') ?? collect())
                    ->live()
                    ->required(
                        fn(callable $get) =>
                        Service::find($get('service_id'))?->unitType?->name === 'Tooth'
                            && filled($get('quantity'))
                    )

                    ->visible(function (callable $get) {
                        return Service::find($get('service_id'))
                            ?->unitType
                            ?->name === 'Surface'
                            && filled($get('quantity'));
                    }),


                Forms\Components\Select::make('surface')
                    ->label('Surface')
                    ->options(Unit::where('unit_type_id', 5)->pluck('name', 'id') ?? collect())
                    ->multiple()
                    ->live()
                    ->required()
                    ->visible(function (callable $get) {
                        return Service::find($get('service_id'))
                            ?->unitType
                            ?->name === 'Surface'
                            && filled($get('quantity'));
                    })
                    ->helperText(
                        fn(callable $get) =>
                        'You can select up to ' . ($get('quantity') ?? 0) . ' surface(s)'
                    )
                    ->maxItems(fn(callable $get) => (int) ($get('quantity') ?? 0)),


                /*
            |--------------------------------------------------------------------------
            | AVAILMENT DATE
            |--------------------------------------------------------------------------
            */
                TextInput::make('availment_date_display')
                    ->label('Availment Date')
                    ->default(fn() => now()->format('F j, Y')) // display today's date
                    ->disabled()
                    ->dehydrated(false), // don't submit

                // Actual value submitted
                Hidden::make('availment_date')
                    ->default(fn() => now()->format('Y-m-d')),   // today's date will be submitted
            ])
            ->statePath('procedureFormData');
    }


    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check()
            && auth()->user()->can('dentist.view');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('dentist.search');
    }
}
