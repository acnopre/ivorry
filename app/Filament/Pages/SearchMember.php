<?php

namespace App\Filament\Pages;

use App\Models\Member;
use App\Models\Procedure;
use App\Models\ProcedureUnit;
use App\Models\Service;
use Filament\Forms;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Auth;

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

        $members = Member::query()
            ->when($this->card_number, fn($q) => $q->where('card_number', 'like', "%{$this->card_number}%"))
            ->when($this->first_name, fn($q) => $q->where('name', 'like', "%{$this->first_name}%"))
            ->when($this->last_name, fn($q) => $q->where('name', 'like', "%{$this->last_name}%"))
            ->get();

        $this->members = $members;
        $this->hasSearched = true;
    }

    public function openProcedureModal(int $memberId): void
    {
        $this->selectedMemberId = $memberId;
        $this->procedureFormData = [];
        $this->showProcedureModal = true;
    }
    public function saveProcedure(): void
    {
        $data = $this->procedureFormData;
        $clinicId = Auth::user()->clinic->id;

        // Generate approval code
        $approvalCode = strtoupper(Str::random(8));

        // Create procedure
        $procedure = Procedure::create([
            'clinic_id' => $clinicId,
            'member_id' => $this->selectedMemberId,
            'service_id' => $data['service_id'],
            'availment_date' => $data['availment_date'] ?? null,
            'status' => Procedure::STATUS_PENDING,
            'approval_code' => $approvalCode,
        ]);

        // Create associated procedure unit
        ProcedureUnit::create([
            'procedure_id' => $procedure->id,
            'unit_id' => $data['unit_id'],
            'quantity' => $data['quantity'] ?? 1,
        ]);

        // 🧮 Deduct service quantity from account_service pivot
        $member = Member::find($this->selectedMemberId);

        if ($member && $member->account) {
            $account = $member->account;
            $serviceId = $data['service_id'];
            $quantityUsed = $data['quantity'] ?? 1;

            $pivot = $account->services()
                ->where('service_id', $serviceId)
                ->first()
                ?->pivot;

            if ($pivot) {
                // Skip deduction if unlimited
                if (!$pivot->is_unlimited) {
                    $newQuantity = max(0, $pivot->quantity - $quantityUsed);

                    $account->services()->updateExistingPivot($serviceId, [
                        'quantity' => $newQuantity,
                    ]);
                }
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
                Forms\Components\Select::make('service_id')
                    ->label('Service')
                    ->options(Service::pluck('name', 'id'))
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if (! $state) {
                            $set('unit_type_name', null);
                            $set('unit_type_id', null);
                            return;
                        }

                        $service = Service::with('unitType')->find($state);

                        if ($service && $service->unitType) {
                            $set('unit_type_name', $service->unitType->name);
                            $set('unit_type_id', $service->unitType->id);
                        } else {
                            $set('unit_type_name', '—');
                            $set('unit_type_id', null);
                        }
                    }),

                Forms\Components\Placeholder::make('unit_type_display')
                    ->label('Unit Type')
                    ->content(fn(callable $get) => Service::find($get('service_id'))?->unitType?->name ?? '—'),

                Forms\Components\Select::make('unit_id')
                    ->label('Unit')
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

                Forms\Components\TextInput::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->nullable(),

                Forms\Components\DatePicker::make('availment_date')
                    ->label('Availment Date')
                    ->nullable(),
            ])
            ->statePath('procedureFormData');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check()
            && auth()->user()->hasAnyRole([
                'Super Admin',
                'Dentist',
            ]);
    }
}
