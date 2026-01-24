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
            'quantity' => '1',
        ];
        $this->showProcedureModal = true;
    }
    public function saveProcedure(): void
    {
        $data = $this->procedureFormData;
        $clinicId = Auth::user()->clinic->id ?? null;

        if (! $clinicId) {
            Notification::make()
                ->title('Clinic not found')
                ->body('Please make sure you have a clinic assigned to your account.')
                ->danger()
                ->send();
            return;
        }

        if ($data['quantity'] > 6) {
            Notification::make()
                ->title('Quantity Error')
                ->body('Quantity cannot be greater than 6. Please enter a valid number.')
                ->danger()
                ->send();

            return;
        }

        // Generate approval code
        $approvalCode = strtoupper(Str::random(8));
        $applidFee = ClinicService::where('clinic_id', $clinicId)->where('service_id', $data['service_id'])->first()->fee;
        // Create procedure
        $procedure = Procedure::create([
            'clinic_id' => $clinicId,
            'member_id' => $this->selectedMemberId,
            'service_id' => $data['service_id'],
            'availment_date' => $data['availment_date'] ?? null,
            'status' => Procedure::STATUS_PENDING,
            'quantity' => $data['quantity'],
            'approval_code' => $approvalCode,
            'applied_fee' => $applidFee,
        ]);


        // Create associated procedure unit
        $procedureUnit = ProcedureUnit::create([
            'procedure_id' => $procedure->id,
            'unit_id' => $data['unit_id'] ?? null,
            'quantity' => $data['quantity'] ?? 1,
        ]);

        $selectedSurfaces = $this->procedureFormData['procedure_surface'] ?? [];
        if (!empty($selectedSurfaces)) {
            foreach ($selectedSurfaces as $surfaceId) {
                ProcedureSurface::create([
                    'procedure_unit_id' => $procedureUnit->id,
                    'surface_id' => $surfaceId,
                ]);
            }
        }


        // Close form modal and show approval modal
        $this->showProcedureModal = false;
        $this->approvalCode = $approvalCode;
        $this->showApprovalModal = true;

        $this->search(); // refresh member list
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
                    ->live() // Use live() for better real-time updates
                    ->afterStateUpdated(function ($state, callable $set) {

                        // 1. Reset fields if Service is cleared
                        if (! $state) {
                            $set('unit_type_name', null);
                            $set('unit_type_id', null);
                            $set('quantity', null);
                            $set('unit_id', null);
                            $set('procedure_surface', []);
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

                        // 3. Auto-populate Quantity
                        $accountId = $this->members->first()->account_id ?? null;

                        if ($accountId) {
                            $accountService = AccountService::where('account_id', $accountId)
                                ->where('service_id', $state)
                                ->first();

                            if ($accountService) {
                                // Priority: Default Quantity -> Fallback to 1
                                $qty = $accountService->default_quantity ?? 1;

                                // If NOT unlimited, cap it at their remaining balance
                                if (! $accountService->is_unlimited) {
                                    $qty = min($qty, $accountService->quantity);
                                }

                                // Global Max Cap of 6
                                $qty = min($qty, 6);

                                // IMPORTANT: Force integer cast and set
                                $set('quantity', (int) $qty);
                            } else {
                                $set('quantity', 1);
                            }
                        }
                    }),

                /*
            |--------------------------------------------------------------------------
            | UNIT TYPE DISPLAY
            |--------------------------------------------------------------------------
            */
                Forms\Components\Placeholder::make('unit_type_display')
                    ->label('Unit Type')
                    ->content(
                        fn(callable $get) =>
                        Service::find($get('service_id'))?->unitType?->name ?? '—'
                    ),

                /*
            |--------------------------------------------------------------------------
            | UNIT SELECT
            |--------------------------------------------------------------------------
            */
                Forms\Components\Select::make('unit_id')
                    ->label(fn(callable $get) => match (Service::find($get('service_id'))?->unitType?->name) {
                        'Tooth' => 'Tooth Number',
                        'Quadrant' => 'Quadrant',
                        'Canal' => 'Tooth Number',
                        'Surface' => 'Tooth Number',
                        default => 'Unit',
                    })
                    ->options(
                        fn(callable $get) =>
                        Service::find($get('service_id'))
                            ?->unitType?->units?->pluck('name', 'id') ?? collect()
                    )
                    ->reactive()
                    ->required()
                    ->visible(
                        fn(callable $get) =>
                        Service::find($get('service_id'))
                            ?->unitType?->units?->isNotEmpty()
                    ),

                /*
            |--------------------------------------------------------------------------
            | QUANTITY
            |--------------------------------------------------------------------------
            */
                Forms\Components\TextInput::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->minValue(1)
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
                    ->rules(['nullable', 'integer'])
                    ->nullable()
                    ->live(), // Make live so helper text updates instantly

                /*
            |--------------------------------------------------------------------------
            | SURFACE SELECTION
            |--------------------------------------------------------------------------
            */
                Forms\Components\Select::make('procedure_surface')
                    ->label('Surface')
                    ->options(Surface::all()->pluck('name', 'id') ?? collect())
                    ->reactive()
                    ->multiple()
                    ->required()
                    ->visible(function (callable $get) {
                        $service = Service::find($get('service_id'));
                        return $service?->unitType?->name === 'Surface' && $get('quantity') > 0;
                    })
                    ->helperText(
                        fn(callable $get) =>
                        'You can select up to ' . ($get('quantity') ?? 0) . ' surface(s)'
                    )
                    ->maxItems(fn(callable $get) => $get('quantity') ?? 0),

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
