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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;

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

                                    $this->dispatch('closeModal');
                                    // $this->dispatch('refreshTable');
                                }),

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
                Tables\Actions\Action::make('generate_adc')
                    ->label('Generate ADC')
                    ->color('success')
                    ->icon('heroicon-o-check-badge')
                    ->requiresConfirmation()
                    ->visible('claims.generate')
                    ->modalHeading('Approve Dentist Claims')
                    ->modalDescription('Once confirmed, all displayed procedures will be marked as PROCESSED before the ADC is created.')

                    ->modalSubmitActionLabel('Yes, Approve Claims')
                    ->visible(function () {
                        if (! $this->hasSearched) {
                            return false;
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
                            );

                        return $query->where('status', Procedure::STATUS_VALID)->exists();
                    })
                    ->action(fn() => $this->generateClaims(Procedure::STATUS_VALID)),
                Tables\Actions\Action::make('generate_return')
                    ->label('Generate Return')
                    ->color('warning')
                    ->icon('heroicon-o-check-badge')
                    ->visible(auth()->user()->can('claims.return'))
                    ->requiresConfirmation()
                    ->modalHeading('Generate Return')
                    ->modalDescription('Are you sure you want to generate return claims?')

                    ->modalSubmitActionLabel('Yes, Generate')
                    ->visible(function () {
                        if (! $this->hasSearched) {
                            return false;
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
                            );

                        // ✅ Show button if at least 1 RETURN exists
                        return $query->where('status', Procedure::STATUS_RETURN)->exists();
                    })
                    ->action(fn() => $this->generateClaims(Procedure::STATUS_RETURN)),
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


    public function generateClaims(string $status)
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
            \Filament\Notifications\Notification::make()
                ->title('No Data')
                ->body('No valid procedures were found.')
                ->warning()
                ->send();
            return;
        }
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

        /** Create the SOA */
        $soa = GeneratedSoa::create([
            'clinic_id' => $data['clinic_id'],
            'from_date' => $data['availment_from'],
            'to_date' => $data['availment_to'],
            'total_amount' => $totalClinicFee,
            'generated_by' => auth()->id(),
            'status' => 'generated',
        ]);

        /** Attach procedures */
        foreach ($claims as $procedure) {
            $soa->procedures()->attach($procedure->id, [
                'amount' => $procedure->clinic_service_fee
            ]);
        }

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
        $data = $this->data;
        $clinicDetails = Clinic::find($data['clinic_id']);
        $dentist = $clinicDetails->dentists->where('is_owner', 1)->first();
        $preparedBy = auth()->user()->name ?? 'System Generated';
        $timestamp = now()->format('Y-m-d_His');
        Procedure::whereIn('id', $claims->pluck('id'))->update(['status' => 'processed']);

        // ----------------- Sequence Number -----------------
        $sequenceNumber = 'ADC' . str_pad($soa->id, 10, '0', STR_PAD_LEFT);

        // ----------------- Views -----------------
        if ($status == Procedure::STATUS_VALID) {
            $originalView = 'pdf.adc.adc';
            $duplicateView = 'pdf.adc.adc_duplicate';
        } else {
            $originalView = 'pdf.adc.return';
            $duplicateView = 'pdf.adc.return_duplicate';
        }

        // ----------------- Determine Print Count & Copy Label -----------------
        $printCount = $soa->print_count + 1;
        $copyLabelOriginal = $printCount === 1
            ? 'ORIGINAL'
            : 'DUPLICATE #' . $printCount;

        // ----------------- ORIGINAL PDF -----------------
        $pdfOriginal = Pdf::loadView($originalView, [
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
            'copyLabel' => $copyLabelOriginal,
        ])->setPaper('a4', 'landscape');

        $originalFileName = 'ADC_ORIGINAL_' . $timestamp . '.pdf';
        $originalPath = 'adc/originals/' . $originalFileName;
        Storage::disk('public')->put($originalPath, $pdfOriginal->output());
        $absoluteOriginalPath = storage_path('app/public/' . $originalPath);

        // ----------------- Dynamic Printer Selection -----------------
        $clinicPrinter = $clinicDetails->printer_name ?? null;
        $printerName = \App\Services\PrinterService::getPrinter($clinicPrinter);

        if ($printerName) {
            // Print ORIGINAL PDF
            exec(
                'lp -o landscape -d ' . escapeshellarg($printerName) . ' ' . escapeshellarg($absoluteOriginalPath),
                $output,
                $statusCode
            );

            if ($statusCode !== 0) {
                Notification::make()
                    ->title('ADC Printing Failed')
                    ->body('Please try to print again')
                    ->warning()
                    ->send();
                Log::error('ADC printing failed', [
                    'soa_id' => $soa->id,
                    'printer' => $printerName,
                    'output' => $output,
                ]);
            }
            // Update procedures status → processed
        } else {
            Notification::make()
                ->title('ADC Printing Failed')
                ->body('No available printer for ADC ID ' . $soa->id)
                ->warning()
                ->send();
            Log::error('No available printer for ADC ID ' . $soa->id);
        }

        // ----------------- Increment print count & log -----------------
        $soa->increment('print_count');

        DB::table('print_logs')->insert([
            'user_id' => auth()->id(),
            'document_id' => $soa->id,
            'copy_type' => $copyLabelOriginal,
            'printer' => $printerName ?? 'NO_PRINTER_AVAILABLE',
            'created_at' => now(),
        ]);

        // ----------------- DUPLICATE PDF (Stored Only) -----------------
        $pdfDuplicate = Pdf::loadView($duplicateView, [
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
            'copyLabel' => 'DUPLICATE #' . $printCount,
        ])->setPaper('a4', 'landscape');

        $duplicateFileName = 'ADC_DUPLICATE_' . $timestamp . '.pdf';
        $duplicatePath = 'adc/duplicates/' . $duplicateFileName;
        Storage::disk('public')->put($duplicatePath, $pdfDuplicate->output());

        // ----------------- Update SOA paths -----------------
        $soa->update([
            'original_file_path' => $originalPath,
            'duplicate_file_path' => $duplicatePath,
        ]);

        // ----------------- Return ORIGINAL download -----------------
        return Storage::disk('public')->download($duplicatePath, $duplicateFileName);
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

    public static function canViewAny(): bool
    {
        return auth()->user()->can('claims.search');
    }
}
