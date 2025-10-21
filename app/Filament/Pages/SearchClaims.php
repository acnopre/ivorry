<?php

namespace App\Filament\Pages;

use App\Models\Procedure;
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

class SearchClaims extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $title = 'Search Claims';
    protected static string $view = 'filament.pages.search-claims';
    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';
    protected static ?string $navigationGroup = 'Claims Management';

    public ?array $data = [];
    public bool $hasSearched = false;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function search(): void
    {
        $formData = $this->form->getState();

        // ✅ Only trigger search if at least one field has a value
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
                        Forms\Components\TextInput::make('member_name')
                            ->placeholder('Enter Member Name'),

                        // ✅ Free-text Clinic Name
                        Forms\Components\TextInput::make('clinic_name')
                            ->label('Clinic Name')
                            ->placeholder('Enter Clinic Name'),

                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'denied' => 'Denied',
                            ])
                            ->label('Claim Status')
                            ->placeholder('Any Status'),

                            Forms\Components\DatePicker::make('availment_from')
                            ->label('Availment From')
                            ->placeholder('Start Date'),
                    
                        Forms\Components\DatePicker::make('availment_to')
                            ->label('Availment To')
                            ->placeholder('End Date'),
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
                    return Procedure::query()->whereRaw('1 = 0'); // empty
                }

                $searchData = $this->data;

                return Procedure::query()
                    ->when(
                        $searchData['member_name'] ?? null,
                        fn(Builder $q, $name) => $q->whereHas('member', fn($r) => $r->where('name', 'like', "%{$name}%"))
                    )
                    ->when(
                        $searchData['clinic_name'] ?? null,
                        fn(Builder $q, $clinicName) => $q->whereHas('clinic', fn($r) => $r->where('clinic_name', 'like', "%{$clinicName}%"))
                    )
                    ->when(
                        $searchData['status'] ?? null,
                        fn(Builder $q, $status) => $q->where('status', $status)
                    )
                    ->when(
                        isset($searchData['availment_from'], $searchData['availment_to']),
                        fn(Builder $q) => $q->whereBetween('availment_date', [
                            $searchData['availment_from'],
                            $searchData['availment_to'],
                        ])
                    )
                    ->limit(1);
            })
            ->columns([
              
                Tables\Columns\TextColumn::make('clinic.clinic_name')
                    ->label('Clinic Name')
                    ->sortable(),

                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service Claimed')
                    ->limit(30),

                Tables\Columns\TextColumn::make('availment_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'approved' => 'success',
                        'denied' => 'danger',
                        default => 'warning',
                    }),
            ])
            ->actions([
                // ✅ Custom ViewAction showing Account + Services
                Tables\Actions\ViewAction::make()
                ->modalHeading('Claim Details')
                ->modalContent(function (Procedure $record) {

                    $account = $record->member->account;
                    $services = $record->member->account->services ?? collect();
                    $member = $record->member;
                    $units = $record->units ?? collect();
            
                    return view('filament.modals.claim-view-tabs', [
                        'record' => $record,
                        'account' => $account,
                        'services' => $services,
                        'units' => $units,
                        'member' => $member
                    ]);
                })
                ->modalSubmitAction(false),

                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn(Procedure $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Claim?')
                    ->modalDescription('Are you sure you want to approve this claim?')
                    ->modalSubmitActionLabel('Yes, Approve')
                    ->action(function (Procedure $record) {
                        $record->update(['status' => 'approved']);
                        Notification::make()
                            ->title('Claim Approved')
                            ->body('The claim has been approved successfully.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn(Procedure $record): bool => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('remarks')
                            ->label('Rejection Remarks')
                            ->required()
                            ->placeholder('Enter reason for denial...'),
                    ])
                    ->requiresConfirmation()
                    ->action(function (Procedure $record, array $data) {
                        $record->update([
                            'status' => 'denied',
                            'remarks' => $data['remarks'] ?? null,
                        ]);

                        Notification::make()
                            ->title('Claim Rejected')
                            ->body('The claim has been denied with remarks.')
                            ->danger()
                            ->send();
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('generate_soa')
                    ->label('Download SOA')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Generate Statement of Account')
                    ->modalDescription('Download SOA as PDF based on the selected filters and date range.')
                    ->action('generateSOA'),
            ])
            ->defaultSort('availment_date', 'desc');
    }

    public function generateSOA()
    {
        $data = $this->data;

        if (empty($data['availment_from']) || empty($data['availment_to'])) {
            Notification::make()
                ->title('Date Range Required')
                ->body('Please select both start and end dates before generating an SOA.')
                ->warning()
                ->send();
            return;
        }

        $claims = Procedure::query()
            ->with(['member', 'clinic', 'service'])
            ->whereBetween('availment_date', [
                $data['availment_from'],
                $data['availment_to'],
            ])
            ->where('status', 'approved')
            ->get();

        if ($claims->isEmpty()) {
            Notification::make()
                ->title('No Valid Claims')
                ->body('No approved claims found within the selected period.')
                ->warning()
                ->send();
            return;
        }

        $pdf = Pdf::loadView('pdf.soa', [
            'claims' => $claims,
            'from' => $data['availment_from'],
            'to' => $data['availment_to'],
        ]);

        $filename = 'Statement_of_Account_' . now()->format('Y-m-d_His') . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['Super Admin', 'Claims Processor']);
    }
}