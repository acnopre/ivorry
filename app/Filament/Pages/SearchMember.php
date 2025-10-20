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
                // 🦷 Select Service
                Forms\Components\Select::make('service_id')
                    ->label('Service')
                    ->options($this->getGroupedServices())
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get, $component) {
                        // Reset dependent fields first
                        $set('unit_type_id', null);
                        $set('unit_id', null);
    
                        if (!$state) {
                            return;
                        }
    
                        // Fetch the service and match its unit_type
                        $service = \App\Models\Service::find($state);
    
                        if ($service && $service->unit_type) {
                            $unitType = \App\Models\UnitType::where('name', $service->unit_type)->first();
    
                            if ($unitType) {
                                // ✅ Set value AND trigger re-render for unit_type_id
                                $set('unit_type_id', $unitType->id);
                                $component
                                    ->getContainer()
                                    ->getComponent('unit_type_id')
                                    ?->state($unitType->id)
                                    ?->reactive();
                            }
                        }
                    })
                    ->required(),
    
                // ⚙️ Unit Type (auto-filled & locked)
                Forms\Components\Select::make('unit_type_id')
                    ->label('Unit Type')
                    ->options(\App\Models\UnitType::pluck('name', 'id'))
                    ->reactive() // ✅ ensure reactivity for auto-refresh
                    ->disabled() // no need to conditionally disable, it's auto-filled
                    ->required(),
    
                // 📏 Unit (depends on Unit Type)
                Forms\Components\Select::make('unit_id')
                    ->label('Unit')
                    ->options(fn (callable $get) => $get('unit_type_id')
                        ? \App\Models\Unit::where('unit_type_id', $get('unit_type_id'))->pluck('name', 'id')
                        : [])
                    ->reactive()
                    ->disabled(fn (callable $get) => blank($get('unit_type_id')))
                    ->required(),
    
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
