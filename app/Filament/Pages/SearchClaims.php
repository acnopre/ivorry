<?php

namespace App\Filament\Pages;

use App\Models\Clinic;
use App\Models\GeneratedSoa;
use App\Models\Procedure;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;

class SearchClaims extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $title = 'Search Claims';
    protected static string $view = 'filament.pages.search-claims';
    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';

    public ?array $data = [];
    public bool $hasSearched = false;

    public function mount(): void
    {
        $this->form->fill();
    }
    #[On('refreshTable')]
    public function refreshTable(): void
    {
        $this->dispatch('$refresh');
    }

    public function search(): void
    {
        $formData = $this->form->getState();

        $hasInput = collect($formData)
            ->filter(fn($value) => !empty($value))
            ->isNotEmpty();

        if (! $hasInput) {
            Notification::make()
                ->title('No Filters Applied')
                ->body('Please enter at least one search filter before searching.')
                ->warning()
                ->send();

            $this->hasSearched = false;
            return;
        }

        $this->data = $formData;
        $this->hasSearched = true;
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Search Claims')
                ->schema([
                    Forms\Components\Grid::make(4)->schema([
                        Forms\Components\TextInput::make('approval_code')->label('Approval Code')->placeholder('Enter Approval Code'),
                        Forms\Components\TextInput::make('member_name')->placeholder('Enter Member Name'),
                        Forms\Components\Select::make('clinic_id')
                            ->label('Clinic')
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) {
                                return \App\Models\Clinic::where('clinic_name', 'like', "%{$search}%")
                                    ->limit(20)
                                    ->pluck('clinic_name', 'id');
                            })
                            ->getOptionLabelUsing(
                                fn($value): ?string =>
                                \App\Models\Clinic::find($value)?->clinic_name
                            )
                            ->required()
                            ->placeholder('Search Clinic...'),

                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'completed' => 'Completed',
                                'valid' => 'Valid',
                                'invalid' => 'Rejected',
                                'returned' => 'Returned',
                                'processed' => 'Processed',
                            ])
                            ->label('Claim Status')
                            ->placeholder('Any Status'),
                        Forms\Components\DatePicker::make('availment_from')
                            ->required()
                            ->label('Availment From'),
                        Forms\Components\DatePicker::make('availment_to')
                            ->required()
                            ->label('Availment To'),
                    ]),
                ])
                ->footerActions([
                    Forms\Components\Actions\Action::make('search_action')
                        ->label('Search Claims')
                        ->icon('heroicon-o-magnifying-glass')
                        ->color('primary')
                        ->action('search'),
                ]),
        ];
    }

    protected function getFormModel(): string
    {
        return Procedure::class;
    }

    protected function getFormStatePath(): ?string
    {
        return 'data';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                if (! $this->hasSearched) {
                    return Procedure::query()->whereRaw('1 = 0'); // empty state
                }
                $searchData = $this->data;
                $query =  Procedure::query()
                    ->when(
                        $searchData['member_name'] ?? null,
                        fn(Builder $q, $name) =>
                        $q->whereHas('member', function ($r) use ($name) {
                            $r->where(function ($sub) use ($name) {
                                $sub->where('first_name', 'like', "%{$name}%")
                                    ->orWhere('last_name', 'like', "%{$name}%")
                                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$name}%"]);
                            });
                        })
                    )

                    ->when(
                        $searchData['approval_code'] ?? null,
                        fn(Builder $q, $code) =>
                        $q->where('approval_code', 'like', "%{$code}%")
                    )
                    ->when(
                        $searchData['clinic_id'] ?? null,
                        fn(Builder $q, $clinic_id) =>
                        $q->whereHas('clinic', fn($r) => $r->where('id', '=', $clinic_id))
                    )
                    ->when(
                        $searchData['status'] ?? null,
                        fn(Builder $q, $status) =>
                        $q->where('status', $status)
                    )
                    ->when(
                        isset($searchData['availment_from'], $searchData['availment_to']),
                        fn(Builder $q) =>
                        $q->whereBetween('availment_date', [
                            $searchData['availment_from'],
                            $searchData['availment_to'],
                        ])
                    )
                    ->latest();
                return $query;
            })
            ->columns([
                Tables\Columns\TextColumn::make('clinic.clinic_name')->label('Clinic Name')->sortable(),
                Tables\Columns\TextColumn::make('member.first_name')
                    ->label('Member Name')
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {
                        $first = $record->member?->first_name;
                        $last = $record->member?->last_name;
                        $suffix = $record->member?->suffix;

                        return trim(ucwords("{$first} {$last}" . ($suffix ? ", {$suffix}" : '')));
                    }),
                Tables\Columns\TextColumn::make('approval_code')->label('Approval Code')->limit(30),
                Tables\Columns\TextColumn::make('service.name')->label('Service Claimed')->limit(30),
                Tables\Columns\TextColumn::make('availment_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn($state) => ucfirst($state))
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'completed' => 'info',
                        'valid' => 'success',
                        'invalid' => 'danger',
                        'returned' => 'warning',
                        'processed' => 'primary',
                        default => 'secondary',
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Claim Details')
                    ->modalContent(function (?Procedure $record) {
                        if (! $record) {
                            return [];
                        }
                        $account = $record->member->account;
                        $services = $account->services ?? collect();
                        $member = $record->member;
                        $units = $record->units ?? collect();

                        return view('filament.modals.claim-view-tabs', [
                            'record' => $record,
                            'account' => $account,
                            'services' => $services,
                            'units' => $units,
                            'member' => $member,
                        ]);
                    })
                    ->extraModalFooterActions(function (?Procedure $record) {
                        if (! $record) {
                            return [];
                        }

                        if ($record->status !== Procedure::STATUS_COMPLETED) {
                            return [];
                        }

                        return [
                            // 🟢 VALID
                            Tables\Actions\Action::make('mark_valid_modal')
                                ->label('Valid')
                                ->icon('heroicon-o-check-circle')
                                ->color('success')
                                ->requiresConfirmation()
                                ->modalHeading('Confirm Validation')
                                ->modalDescription('Are you sure you want to mark this claim as valid?')
                                ->action(function (Procedure $record) {
                                    $record->update(['status' => Procedure::STATUS_VALID]);

                                    Notification::make()
                                        ->title('Claim Marked as Valid')
                                        ->success()
                                        ->send();

                                    $this->dispatch('closeModal');
                                    // $this->dispatch('refreshTable');
                                }),

                            // 🔴 REJECTED
                            Tables\Actions\Action::make('mark_invalid_modal')
                                ->label('Rejected')
                                ->icon('heroicon-o-x-circle')
                                ->color('danger')
                                ->form([
                                    Forms\Components\Textarea::make('remarks')
                                        ->label('Remarks')
                                        ->placeholder('Enter reason for rejection...')
                                        ->required(),
                                ])
                                ->requiresConfirmation()
                                ->modalHeading('Confirm Rejection')
                                ->modalDescription('Are you sure you want to reject this claim?')
                                ->action(function (Procedure $record, array $data) {
                                    $record->update([
                                        'status' => Procedure::STATUS_REJECT,
                                        'remarks' => $data['remarks'],
                                    ]);

                                    Notification::make()
                                        ->title('Claim Rejected')
                                        ->danger()
                                        ->send();

                                    $this->dispatch('closeModal');
                                    // $this->dispatch('refreshTable');
                                }),

                            // 🟡 RETURN
                            Tables\Actions\Action::make('mark_returned_modal')
                                ->label('Return')
                                ->icon('heroicon-o-arrow-uturn-left')
                                ->color('warning')
                                ->form([
                                    Forms\Components\Textarea::make('remarks')
                                        ->label('Remarks')
                                        ->placeholder('Enter reason for return...')
                                        ->required(),
                                ])
                                ->requiresConfirmation()
                                ->modalHeading('Confirm Return Claims')
                                ->modalDescription('Are you sure you want to return this claim?')
                                ->action(function (Procedure $record, array $data) {
                                    $record->update([
                                        'status' => Procedure::STATUS_RETURN,
                                        'remarks' => $data['remarks'],
                                    ]);

                                    Notification::make()
                                        ->title('Claim Return')
                                        ->danger()
                                        ->send();

                                    $this->dispatch('closeModal');
                                    // $this->dispatch('refreshTable');
                                }),
                        ];
                    })

                    ->modalSubmitAction(false)
            ])
            ->headerActions([
                Tables\Actions\Action::make('generate_soa')
                    ->label('Generate SOA')
                    ->color('success')
                    ->icon('heroicon-o-document-arrow-down')
                    ->requiresConfirmation()
                    ->modalHeading('Generate Statement of Account')
                    ->modalDescription('Once confirmed, all displayed procedures will be marked as PROCESSED before the SOA is created.')
                    ->modalSubmitActionLabel('Yes, Generate SOA')
                    ->action(fn() => $this->confirmSOA()),

            ])
            ->defaultSort('availment_date', 'desc');
    }

    #[On('updateClaimStatus')]
    public function updateClaimStatus(int $id, string $status, ?string $remarks = null): void
    {
        $claim = Procedure::find($id);

        if (! $claim) {
            Notification::make()->title('Claim Not Found')->danger()->send();
            return;
        }

        $claim->update(['status' => $status, 'remarks' => $remarks]);

        Notification::make()
            ->title('Claim Updated')
            ->body("Claim marked as " . ucfirst($status))
            ->success()
            ->send();

        $this->dispatch('$refresh');
    }


    public function confirmSOA()
    {
        $data = $this->data;

        if (! $this->hasSearched) {
            \Filament\Notifications\Notification::make()
                ->title('Search Required')
                ->body('Please apply search filters before generating the SOA.')
                ->warning()
                ->send();
            return;
        }

        // Fetch all matching procedures
        $claims = Procedure::query()
            ->when(
                $data['member_name'] ?? null,
                fn($q, $name) =>
                $q->whereHas(
                    'member',
                    fn($r) =>
                    $r->where(function ($sub) use ($name) {
                        $sub->where('first_name', 'like', "%{$name}%")
                            ->orWhere('last_name', 'like', "%{$name}%")
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$name}%"]);
                    })
                )
            )
            ->when($data['approval_code'] ?? null, fn($q, $code) => $q->where('approval_code', 'like', "%{$code}%"))
            ->when($data['clinic_id'] ?? null, fn($q, $clinic_id) => $q->where('clinic_id', $clinic_id))
            ->when($data['status'] ?? null, fn($q, $status) => $q->where('status', $status))
            ->when(
                isset($data['availment_from'], $data['availment_to']),
                fn($q) =>
                $q->whereBetween('availment_date', [$data['availment_from'], $data['availment_to']])
            )
            ->where('status', 'valid')
            ->get();

        if ($claims->isEmpty()) {
            \Filament\Notifications\Notification::make()
                ->title('No Data')
                ->body('No valid procedures were found.')
                ->warning()
                ->send();
            return;
        }

        // Update procedures status → processed
        Procedure::whereIn('id', $claims->pluck('id'))->update(['status' => 'processed']);

        // Calculate total amount for the SOA (optional)
        $totalAmount = $claims->sum('quantity'); // or use actual billing column

        // Create a GeneratedSoa record
        $soa = GeneratedSoa::create([
            'clinic_id' => $data['clinic_id'],
            'from_date' => $data['availment_from'],
            'to_date' => $data['availment_to'],
            'total_amount' => $totalAmount,
            'generated_by' => auth()->id(),
            'status' => 'generated',
        ]);

        // Attach procedures to pivot table
        foreach ($claims as $procedure) {
            $soa->procedures()->attach($procedure->id, [
                'amount' => $procedure->quantity, // optional per procedure amount
            ]);
        }

        // Generate and download PDF
        return $this->generateSOAAfterProcessing($claims, $soa);
    }



    public function generateSOAAfterProcessing($claims, GeneratedSoa $soa)
    {
        $data = $this->data;
        $clinicDetails = Clinic::find($data['clinic_id']);
        $pdf = Pdf::loadView('pdf.soa', [
            'claims' => $claims,
            'from' => $data['availment_from'],
            'to' => $data['availment_to'],
            'clinicDetails' => $clinicDetails,
            'soa' => $soa,
        ])->setPaper('a4', 'landscape');

        $fileName = 'SOA_' . now()->format('Y-m-d_His') . '.pdf';
        $path = 'soas/' . $fileName;

        // Store the file on the "public" disk
        Storage::disk('public')->put($path, $pdf->output());

        // Update the database with relative path
        $soa->update(['file_path' => $path]);

        // Return download
        return Storage::disk('public')->download($path, $fileName);
    }




    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole([Role::SUPER_ADMIN, Role::CLAIMS_PROCESSOR]);
    }
}
