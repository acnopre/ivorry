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
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use App\Models\User;
use App\Services\ServiceQuantityService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\Fpdi;
use App\Pdf\SectionedFpdi;

class SearchClaims extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $title = 'Search Claims';
    protected static string $view = 'filament.pages.search-claims';
    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';
    protected static ?string $navigationGroup = 'Search';
    protected static ?int $navigationSort = 2;

    public ?array $data = [];
    public bool $hasSearched = false;
    public bool $isResultHasValid = false;
    public ?array $previewData = null;
    public ?string $selectedPrinter = null;

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
                                'signed' => 'Signed',
                                'valid' => 'Valid',
                                'invalid' => 'Rejected',
                                'returned' => 'Returned',
                                'processed' => 'Processed',
                                'cancelled' => 'Cancelled',
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
                        ->visible(auth()->user()->can('claims.search'))
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
                        !empty($searchData['availment_from']) && !empty($searchData['availment_to']),
                        fn(Builder $q) =>
                        $q->whereBetween('availment_date', [
                            $searchData['availment_from'],
                            $searchData['availment_to'],
                        ])
                    )
                    ->latest();
                $this->isResultHasValid = ! (clone $query)
                    ->where('status', 'VALID')
                    ->exists();
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
                Tables\Columns\TextColumn::make('applied_fee')->label('Applied Fee')->money('PHP')->sortable(),
                Tables\Columns\TextColumn::make('units_display')
                    ->label('Units')
                    ->getStateUsing(function ($record) {
                        $lines = [];
                        foreach ($record->units as $unit) {
                            if ($unit->pivot->surface_id) {
                                $sub = \App\Models\Unit::with('unitType')->find($unit->pivot->surface_id);
                                $subLabel = $sub?->unitType?->name ?? 'Surface';
                                $lines[] = 'Tooth ' . $unit->name . ' | ' . $subLabel . ': ' . ($sub?->name ?? '—');
                            } else {
                                $lines[] = ($unit->unitType?->name ?? '—') . ': ' . $unit->name;
                            }
                        }
                        return implode("\n", $lines) ?: '—';
                    })
                    ->wrap(),
                Tables\Columns\TextColumn::make('availment_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn($state) => match($state) {
                        'pending'   => 'Pending',
                        'signed'    => 'Signed',
                        'valid'     => 'Valid',
                        'invalid'   => 'Rejected',
                        'returned'  => 'Returned',
                        'processed' => 'Processed',
                        'cancelled' => 'Cancelled',
                        default     => ucfirst($state),
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'pending'   => 'warning',
                        'signed'    => 'info',
                        'valid'     => 'success',
                        'invalid'   => 'danger',
                        'returned'  => 'gray',
                        'processed' => 'primary',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('edit_fee')
                    ->label('Request Fee Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->visible(fn(Procedure $record) =>
                        (auth()->user()->can('claims.valid') || auth()->user()->can('claims.request-fee'))
                        && in_array($record->status, [Procedure::STATUS_SIGN, Procedure::STATUS_PENDING])
                        && ! $record->hasPendingFeeAdjustment()
                    )
                    ->fillForm(fn(Procedure $record) => ['current_fee' => $record->applied_fee])
                    ->form([
                        Forms\Components\TextInput::make('current_fee')
                            ->label('Current Fee')
                            ->prefix('₱')
                            ->disabled(),
                        Forms\Components\TextInput::make('proposed_fee')
                            ->label('Proposed Fee')
                            ->numeric()
                            ->prefix('₱')
                            ->required()
                            ->minValue(0),
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason / Justification')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Procedure $record, array $data) {
                        \App\Models\FeeAdjustmentRequest::create([
                            'procedure_id' => $record->id,
                            'current_fee' => $record->applied_fee,
                            'proposed_fee' => $data['proposed_fee'],
                            'reason' => $data['reason'],
                            'requested_by' => auth()->id(),
                        ]);

                        $approvers = User::permission('claims.approve-fee')->get();
                        $feeApprovalsUrl = \App\Filament\Pages\FeeAdjustmentApprovals::getUrl();
                        foreach ($approvers as $approver) {
                            Notification::make()
                                ->title('New Fee Adjustment Request')
                                ->body('A fee adjustment was requested for approval code ' . ($record->approval_code ?? '—') . ' by ' . auth()->user()->name)
                                ->warning()
                                ->actions([NotificationAction::make('view')->label('Review Request')->url($feeApprovalsUrl)])
                                ->sendToDatabase($approver);
                        }

                        Notification::make()
                            ->title('Fee Adjustment Requested')
                            ->body('Your request has been submitted for approval.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('request_validation')
                    ->label('Request Validation')
                    ->icon('heroicon-o-arrow-up-circle')
                    ->color('info')
                    ->visible(fn(Procedure $record) =>
                        auth()->user()->can('claims.request-validation')
                        && $record->status === Procedure::STATUS_PENDING
                        && ! $record->validation_requested
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Request Validation')
                    ->modalDescription('This will send the procedure for validation by management.')
                    ->action(function (Procedure $record) {
                        $record->update(['validation_requested' => true]);

                        $approvers = User::role([\App\Models\Role::UPPER_MANAGEMENT, \App\Models\Role::MIDDLE_MANAGEMENT])->get();
                        Notification::make()
                            ->title('Procedure Validation Request')
                            ->body("Procedure {$record->approval_code} for " . trim($record->member?->first_name . ' ' . $record->member?->last_name) . ' is requesting validation.')
                            ->warning()
                            ->actions([NotificationAction::make('view')->label('Review')->url(\App\Filament\Pages\PendingProcedures::getUrl() . '?validation=1')])
                            ->sendToDatabase($approvers);

                        Notification::make()
                            ->title('Validation Requested')
                            ->success()
                            ->send();
                    }),

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

                        if ($record->status !== Procedure::STATUS_SIGN) {
                            return [];
                        }

                        return [
                            // 🟢 VALID
                            Tables\Actions\Action::make('mark_valid_modal')
                                ->label('Valid')
                                ->icon('heroicon-o-check-circle')
                                ->color('success')
                                ->visible(auth()->user()->can('claims.valid'))
                                ->requiresConfirmation()
                                ->modalHeading('Confirm Validation')
                                ->modalDescription('Are you sure you want to mark this claim as valid?')
                                ->action(function (Procedure $record) {
                                    $record->update(['status' => Procedure::STATUS_VALID]);

                                    Notification::make()
                                        ->title('Claim Marked as Valid')
                                        ->success()
                                        ->send();
                                })
                                ->successNotificationTitle('Claim Marked as Valid'),

                            // 🔴 REJECTED
                            Tables\Actions\Action::make('mark_invalid_modal')
                                ->label('Rejected')
                                ->icon('heroicon-o-x-circle')
                                ->visible(auth()->user()->can('claims.reject'))
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

                                    // Return deducted quantity (family-aware for SHARED)
                                    $member = \App\Models\Member::find($record->member_id);
                                    if ($member && $member->account) {
                                        ServiceQuantityService::returnQuantity($member, $record->service_id);

                                        if ($member->account->mbl_type === 'Fixed') {
                                            \App\Services\ProcedureService::returnMbl($member, $record->applied_fee);
                                        }
                                    }

                                    Notification::make()
                                        ->title('Claim Rejected Successfully')
                                        ->body('The service quantity has been returned to the account.')
                                        ->danger()
                                        ->send();
                                })
                                ->closeModalByClickingAway(false)
                                ->successNotificationTitle('Claim Rejected Successfully'),

                            // 🟡 RETURN
                            Tables\Actions\Action::make('mark_returned_modal')
                                ->label('Return')
                                ->visible(auth()->user()->can('claims.return'))
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
                                        'previous_status' => $record->status,
                                        'status' => Procedure::STATUS_RETURN,
                                        'remarks' => $data['remarks'],
                                    ]);

                                    Notification::make()
                                        ->title('Claim Returned Successfully')
                                        ->warning()
                                        ->send();
                                })
                                ->closeModalByClickingAway(false)
                                ->successNotificationTitle('Claim Returned Successfully'),
                        ];
                    })

                    ->modalSubmitAction(false)
            ])
            ->headerActions([
                Tables\Actions\Action::make('preview_adc')
                    ->label('Preview ADC')
                    ->color('info')
                    ->icon('heroicon-o-eye')
                    ->visible(auth()->user()->can('claims.generate'))
                    ->disabled(fn() => $this->isResultHasValid)
                    ->mountUsing(fn() => $this->previewAdc())
                    ->modalHeading('ADC Print Preview')
                    ->modalWidth('7xl')
                    ->modalContent(fn() => view('filament.pages.partials.adc-preview', ['previewData' => $this->previewData]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                Tables\Actions\Action::make('generate_adc')
                    ->label('Print ADC')
                    ->color('success')
                    ->icon('heroicon-o-printer')
                    ->visible(auth()->user()->can('claims.generate'))
                    ->modalHeading('Print Dentist Claims')
                    ->modalSubmitActionLabel('Print Now')
                    ->disabled(fn() => $this->isResultHasValid)
                    ->form(function () {
                        $printers = \App\Services\PrinterService::getAvailablePrinters();
                        $default  = \App\Services\PrinterService::getPrinter();

                        if (empty($printers)) {
                            return [
                                Forms\Components\TextInput::make('printer')
                                    ->label('Printer Name')
                                    ->placeholder('e.g. EPSON_L365_Series_8')
                                    ->required()
                                    ->helperText('No printers auto-detected. Enter the printer name manually.'),
                            ];
                        }

                        return [
                            Forms\Components\Select::make('printer')
                                ->label('Select Printer')
                                ->options(array_combine($printers, $printers))
                                ->default($default)
                                ->required()
                                ->helperText('Choose the network printer to send this ADC to.'),
                        ];
                    })
                    ->action(function (array $data) {
                        if (empty($data['printer'])) {
                            Notification::make()->warning()->title('No printer selected.')->send();
                            return;
                        }
                        $this->selectedPrinter = $data['printer'];
                        $this->dispatch('start-printing');
                        $this->generateClaims(Procedure::STATUS_VALID);
                    }),
                Tables\Actions\Action::make('generate_return')
                    ->label('Generate Return')
                    ->color('warning')
                    ->icon('heroicon-o-check-badge')
                    ->modalHeading('Generate Return Claims')
                    ->modalSubmitActionLabel('Print Now')
                    ->visible(function () {
                        if (! $this->hasSearched) {
                            return false;
                        }

                        $searchData = $this->data;

                        $query = Procedure::query()
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
                            );

                        return $query->where('status', Procedure::STATUS_RETURN)->exists();
                    })
                    ->form(function () {
                        $printers = \App\Services\PrinterService::getAvailablePrinters();
                        $default  = \App\Services\PrinterService::getPrinter();

                        if (empty($printers)) {
                            return [
                                Forms\Components\TextInput::make('printer')
                                    ->label('Printer Name')
                                    ->placeholder('e.g. EPSON_L365_Series_8')
                                    ->required()
                                    ->helperText('No printers auto-detected. Enter the printer name manually.'),
                            ];
                        }

                        return [
                            Forms\Components\Select::make('printer')
                                ->label('Select Printer')
                                ->options(array_combine($printers, $printers))
                                ->default($default)
                                ->required()
                                ->helperText('Choose the network printer to send this return claim to.'),
                        ];
                    })
                    ->action(function (array $data) {
                        if (empty($data['printer'])) {
                            Notification::make()->warning()->title('No printer selected.')->send();
                            return;
                        }
                        $this->selectedPrinter = $data['printer'];
                        $this->generateClaims(Procedure::STATUS_RETURN);
                    }),
            ])
            ->defaultSort('availment_date', 'desc')
            ->deferLoading()
            ->defaultPaginationPageOption(25);
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


    public function previewAdc(): void
    {
        if (!$this->hasSearched) {
            Notification::make()->title('Search Required')->body('Please apply search filters before previewing.')->warning()->send();
            return;
        }

        $data = $this->data;
        $claims = Procedure::query()
            ->with(['member.account', 'clinic', 'service', 'units.unitType'])
            ->when($data['member_name'] ?? null, fn($q, $name) => $q->whereHas('member', fn($sub) => $sub->where('first_name', 'like', "%{$name}%")->orWhere('last_name', 'like', "%{$name}%")->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$name}%"])))
            ->when($data['approval_code'] ?? null, fn($q, $code) => $q->where('approval_code', 'like', "%{$code}%"))
            ->when($data['clinic_id'] ?? null, fn($q, $id) => $q->where('clinic_id', $id))
            ->when($data['status'] ?? null, fn($q, $s) => $q->where('status', $s))
            ->when(isset($data['availment_from'], $data['availment_to']), fn($q) => $q->whereBetween('availment_date', [$data['availment_from'], $data['availment_to']]))
            ->where('status', Procedure::STATUS_VALID)
            ->get()
            ->map(function ($procedure) {
                $vatRate = $this->parseVatType($procedure->clinic->vat_type ?? null);
                $ewtRate = $this->parsePercentage($procedure->clinic->withholding_tax ?? null);
                $serviceFee = $procedure->applied_fee;
                $vatAmount = $serviceFee * $vatRate;
                $ewtAmount = $serviceFee * $ewtRate;
                $procedure->clinic_service_fee = $serviceFee;
                $procedure->vat_amount = $vatAmount;
                $procedure->ewt_amount = $ewtAmount;
                $procedure->net = round(($serviceFee + $vatAmount) - $ewtAmount, 2);
                return $procedure;
            });

        if ($claims->isEmpty()) {
            Notification::make()->title('No Data')->body('No valid procedures found.')->warning()->send();
            return;
        }

        $clinicDetails = Clinic::find($data['clinic_id']);
        $dentist = $clinicDetails?->dentists->where('is_owner', 1)->first();

        $this->previewData = [
            'claims'         => $claims->map(fn($p) => [
                'availment_date'     => $p->availment_date,
                'member_name'        => $p->member->first_name . ' ' . $p->member->last_name,
                'company_name'       => $p->member->account->company_name ?? '—',
                'service_name'       => $p->service->name ?? '—',
                'units'              => self::formatUnits($p->units),
                'clinic_service_fee' => $p->clinic_service_fee,
                'vat_amount'         => $p->vat_amount,
                'ewt_amount'         => $p->ewt_amount,
                'net'                => $p->net,
                'adc_number_from'    => $p->adc_number_from,
            ])->toArray(),
            'clinic_name'    => $clinicDetails?->clinic_name,
            'dentist_name'   => $dentist ? $dentist->first_name . ' ' . $dentist->last_name : '—',
            'registered_name' => $clinicDetails?->registered_name,
            'tin'            => $clinicDetails?->tax_identification_no,
            'is_branch'      => $clinicDetails?->is_branch,
            'address'        => $clinicDetails?->complete_address,
            'vat_type'       => $clinicDetails?->vat_type,
            'ewt'            => $clinicDetails?->withholding_tax,
            'from'           => $data['availment_from'] ?? null,
            'to'             => $data['availment_to'] ?? null,
            'total_fee'      => $claims->sum('clinic_service_fee'),
            'total_vat'      => $claims->sum('vat_amount'),
            'total_ewt'      => $claims->sum('ewt_amount'),
            'total_net'      => $claims->sum('net'),
        ];
    }

    public function generateClaims(string $status)
    {
        $this->dispatch('update-progress', status: 'Fetching claims...', progress: 10);

        $data = $this->data;

        if (! $this->hasSearched) {
            \Filament\Notifications\Notification::make()
                ->title('Search Required')
                ->body('Please apply search filters before generating the SOA.')
                ->warning()
                ->send();
            return;
        }

        $this->dispatch('update-progress', status: 'Processing claims data...', progress: 30);

        // Fetch all matching procedures
        $claims = Procedure::query()
            ->with(['member', 'clinic', 'service', 'clinic.services'])
            ->when($data['member_name'] ?? null, function ($q, $name) {
                $q->whereHas('member', function ($sub) use ($name) {
                    $sub->where('first_name', 'like', "%{$name}%")
                        ->orWhere('last_name', 'like', "%{$name}%")
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$name}%"]);
                });
            })
            ->when($data['approval_code'] ?? null, fn($q, $code) => $q->where('approval_code', 'like', "%{$code}%"))
            ->when($data['clinic_id'] ?? null, fn($q, $clinic_id) => $q->where('clinic_id', $clinic_id))
            ->when($data['status'] ?? null, fn($q, $status) => $q->where('status', $status))
            ->when(isset($data['availment_from'], $data['availment_to']), function ($q) use ($data) {
                $q->whereBetween('availment_date', [$data['availment_from'], $data['availment_to']]);
            })
            ->where('status', $status)
            ->get()
            ->map(function ($procedure) {

                /** GET CLINIC SERVICE FEE */
                $clinicService = $procedure->clinic
                    ? $procedure->clinic->services()->where('service_id', $procedure->service_id)->first()
                    : null;

                $serviceFee = $procedure->applied_fee;

                // Percentages
                $vatRate = $this->parseVatType($procedure->clinic->vat_type ?? null);
                $ewtRate = $this->parsePercentage($procedure->clinic->withholding_tax ?? null);

                // Amounts
                $vatAmount = $serviceFee * $vatRate;
                $ewtAmount = $serviceFee * $ewtRate;

                // NET = Service Fee + VAT − EWT
                $net = ($serviceFee + $vatAmount) - $ewtAmount;

                // Assign computed values
                $procedure->clinic_service_fee = $serviceFee;
                $procedure->vat_rate           = $vatRate;
                $procedure->vat_amount         = $vatAmount;
                $procedure->ewt_rate           = $ewtRate;
                $procedure->ewt_amount         = $ewtAmount;
                $procedure->net                = round($net, 2);
                return $procedure;
            });

        if ($claims->isEmpty()) {
            $this->dispatch('close-printing');
            \Filament\Notifications\Notification::make()
                ->title('No Data')
                ->body('No valid procedures were found.')
                ->warning()
                ->send();
            return;
        }

        $this->dispatch('update-progress', status: 'Calculating totals...', progress: 50);
        /** -----------------------------------------
         *  ADD TOTALS (RATE, EWT, NET)
         * -----------------------------------------*/
        $totalClinicFee = $claims->sum('clinic_service_fee');
        $totalVat       = $claims->sum('vat_amount');
        $totalEwt       = $claims->sum('ewt_amount');
        $totalNet       = $claims->sum('net');

        /** -----------------------------------------
         * LIST OF ACCOUNTS (GROUPED BY ACCOUNT ID)
         * -----------------------------------------*/
        $accounts = $claims->groupBy(fn($item) => $item->member->account_id)
            ->map(function ($items) {
                return [
                    'account_id'   => $items->first()->member->account->id,
                    'account_name' => $items->first()->member->account->company_name ?? 'Unknown Account',
                    'hip' => $items->first()->member->account->hip?->name ?? 'Unknown HIP',
                    'total_rate'   => $items->sum('clinic_service_fee'),
                    'total_vat'    => $items->sum('vat_amount'),
                    'total_ewt'    => $items->sum('ewt_amount'),
                    'total_net'    => $items->sum('net'),
                ];
            });

        // 
        $grandTotalRate = $accounts->sum('total_rate');
        $grandTotalVat  = $accounts->sum('total_vat');
        $grandTotalEwt  = $accounts->sum('total_ewt');
        $grandTotalNet  = $accounts->sum('total_net');


        // dd($claims, $accounts, $totalClinicFee, $totalEwt, $totalNet);

        /** Create the SOA — created before PDF so we have the ID for the sequence number */
        $this->dispatch('update-progress', status: 'Creating SOA record...', progress: 70);

        // Guard: block if any procedure is already linked to a printing SOA
        $printingSOA = \App\Models\GeneratedSoa::where('status', 'printing')
            ->whereHas('procedures', fn($q) => $q->whereIn('procedures.id', $claims->pluck('id')))
            ->first();

        if ($printingSOA) {
            $this->dispatch('close-printing');
            \Filament\Notifications\Notification::make()
                ->warning()
                ->title('Print Already In Progress')
                ->body('These claims are already linked to a SOA that is currently printing. Please wait for it to complete.')
                ->send();
            return;
        }

        $soa = GeneratedSoa::create([
            'clinic_id'    => $data['clinic_id'],
            'from_date'    => $data['availment_from'],
            'to_date'      => $data['availment_to'],
            'total_amount' => $totalClinicFee,
            'generated_by' => auth()->id(),
            'status'       => 'generated',
        ]);

        $this->dispatch('update-progress', status: 'Generating PDF...', progress: 85);

        // Generate PDF
        return $this->generateSOAAfterProcessing(
            $claims,
            $totalClinicFee,
            $totalVat,
            $totalEwt,
            $totalNet,
            $accounts,
            $soa,
            $grandTotalRate,
            $grandTotalVat,
            $grandTotalEwt,
            $grandTotalNet,
            $status
        );
    }
    public function generateSOAAfterProcessing(
        $claims,
        $totalClinicFee,
        $totalVat,
        $totalEwt,
        $totalNet,
        $accounts,
        GeneratedSoa $soa,
        $grandTotalRate,
        $grandTotalVat,
        $grandTotalEwt,
        $grandTotalNet,
        $status
    ) {
        $this->dispatch('update-progress', status: 'Preparing document data...', progress: 86);

        $data = $this->data;
        $clinicDetails = Clinic::find($data['clinic_id']);
        $dentist = $clinicDetails->dentists->where('is_owner', 1)->first();
        $preparedBy = auth()->user()->name ?? 'System Generated';
        $timestamp = now()->format('Y-m-d_His');

        $sequenceNumber = 'ADC' . str_pad($soa->id, 10, '0', STR_PAD_LEFT);

        // Views
        $financeView = $status == Procedure::STATUS_VALID ? 'pdf.adc.adc_finance' : null;
        $dentistView = $status == Procedure::STATUS_VALID ? 'pdf.adc.adc_dentist' : null;

        $this->dispatch('update-progress', status: 'Generating finance PDF...', progress: 88);

        // ----------------- Closure to generate merged PDF -----------------
        $generatePdf = function ($copyLabel) use (
            $financeView,
            $dentistView,
            $claims,
            $data,
            $clinicDetails,
            $dentist,
            $soa,
            $totalClinicFee,
            $totalVat,
            $totalEwt,
            $totalNet,
            $accounts,
            $grandTotalRate,
            $grandTotalVat,
            $grandTotalEwt,
            $grandTotalNet,
            $sequenceNumber,
            $preparedBy
        ) {
            $financePdf = Pdf::loadView($financeView, [
                'claims' => $claims,
                'from' => $data['availment_from'],
                'to' => $data['availment_to'],
                'clinicDetails' => $clinicDetails,
                'dentist' => $dentist,
                'soa' => $soa,
                'totalClinicFee' => $totalClinicFee,
                'totalVat' => $totalVat,
                'totalEwt' => $totalEwt,
                'totalNet' => $totalNet,
                'accounts' => $accounts,
                'grandTotalRate' => $grandTotalRate,
                'grandTotalVat' => $grandTotalVat,
                'grandTotalEwt' => $grandTotalEwt,
                'grandTotalNet' => $grandTotalNet,
                'sequenceNumber' => $sequenceNumber,
                'preparedBy' => $preparedBy,
                'copyLabel' => $copyLabel,
            ])->setPaper('a4', 'landscape')->output();

            $dentistPdf = Pdf::loadView($dentistView, [
                'claims' => $claims,
                'from' => $data['availment_from'],
                'to' => $data['availment_to'],
                'clinicDetails' => $clinicDetails,
                'dentist' => $dentist,
                'soa' => $soa,
                'totalClinicFee' => $totalClinicFee,
                'totalVat' => $totalVat,
                'totalEwt' => $totalEwt,
                'totalNet' => $totalNet,
                'accounts' => $accounts,
                'grandTotalRate' => $grandTotalRate,
                'grandTotalVat' => $grandTotalVat,
                'grandTotalEwt' => $grandTotalEwt,
                'grandTotalNet' => $grandTotalNet,
                'sequenceNumber' => $sequenceNumber,
                'preparedBy' => $preparedBy,
                'copyLabel' => $copyLabel,
            ])->setPaper('a4', 'landscape')->output();

            $pdf = new SectionedFpdi();
            $addSection = function ($pdfContent) use ($pdf) {
                $pageCount = $pdf->setSourceFile(StreamReader::createByString($pdfContent));
                for ($i = 1; $i <= $pageCount; $i++) {
                    $tpl = $pdf->importPage($i);
                    $size = $pdf->getTemplateSize($tpl);
                    $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
                    $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                    $pdf->useTemplate($tpl);
                }
            };

            $addSection($financePdf);
            $addSection($dentistPdf);

            return $pdf;
        };

        // ----------------- Save & Print Original -----------------
        $this->dispatch('update-progress', status: 'Creating original copy...', progress: 90);

        $originalPdf = $generatePdf('ORIGINAL');
        $originalFileName = 'ADC_ORIGINAL_' . $timestamp . '.pdf';
        $originalPath = 'adc/originals/' . $originalFileName;

        $this->dispatch('update-progress', status: 'Saving original PDF...', progress: 92);
        Storage::disk('public')->put($originalPath, $originalPdf->Output('S'));

        $this->dispatch('update-progress', status: 'Updating claim status...', progress: 97);

        foreach ($claims as $procedure) {
            $soa->procedures()->attach($procedure->id, ['amount' => $procedure->clinic_service_fee]);
        }

        Procedure::whereIn('id', $claims->pluck('id'))->update(['adc_number' => $sequenceNumber]);

        $this->dispatch('update-progress', status: 'Creating duplicate copy...', progress: 96);

        $duplicatePdf      = $generatePdf('DUPLICATE');
        $duplicateFileName = 'ADC_DUPLICATE_' . $timestamp . '.pdf';
        $duplicatePath     = 'adc/duplicates/' . $duplicateFileName;
        Storage::disk('public')->put($duplicatePath, $duplicatePdf->Output('S'));

        $this->dispatch('update-progress', status: 'Logging print activity...', progress: 98);

        $soa->increment('print_count');

        if (\App\Filament\Pages\PrinterSettings::isSimulating()) {
            $soa->update([
                'status'              => 'processed',
                'file_path'           => $originalPath,
                'duplicate_file_path' => $duplicatePath,
            ]);
            Procedure::whereIn('id', $claims->pluck('id'))->update(['status' => 'processed']);
            DB::table('print_logs')->insert([
                'user_id'     => auth()->id(),
                'document_id' => $soa->id,
                'copy_type'   => 'ORIGINAL',
                'printer'     => 'SIMULATED',
                'cups_job_id' => null,
                'status'      => 'completed',
                'created_at'  => now(),
            ]);
            Notification::make()->success()->title('ADC Processed (Simulated)')->body('Print simulation enabled — claims marked as processed.')->send();
            $this->dispatch('update-progress', status: 'Complete!', progress: 100);
            $this->dispatch('close-printing');
            return;
        }

        $this->dispatch('update-progress', status: 'Sending to printer...', progress: 95);

        $clinicPrinter = $clinicDetails->printer_name ?? null;
        $printerName = $this->selectedPrinter ?? \App\Services\PrinterService::getPrinter($clinicPrinter);

        if (!$printerName) {
            $soa->delete();
            $this->dispatch('close-printing');
            Notification::make()->warning()->title('No Printer Found')->body('No available printer was found. Claims were not processed.')->send();
            Log::error('No available printer for ADC', ['clinic_id' => $data['clinic_id']]);
            return;
        }

        if (!\App\Services\PrinterService::isPrinterOnline($printerName)) {
            $soa->delete();
            $this->dispatch('close-printing');
            Notification::make()->danger()->title('Printer Offline')->body("'{$printerName}' is currently offline. Please check the printer and try again. Claims were not processed.")->send();
            Log::warning('Printer is offline', ['printer' => $printerName]);
            return;
        }

        $absoluteOriginalPath = storage_path('app/public/' . $originalPath);
        $lpOutput = [];
        exec(
            'lp -o landscape -d ' . escapeshellarg($printerName) . ' ' . escapeshellarg($absoluteOriginalPath) . ' 2>&1',
            $lpOutput,
            $statusCode
        );

        if ($statusCode !== 0) {
            $soa->delete();
            $this->dispatch('close-printing');
            Notification::make()->warning()->title('Print Failed')->body('The print job was rejected by the printer. Claims were not processed.')->send();
            Log::error('ADC printing failed', ['printer' => $printerName, 'output' => $lpOutput]);
            return;
        }

        $jobId = null;
        foreach ($lpOutput as $line) {
            if (preg_match('/request id is (\S+)/i', $line, $matches)) {
                $jobId = $matches[1];
                break;
            }
        }

        Log::info('ADC print job queued', ['printer' => $printerName, 'job' => $jobId, 'soa' => $soa->id]);

        $soa->update([
            'status'              => 'printing',
            'file_path'           => $originalPath,
            'duplicate_file_path' => $duplicatePath,
        ]);

        DB::table('print_logs')->insert([
            'user_id'      => auth()->id(),
            'document_id'  => $soa->id,
            'copy_type'    => 'ORIGINAL',
            'printer'      => $printerName,
            'cups_job_id'  => $jobId,
            'status'       => 'sent',
            'created_at'   => now(),
        ]);

        Notification::make()->success()->title('ADC Sent to Printer')->body('Document sent to ' . $printerName . '. Status will update automatically once printing is confirmed.')->send();

        $this->dispatch('update-progress', status: 'Complete!', progress: 100);
        $this->dispatch('close-printing');
    }



    private static function formatUnits($units): string
    {
        $lines = [];
        foreach ($units as $unit) {
            if ($unit->pivot->surface_id) {
                // Surface: Tooth 12 | Surface: Mesial
                $surface = \App\Models\Unit::with('unitType')->find($unit->pivot->surface_id);
                $surfaceLabel = $surface?->unitType?->name ?? 'Surface';
                $lines[] = 'Tooth ' . ($unit->name ?? '—') . ' | ' . $surfaceLabel . ': ' . ($surface?->name ?? '—');
            } elseif ($unit->pivot->unit_id && $unit->unitType?->name === 'Canal') {
                // Canal: Tooth 12 | Canal: MB
                $tooth = \App\Models\Unit::with('unitType')->find($unit->pivot->unit_id);
                $lines[] = 'Tooth ' . ($tooth?->name ?? '—') . ' | Canal: ' . ($unit->name ?? '—');
            } else {
                $lines[] = ($unit->unitType?->name ?? '—') . ': ' . ($unit->name ?? '—');
            }
        }
        return implode(', ', $lines) ?: '—';
    }

    private function parsePercentage(?string $value): float
    {
        if (! $value || $value === 'ZERO') {
            return 0;
        }

        // Extract numeric value from strings like "12%", "5%"
        return (float) str_replace('%', '', $value) / 100;
    }

    private function parseVatType(?string $vatType): float
    {
        return match ($vatType) {
            'VAT 12%'  => 0.12,
            'VAT ZERO',
            'VAT EXEMPT',
            'NON-VAT',
            null       => 0,
            default    => 0,
        };
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check()
            && auth()->user()->can('claims.search');
    }

    public static function canAccess(): bool
    {
        return auth()->user()->can('claims.search');
    }
}
