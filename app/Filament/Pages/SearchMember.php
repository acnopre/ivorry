<?php

namespace App\Filament\Pages;

use App\Models\Member;
use App\Models\Procedure;
use App\Models\ProcedureUnit;
use App\Models\Service;
use App\Models\Unit;
use App\Models\UnitType;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
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

    public function mount(): void
    {
        $this->members = collect();
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Grid::make(3)->schema([
                Forms\Components\TextInput::make('card_number')->label('Card Number'),
                Forms\Components\TextInput::make('first_name')->label('First Name'),
                Forms\Components\TextInput::make('last_name')->label('Last Name'),
            ]),
        ];
    }

    public function search(): void
    {
        if (! $this->card_number && ! $this->first_name && ! $this->last_name) {
            $this->members = collect();
            return;
        }

        $members = Member::query()
            ->when($this->card_number, fn($q) => $q->where('card_number', 'like', "%{$this->card_number}%"))
            ->when($this->first_name, fn($q) => $q->where('name', 'like', "%{$this->first_name}%"))
            ->when($this->last_name, fn($q) => $q->where('name', 'like', "%{$this->last_name}%"))
            ->get();

        $this->members = $members;
    }

    public function openProcedureModal(int $memberId): void
    {
        $this->selectedMemberId = $memberId;
        $this->procedureFormData = [];
        $this->showProcedureModal = true;
    }

    public function saveProcedure(): void
    {
        // dd(Auth::id(), \App\Models\Clinics::where('user_id', Auth::id())->first());
        $data = $this->procedureFormData;
        $clinicId = Auth::user()->clinic->id;
        $procedure = Procedure::create([
            'clinics_id' => $clinicId,
            'member_id' => $this->selectedMemberId,
            'service_id' => $data['service_id'],
            'availment_date' => $data['availment_date'] ?? null,
            'status' => 'pending',
        ]);

        ProcedureUnit::create([
            'procedure_id' => $procedure->id,
            'unit_type_id' => $data['unit_type_id'],
            'unit_id' => $data['unit_id'],
            'quantity' => $data['quantity'] ?? null,
        ]);

        $this->showProcedureModal = false;

        Notification::make()
            ->title('Procedure Added')
            ->body('The procedure was successfully added.')
            ->success()
            ->send();

        $this->search(); // refresh table
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
                        $set('unit_type_name', $service->unitType->name); // ✅ set visible field
                        $set('unit_type_id', $service->unitType->id);     // ✅ optional hidden field
                    } else {
                        $set('unit_type_name', '—'); // fallback if no unit type
                        $set('unit_type_id', null);
                    }
                }),
            
                Forms\Components\Placeholder::make('unit_type_display')
                ->label('Unit Type')
                ->content(fn (callable $get) =>
                    Service::find($get('service_id'))?->unitType?->name ?? '—'
                ),
                Forms\Components\Select::make('unit_id')
                ->label('Unit')
                ->options(fn (callable $get) =>
                    Service::find($get('service_id'))
                        ?->unitType?->units?->pluck('name', 'id') ?? collect()
                )
                ->reactive()
                ->required()
                ->visible(fn (callable $get) => 
                    Service::find($get('service_id'))
                        ?->unitType?->units?->isNotEmpty()
                ),

                // 🔢 Quantity
                Forms\Components\TextInput::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->nullable(),

                // 📅 Availment Date
                Forms\Components\DatePicker::make('availment_date')
                    ->label('Availment Date')
                    ->nullable(),
            ])
            ->statePath('procedureFormData');
    }



    protected function getGroupedServices(): array
    {
        return Service::all()
            ->groupBy('type')
            ->mapWithKeys(fn($group, $type) => [
                ucfirst($type) => $group->pluck('name', 'id')->toArray()
            ])
            ->toArray();
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
