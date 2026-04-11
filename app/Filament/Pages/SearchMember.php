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
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class SearchMember extends Page implements HasActions
{
    use InteractsWithActions;
    protected static ?string $title = 'Search Member';
    protected static string $view = 'filament.pages.search-member';
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Search';

    public ?string $card_number = null;
    public ?string $first_name = null;
    public ?string $last_name = null;

    public Collection $members;

    public bool $showProcedureModal = false;
    public ?int $selectedMemberId = null;
    public array $procedureFormData = [];
    public bool $hasSearched = false;

    public ?string $approvalCode = null;
    public bool $showApprovalModal = false;
    public bool $showCancelModal = false;
    public ?int $cancelProcedureId = null;
    public ?string $cancelReason = null;

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
                            ->placeholder('Enter Card Number')
                            ->required(fn() => Auth::user()->hasRole('Dentist')),

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
        if (Auth::user()->hasRole('Dentist') && empty($this->card_number)) {
            $this->members = collect();
            $this->hasSearched = false;
            Notification::make()
                ->title('Card Number Required')
                ->body('Card number is required to search.')
                ->warning()
                ->send();
            return;
        }

        if (! $this->card_number && ! $this->first_name && ! $this->last_name) {
            $this->members = collect();
            $this->hasSearched = false;
            Notification::make()
                ->title('No Filters Applied')
                ->body('Please enter at least one search filter before searching.')
                ->warning()
                ->send();
            return;
        }

        $query = Member::query()
            ->with('account')
            ->when($this->card_number, fn($q) => $q->where('card_number', 'like', "%{$this->card_number}%"))
            ->when($this->first_name, fn($q) => $q->where('first_name', 'like', "%{$this->first_name}%"))
            ->when($this->last_name, fn($q) => $q->where('last_name', 'like', "%{$this->last_name}%"));

        // Check if current user's clinic is 'SPECIFIC ACCOUNT'
        $clinic = Clinic::where('user_id', Auth::id())->first();
        if ($clinic && $clinic->accreditation_status === 'SPECIFIC ACCOUNT') {
            $query->where('account_id', $clinic->account_id);
        }

        $results = Auth::user()->hasRole('Dentist') ? $query->limit(1)->get() : $query->get();

        // For SHARED plans, include all members with the same card number
        if ($results->isNotEmpty()) {
            $sharedCardNumbers = $results
                ->filter(fn($m) => $m->account?->plan_type === 'SHARED' && $m->card_number)
                ->pluck('card_number')
                ->unique();

            if ($sharedCardNumbers->isNotEmpty()) {
                $associatedMembers = Member::with('account')
                    ->whereIn('card_number', $sharedCardNumbers)
                    ->whereNotIn('id', $results->pluck('id'))
                    ->get();

                $results = $results->concat($associatedMembers)
                    ->sortBy(fn($m) => [$m->card_number, $m->is_principal ? 0 : 1]);
            }
        }

        $this->members = $results;
        $this->hasSearched = true;
    }

    public function canAddProcedure($member): bool
    {
        return $this->getCanAddProcedureReason($member) === null;
    }

    public function getCanAddProcedureReason($member): ?string
    {
        $today = now()->startOfDay();

        if ($member->status !== 'ACTIVE' || $member->inactive_date !== null) {
            return 'Member is not active';
        }

        if ($member->effective_date && $today->lt(\Carbon\Carbon::parse($member->effective_date)->startOfDay())) {
            return 'Member coverage has not started yet';
        }
        if ($member->expiration_date && $today->gt(\Carbon\Carbon::parse($member->expiration_date)->endOfDay())) {
            return 'Member coverage has expired';
        }

        if (!$member->account) {
            return 'No account found';
        }

        if ($member->account->account_status !== 'active') {
            return 'Account is not active';
        }

        if ($member->account->effective_date && $today->lt(\Carbon\Carbon::parse($member->account->effective_date)->startOfDay())) {
            return 'Account coverage has not started yet';
        }
        if ($member->account->expiration_date && $today->gt(\Carbon\Carbon::parse($member->account->expiration_date)->endOfDay())) {
            return 'Account coverage has expired';
        }

        return null;
    }


    public function openProcedureModal(int $memberId): void
    {
        $this->selectedMemberId = $memberId;
        $this->procedureFormData = [
            'clinic_id' => Auth::user()->clinic->id ?? null,
            'availment_date_display' => now()->format('F j, Y'),
            'availment_date' => now()->format('Y-m-d'),
            'surface' => [],
            'quadrant' => [],
            'tooth' => [],
            'canal' => [],
            'arch' => [],
            'unit_id' => [],
        ];
        $this->dispatch('open-modal', id: 'add-procedure');
    }
    public function saveProcedure(): void
    {
        $data = $this->getProcedureForm()->getState();

        $clinicId = $data['clinic_id'] ?? Auth::user()->clinic->id ?? null;

        if (empty($clinicId)) {
            Notification::make()
                ->title('Clinic Required')
                ->body('Please select a clinic.')
                ->danger()
                ->send();
            return;
        }

        $member = Member::where('id', $this->selectedMemberId)->first();
        $account = $member->account;

        if (!$this->validateBusinessRules($data, $clinicId)) {
            return;
        }

        $appliedFee = ClinicService::where('clinic_id', $clinicId)
            ->where('service_id', $data['service_id'])
            ->value('fee') ?? 0;

        // Check MBL balance for Fixed type
        if ($account->mbl_type === 'Fixed') {
            if ($member->mbl_balance < $appliedFee) {
                Notification::make()
                    ->title('Insufficient MBL Balance')
                    ->body("Service fee (₱" . number_format($appliedFee, 2) . ") exceeds MBL balance (₱" . number_format($member->mbl_balance, 2) . ").")
                    ->danger()
                    ->send();
                return;
            }
        } else {
            // Procedural type - check service quantity/unlimited
            $isServiceUnlimited = $account->services->find($data['service_id'])->pivot->is_unlimited;
            $serviceQuantity = $account->services->find($data['service_id'])->pivot->quantity;

            if (!$isServiceUnlimited && $serviceQuantity <= 0) {
                Notification::make()
                    ->title('Service Unavailable')
                    ->body('This service has no remaining quantity.')
                    ->danger()
                    ->send();
                return;
            }
        }

        $approvalCode = strtoupper(Str::random(8));

        // Possible unit inputs
        $unitInputs = ['tooth', 'arch', 'quadrant', 'canal', 'surface'];
        $basicFields = ['service_id', 'quantity', 'availment_date'];
        $hasUnits = count(array_diff(array_keys($data), $basicFields)) > 0;

        // For Fixed MBL type, always create single procedure regardless of unlimited status
        if ($account->mbl_type === 'Fixed') {
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
        } elseif ($isServiceUnlimited) {

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


        // UI updates
        $this->dispatch('close-modal', id: 'add-procedure');
        $this->approvalCode = $approvalCode;
        $this->showApprovalModal = true;

        $this->search();
    }


    private function validateBusinessRules($data, $clinicId): bool
    {
        $service = Service::find($data['service_id']);
        $serviceName = $service->name;
        $availmentDate = $data['availment_date'] ?? null;
        $memberId = $this->selectedMemberId;

        if (!$clinicId) {
            return $this->showError('Clinic not found', 'Please make sure you have a clinic assigned to your account.');
        }

        if ($service->type === 'special' && !Auth::user()->hasRole('CSR')) {
            return $this->showError('Special Service Restriction', 'Please call HPDAI for approval to avail this special service.');
        }

        // Check if member has procedure in different clinic on same date
        if ($availmentDate) {
            $existsInDifferentClinic = Procedure::where('member_id', $memberId)
                ->where('clinic_id', '!=', $clinicId)
                ->where('availment_date', $availmentDate)
                ->whereNotIn('status', [Procedure::STATUS_VALID, Procedure::STATUS_CANCELLED])
                ->exists();

            if ($existsInDifferentClinic) {
                return $this->showError('Multiple Clinic Restriction', 'Member cannot have procedures in different clinics on the same date.');
            }
        }

        // Check if procedure exists - include units if present
        $unitInputs = ['tooth', 'arch', 'quadrant', 'canal', 'surface'];
        $hasUnits = false;
        $unitIds = [];

        foreach ($unitInputs as $input) {
            if (isset($data[$input]) && !empty($data[$input])) {
                $hasUnits = true;
                $unitIds = array_merge($unitIds, is_array($data[$input]) ? $data[$input] : [$data[$input]]);
            }
        }

        if ($hasUnits) {
            // Check if procedure with same service and units exists
            foreach ($unitIds as $unitId) {
                $exists = Procedure::where('service_id', $data['service_id'])
                    ->where('member_id', $memberId)
                    ->whereNotIn('status', [Procedure::STATUS_VALID, Procedure::STATUS_CANCELLED])
                    ->whereHas('units', fn($q) => $q->where('unit_id', $unitId))
                    ->exists();

                if ($exists) {
                    return $this->showError('Procedure Already Exist', 'This procedure already exists in other clinics and is currently pending. Please contact HPDAI for assistance.');
                }
            }
        } else {
            // Check if procedure without units exists
            if (Procedure::where('service_id', $data['service_id'])
                ->where('member_id', $memberId)
                ->whereNotIn('status', [Procedure::STATUS_VALID, Procedure::STATUS_CANCELLED])
                ->exists()
            ) {
                return $this->showError('Procedure Already Exist', 'This procedure already exists in other clinics and is currently pending. Please contact HPDAI for assistance.');
            }
        }

        if (!$availmentDate) {
            return true;
        }

        // Exclusive services (cannot be done with any other service on same date)
        $exclusiveServices = ['Consultation', 'Simple tooth extraction'];
        if (in_array($serviceName, $exclusiveServices)) {
            // If current service has units, only check for other procedures on same units
            if ($hasUnits) {
                foreach ($unitIds as $unitId) {
                    if ($this->hasOtherProceduresOnUnit($memberId, $clinicId, $availmentDate, $unitId)) {
                        return $this->showError("{$serviceName} Restriction", "{$serviceName} cannot be done with other procedures on the same unit on the same date.");
                    }
                }
            } else {
                // No units, check for any other procedures
                if ($this->hasOtherProcedures($memberId, $clinicId, $availmentDate)) {
                    return $this->showError("{$serviceName} Restriction", "{$serviceName} cannot be done with other procedures on the same date.");
                }
            }
        } else {
            foreach ($exclusiveServices as $exclusive) {
                // Always check if the exclusive service exists (it may have no units, e.g. Consultation)
                if ($this->hasProcedure($memberId, $clinicId, $availmentDate, $exclusive)) {
                    return $this->showError("{$exclusive} Restriction", "No other procedures can be done on the same date as {$exclusive}.");
                }

                // Also check on same units if current service has units (e.g. extraction on same tooth)
                if ($hasUnits) {
                    foreach ($unitIds as $unitId) {
                        if ($this->hasProcedureOnUnit($memberId, $clinicId, $availmentDate, $exclusive, $unitId)) {
                            return $this->showError("{$exclusive} Restriction", "No other procedure can be done with extraction on same tooth number.");
                        }
                    }
                }
            }
        }

        // Service pair restrictions (bidirectional)
        $restrictions = [
            'Treatment of sores, blisters' => 'Oral Prophylaxis',
            'Desensitization of Hypersensitive teeth' => 'Oral Prophylaxis',
            'Oral Prophylaxis' => ['Treatment of sores, blisters', 'Desensitization of Hypersensitive teeth'],
        ];

        if (isset($restrictions[$serviceName])) {
            $conflicting = (array) $restrictions[$serviceName];
            foreach ($conflicting as $conflictService) {
                if ($this->hasProcedure($memberId, $clinicId, $availmentDate, $conflictService)) {
                    return $this->showError('Service Restriction', "{$serviceName} cannot be done with {$conflictService} on the same date.");
                }
            }
        }

        // Tooth-specific validations
        if (isset($data['tooth'])) {
            foreach ($data['tooth'] as $toothId) {
                // Temporary fillings vs Permanent filling (bidirectional)
                if ($serviceName === 'Temporary fillings' && $this->hasToothProcedure($memberId, $availmentDate, $toothId, ['Permanent Filling (per tooth)', 'Permanent filling (per Surface)'])) {
                    return $this->showError('Temporary Filling Restriction', 'Temporary fillings cannot be done on the same tooth as permanent filling on the same date.');
                }
                if (in_array($serviceName, ['Permanent Filling (per tooth)', 'Permanent filling (per Surface)']) && $this->hasToothProcedure($memberId, $availmentDate, $toothId, ['Temporary fillings'])) {
                    return $this->showError('Permanent Filling Restriction', 'Permanent filling cannot be done on the same tooth as temporary fillings on the same date.');
                }

                // Desensitization vs Permanent filling (bidirectional)
                if ($serviceName === 'Desensitization of Hypersensitive teeth' && $this->hasToothProcedure($memberId, $availmentDate, $toothId, ['Permanent Filling (per tooth)', 'Permanent filling (per Surface)'])) {
                    return $this->showError('Desensitization Restriction', 'Desensitization cannot be done on the same tooth with permanent filling on the same date.');
                }
                if (in_array($serviceName, ['Permanent Filling (per tooth)', 'Permanent filling (per Surface)']) && $this->hasToothProcedure($memberId, $availmentDate, $toothId, ['Desensitization of Hypersensitive teeth'])) {
                    return $this->showError('Permanent Filling Restriction', 'Permanent filling cannot be done on the same tooth with desensitization on the same date.');
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
            ->whereNotIn('status', [Procedure::STATUS_VALID, Procedure::STATUS_CANCELLED])
            ->exists();
    }

    private function hasOtherProceduresOnUnit(int $memberId, int $clinicId, string $date, int $unitId): bool
    {
        return Procedure::where('member_id', $memberId)
            ->where('clinic_id', $clinicId)
            ->where('availment_date', $date)
            ->whereNotIn('status', [Procedure::STATUS_VALID, Procedure::STATUS_CANCELLED])
            ->whereHas('units', fn($q) => $q->where('unit_id', $unitId))
            ->exists();
    }

    private function hasProcedure(int $memberId, int $clinicId, string $date, string $serviceName): bool
    {
        return Procedure::where('member_id', $memberId)
            ->where('clinic_id', $clinicId)
            ->where('availment_date', $date)
            ->whereHas('service', fn($q) => $q->where('name', $serviceName))
            ->whereNotIn('status', [Procedure::STATUS_VALID, Procedure::STATUS_CANCELLED])
            ->exists();
    }

    private function hasProcedureOnUnit(int $memberId, int $clinicId, string $date, string $serviceName, int $unitId): bool
    {
        return Procedure::where('member_id', $memberId)
            ->where('clinic_id', $clinicId)
            ->where('availment_date', $date)
            ->whereHas('service', fn($q) => $q->where('name', $serviceName))
            ->whereHas('units', fn($q) => $q->where('unit_id', $unitId))
            ->whereNotIn('status', [Procedure::STATUS_VALID, Procedure::STATUS_CANCELLED])
            ->exists();
    }

    private function hasToothProcedure(int $memberId, ?string $date, int $toothId, array $serviceNames): bool
    {
        $query = Procedure::where('member_id', $memberId)
            ->whereHas('service', fn($q) => $q->whereIn('name', $serviceNames))
            ->whereHas('units', fn($q) => $q->where('unit_id', $toothId));

        if ($date) {
            $query->where('availment_date', $date)->whereNotIn('status', [Procedure::STATUS_VALID, Procedure::STATUS_CANCELLED]);
        }

        return $query->exists();
    }


    public function openAddProcedure(int $memberId): void
    {
        $this->selectedMemberId = $memberId;
        $this->mountAction('addProcedure');
    }

    public function addProcedureAction(): Action
    {
        return Action::make('addProcedure')
            ->label('Add Procedure')
            ->icon('heroicon-o-document-plus')
            ->disabled(fn() => !$this->selectedMemberId || !$this->canAddProcedure(Member::find($this->selectedMemberId)))
            ->modalHeading('Add Procedure')
            ->modalWidth('lg')
            ->fillForm(fn() => [
                'clinic_id' => Auth::user()->clinic->id ?? null,
                'availment_date' => now()->format('Y-m-d'),
            ])
            ->form([
                $this->getClinicField(),
                $this->getServiceField(),
                $this->getFeeField(),
                $this->getUnitTypeDisplay(),
                $this->getQuantityField(),
                ...$this->getUnitFields(),
                $this->getAvailmentDateField(),
            ])
            ->action(function (array $data) {
                $clinicId = $data['clinic_id'] ?? Auth::user()->clinic->id ?? null;
                if (empty($clinicId)) {
                    Notification::make()->title('Clinic Required')->body('Please select a clinic.')->danger()->send();
                    return;
                }
                $this->saveProcedureWithData($data, $clinicId);
            });
    }

    public function getProcedureForm(): Forms\Form
    {
        return $this->makeForm()
            ->schema([
                $this->getClinicField(),
                $this->getServiceField(),
                $this->getFeeField(),
                $this->getUnitTypeDisplay(),
                $this->getQuantityField(),
                ...$this->getUnitFields(),
                $this->getAvailmentDateField(),
            ])
            ->statePath('procedureFormData');
    }

    private function saveProcedureWithData(array $data, int $clinicId): void
    {
        $member = Member::where('id', $this->selectedMemberId)->first();
        $account = $member->account;

        if (!$this->validateBusinessRules($data, $clinicId)) {
            return;
        }

        $appliedFee = $data['applied_fee'] ?? ClinicService::where('clinic_id', $clinicId)
            ->where('service_id', $data['service_id'])
            ->value('fee') ?? 0;

        if ($account->mbl_type === 'Fixed') {
            // Count total units for multiple unit procedures
            $unitInputs = ['tooth', 'arch', 'quadrant', 'canal', 'surface'];
            $totalUnits = 0;
            foreach ($unitInputs as $input) {
                if (isset($data[$input]) && !empty($data[$input])) {
                    $totalUnits += count($data[$input]);
                }
            }
            $totalUnits = max($totalUnits, 1);
            $totalFee = $appliedFee * $totalUnits;

            if ($member->mbl_balance < $totalFee) {
                Notification::make()->title('Insufficient MBL Balance')->body("Total fee (₱" . number_format($totalFee, 2) . ") exceeds MBL balance (₱" . number_format($member->mbl_balance, 2) . ").")->danger()->send();
                return;
            }
        } else {
            $isServiceUnlimited = $account->services->find($data['service_id'])->pivot->is_unlimited;
            $serviceQuantity = $account->services->find($data['service_id'])->pivot->quantity;

            if (!$isServiceUnlimited && $serviceQuantity <= 0) {
                Notification::make()->title('Service Unavailable')->danger()->send();
                return;
            }
        }

        $approvalCode = strtoupper(Str::random(8));
        $status = Auth::user()->hasRole('CSR') ? Procedure::STATUS_SIGN : Procedure::STATUS_PENDING;
        $unitInputs = ['tooth', 'arch', 'quadrant', 'canal', 'surface'];

        $hasUnits = false;
        foreach ($unitInputs as $input) {
            if (isset($data[$input]) && !empty($data[$input])) {
                $hasUnits = true;
                break;
            }
        }

        if ($hasUnits) {
            foreach ($unitInputs as $input) {
                if (!isset($data[$input]) || empty($data[$input])) continue;

                foreach ($data[$input] as $value) {
                    $procedure = Procedure::create([
                        'clinic_id' => $clinicId,
                        'member_id' => $this->selectedMemberId,
                        'service_id' => $data['service_id'],
                        'availment_date' => $data['availment_date'] ?? null,
                        'status' => $status,
                        'quantity' => $data['quantity'] ?? 1,
                        'approval_code' => $approvalCode,
                        'applied_fee' => $appliedFee,
                    ]);

                    ProcedureUnit::create([
                        'procedure_id' => $procedure->id,
                        'unit_id' => $input === 'surface' ? $data['tooth_surface'] : $value,
                        'quantity' => 1,
                        'input_quantity' => $data['quantity'] ?? 1,
                        'surface_id' => $input === 'surface' ? $value : null,
                    ]);
                }
            }
        } else {
            Procedure::create([
                'clinic_id' => $clinicId,
                'member_id' => $this->selectedMemberId,
                'service_id' => $data['service_id'],
                'availment_date' => $data['availment_date'] ?? null,
                'status' => $status,
                'quantity' => 1,
                'approval_code' => $approvalCode,
                'applied_fee' => $appliedFee,
            ]);
        }

        $this->approvalCode = $approvalCode;

        // Deduct balance/quantity if CSR
        if (Auth::user()->hasRole('CSR')) {
            if ($account->mbl_type === 'Fixed') {
                // Deduct MBL balance for Fixed type
                $member->mbl_balance -= $appliedFee;
                $member->save();
            } else {
                // Deduct service quantity for Procedural type
                $accountService = $account->services()->where('service_id', $data['service_id'])->first();
                if ($accountService && !$accountService->pivot->is_unlimited) {
                    $accountService->pivot->decrement('quantity', 1);
                }
            }
        }

        $this->showApprovalModal = true;
        $this->search();
    }

    protected function getClinicField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('clinic_id')
            ->label('Clinic')
            ->options(Clinic::pluck('clinic_name', 'id'))
            ->native(false)
            ->searchable()
            ->dehydrated()
            ->visible(fn() => Auth::user()->hasRole('CSR') && Auth::user()->clinic === null);
    }

    protected function getServiceField(): Forms\Components\Select
    {
        $isDentist = Auth::user()->hasRole('Dentist');

        return Forms\Components\Select::make('service_id')
            ->label('Service')
            ->options(function () use ($isDentist) {
                $accountId = Member::find($this->selectedMemberId)?->account_id ?? null;
                if (!$accountId) return collect();

                return AccountService::where('account_id', $accountId)
                    ->where(fn($q) => $q->where('quantity', '>', 0)->orWhere('is_unlimited', true))
                    ->with('service')
                    ->get()
                    ->when($isDentist, fn($col) => $col->filter(fn($as) => $as->service?->type !== 'special'))
                    ->groupBy('service.type')
                    ->map(fn($group) => $group->pluck('service.name', 'service_id'))
                    ->toArray();
            })
            ->helperText(function () use ($isDentist) {
                if (!$isDentist) return null;
                $accountId = Member::find($this->selectedMemberId)?->account_id ?? null;
                if (!$accountId) return null;
                $hasSpecial = AccountService::where('account_id', $accountId)
                    ->whereHas('service', fn($q) => $q->where('type', 'special'))
                    ->exists();
                return $hasSpecial ? new \Illuminate\Support\HtmlString('<span class="text-xs text-amber-500">📞 Special services included. Call HPDAI for details.</span>') : null;
            })
            ->live()
            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                $this->resetUnitFields($set);
                if (!$state) return;

                $service = Service::with('unitType')->find($state);
                $unitTypeName = $service?->unitType?->name ?? '—';
                $set('unit_type_name', $unitTypeName);
                $set('unit_type_id', $service?->unitType?->id);

                // Set quantity to 1 and disable if unit type is Session
                if ($unitTypeName === 'Session') {
                    $set('quantity', 1);
                }

                $clinicId = $get('clinic_id') ?? Auth::user()->clinic->id ?? null;
                if ($clinicId) {
                    $fee = ClinicService::where('clinic_id', $clinicId)
                        ->where('service_id', $state)
                        ->value('fee') ?? 0;
                    $set('applied_fee', $fee);
                }
            });
    }

    protected function getFeeField(): Forms\Components\TextInput
    {
        $isCSR = Auth::user()->hasRole('CSR');
        return Forms\Components\TextInput::make('applied_fee')
            ->label('Fee')
            ->numeric()
            ->prefix('₱')
            ->required()
            ->disabled(!$isCSR)
            ->dehydrated()
            ->visible(function (callable $get) {
                if (!filled($get('service_id'))) return false;
                $member = $this->members->first();
                return $member?->account?->mbl_type === 'Fixed';
            });
    }

    protected function getUnitTypeDisplay(): Forms\Components\Placeholder
    {
        return Forms\Components\Placeholder::make('unit_type_display')
            ->label('Unit Type')
            ->visible(fn(callable $get) => Service::find($get('service_id'))?->unitType?->units?->isNotEmpty())
            ->content(fn(callable $get) => Service::find($get('service_id'))?->unitType?->name ?? '—');
    }

    protected function getQuantityField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('quantity')
            ->label('Quantity')
            ->numeric()
            ->integer()
            ->minValue(1)
            ->default(1)
            ->required()
            ->live()
            ->afterStateUpdated(fn(callable $set) => $set('surface', []))
            ->visible(fn(callable $get) => $this->shouldShowQuantityField($get))
            ->disabled(fn(callable $get) => $this->isUnitType($get, 'Session'))
            ->dehydrated()
            ->maxValue(fn(callable $get) => $this->getMaxQuantity($get))
            ->helperText(fn(callable $get) => $this->getQuantityHelperText($get))
            ->validationMessages([
                'max' => 'Quantity cannot exceed the maximum allowed per date.',
            ]);
    }

    protected function getUnitFields(): array
    {
        $unitTypes = [
            ['field' => 'quadrant', 'label' => 'Quadrant', 'type_id' => 2, 'type_name' => 'Quadrant'],
            ['field' => 'tooth', 'label' => 'Tooth', 'type_id' => 3, 'type_name' => 'Tooth'],
            ['field' => 'arch', 'label' => 'Arch', 'type_id' => 4, 'type_name' => 'Arch'],
            ['field' => 'canal', 'label' => 'Canal', 'type_id' => 6, 'type_name' => 'Canal'],
        ];

        $fields = [];
        foreach ($unitTypes as $unit) {
            $fields[] = $this->createUnitSelectField($unit['field'], $unit['label'], $unit['type_id'], $unit['type_name']);
        }

        $fields[] = $this->getSurfaceFields();
        return array_merge(...$fields);
    }

    protected function createUnitSelectField(string $field, string $label, int $typeId, string $typeName): array
    {
        return [
            Forms\Components\Select::make($field)
                ->label($label)
                ->options(Unit::where('unit_type_id', $typeId)->pluck('name', 'id'))
                ->multiple()
                ->live()
                ->required(fn(callable $get) => $this->isUnitType($get, $typeName) && filled($get('quantity')))
                ->visible(fn(callable $get) => $this->isUnitType($get, $typeName) && filled($get('quantity')))
                ->helperText(fn(callable $get) => "Select up to {$get('quantity')} {$label}(s)")
                ->maxItems(fn(callable $get) => (int) ($get('quantity') ?? 0))
        ];
    }

    protected function getSurfaceFields(): array
    {
        return [
            Forms\Components\Select::make('tooth_surface')
                ->label('Tooth')
                ->options(Unit::where('unit_type_id', 3)->pluck('name', 'id'))
                ->live()
                ->required(fn(callable $get) => $this->isUnitType($get, 'Surface') && filled($get('quantity')))
                ->visible(fn(callable $get) => $this->isUnitType($get, 'Surface') && filled($get('quantity'))),

            Forms\Components\Select::make('surface')
                ->label('Surface')
                ->options(Unit::where('unit_type_id', 5)->pluck('name', 'id'))
                ->multiple()
                ->live()
                ->required()
                ->visible(fn(callable $get) => $this->isUnitType($get, 'Surface') && filled($get('quantity')))
                ->helperText(fn(callable $get) => "Select up to {$get('quantity')} surface(s)")
                ->maxItems(fn(callable $get) => (int) ($get('quantity') ?? 0)),
        ];
    }

    protected function getAvailmentDateField(): Forms\Components\DatePicker
    {
        $isCSR = Auth::user()->hasRole('CSR');
        return Forms\Components\DatePicker::make('availment_date')
            ->label('Availment Date')
            ->default(now())
            ->minDate($isCSR ? now()->subDays(3) : '2026-02-26')
            ->maxDate($isCSR ? now()->addDays(5) : now())
            ->disabled(!$isCSR)
            ->dehydrated()
            ->required();
    }

    protected function resetUnitFields(callable $set): void
    {
        $fields = ['surface', 'quadrant', 'quantity', 'unit_id', 'unit_type_name', 'unit_type_id'];
        foreach ($fields as $field) {
            $set($field, $field === 'surface' || $field === 'quadrant' ? [] : null);
        }
    }

    protected function isUnitType(callable $get, string $typeName): bool
    {
        return Service::find($get('service_id'))?->unitType?->name === $typeName;
    }

    protected function shouldShowQuantityField(callable $get): bool
    {
        $accountId = Member::find($this->selectedMemberId)?->account_id ?? null;
        $serviceId = $get('service_id');
        if (!$accountId || !$serviceId) return false;

        $accountService = AccountService::where('account_id', $accountId)
            ->where('service_id', $serviceId)
            ->first();

        return ($accountService && !$accountService->is_unlimited) ||
            Service::find($serviceId)?->unitType?->units?->isNotEmpty();
    }

    protected function getMaxQuantity(callable $get): int
    {
        $serviceId = $get('service_id');
        if (!$serviceId) return 3;

        $service = Service::find($serviceId);
        $maxPerDate = $service?->max_per_date ?? 3;

        $accountId = Member::find($this->selectedMemberId)?->account_id ?? null;
        if (!$accountId) return $maxPerDate;

        $accountService = AccountService::where('account_id', $accountId)
            ->where('service_id', $serviceId)
            ->first();

        return $accountService && !$accountService->is_unlimited
            ? min($accountService->quantity, $maxPerDate)
            : $maxPerDate;
    }

    protected function getQuantityHelperText(callable $get): string
    {
        $serviceId = $get('service_id');
        if (!$serviceId) return 'Enter quantity';

        $service = Service::find($serviceId);
        $maxPerDate = $service?->max_per_date ?? 3;

        $accountId = Member::find($this->selectedMemberId)?->account_id ?? null;
        if (!$accountId) return "Max per date: {$maxPerDate}";

        $accountService = AccountService::where('account_id', $accountId)
            ->where('service_id', $serviceId)
            ->first();

        if ($accountService && !$accountService->is_unlimited) {
            $max = min($accountService->quantity, $maxPerDate);
            return "Max: {$max} | Balance: {$accountService->quantity} | Max per date: {$maxPerDate}";
        }

        return "Max per date: {$maxPerDate}";
    }


    public function openCancelModal(int $procedureId): void
    {
        $this->cancelProcedureId = $procedureId;
        $this->cancelReason = null;
        $this->showCancelModal = true;
    }

    public function confirmCancelProcedure(): void
    {
        $procedure = Procedure::find($this->cancelProcedureId);

        if (!$procedure || $procedure->status !== Procedure::STATUS_PENDING) {
            Notification::make()->title('Cannot Cancel')->body('Only pending procedures can be cancelled.')->danger()->send();
            $this->showCancelModal = false;
            return;
        }

        $procedure->update([
            'status'  => Procedure::STATUS_CANCELLED,
            'remarks' => $this->cancelReason,
        ]);

        $this->showCancelModal = false;
        $this->cancelProcedureId = null;
        $this->cancelReason = null;

        Notification::make()->title('Procedure Cancelled')->success()->send();
        $this->search();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check()
            && auth()->user()->can('dentist.search');
    }

    public static function canAccess(): bool
    {
        return auth()->user()->can('dentist.search');
    }
}
