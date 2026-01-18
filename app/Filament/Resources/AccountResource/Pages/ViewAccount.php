<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use App\Models\Account;
use App\Models\AccountAmendment;
use App\Models\AccountRenewal;
use App\Models\AccountRenewalService;
use App\Models\AccountService;
use App\Models\AccountServiceHistory;
use App\Models\Role;
use Filament\Infolists;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Components\Grid;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\Placeholder;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

class ViewAccount extends ViewRecord
{
    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approveAccount')
                ->label('Approve Account')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->visible(fn(Account $record) => $record->account_status === 'inactive' && auth()->user()->can('account.approve'))
                ->requiresConfirmation()
                ->action(function (Account $record) {
                    $record->update([
                        'account_status' => 'active',
                        'endorsement_status' => 'APPROVED'
                    ]);

                    Notification::make()
                        ->title('The account has been approved successfully.')
                        ->success()
                        ->send();

                    Mail::raw("The account {$record->company_name} has been approved.", function ($message) {
                        $message->to('acnopre@upsitf.org')
                            ->subject('Account Approved');
                    });
                }),

            Actions\Action::make('rejectAccount')
                ->label('Reject Account')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(
                    fn(Account $record) =>
                    $record->account_status === 'inactive' &&
                        auth()->user()->can('account.reject')
                )
                ->disabled(fn(Account $record) => $record->endorsement_status === 'REJECTED')
                ->form([
                    Textarea::make('remarks')
                        ->label('Remarks')
                        ->required()
                        ->maxLength(1000)
                        ->placeholder('Enter reason for rejection...'),
                ])
                ->requiresConfirmation()
                ->action(function (Account $record, array $data) {
                    $record->update([
                        'status' => 0,
                        'endorsement_status' => 'REJECTED',
                        'remarks' => $data['remarks'],
                    ]);

                    Notification::make()
                        ->title('Account rejected.')
                        ->danger()
                        ->send();

                    Mail::raw("The account {$record->company_name} has been rejected. Reason: {$data['remarks']}", function ($message) {
                        $message->to('acnopre@upsitf.org')
                            ->subject('Account Rejected');
                    });
                }),

            Actions\Action::make('renewAccount')
                ->label('Renew Account')
                ->color('info')
                ->icon('heroicon-o-arrow-path')
                ->visible(
                    fn(Account $record) =>
                    $record->endorsement_type === 'RENEWAL'
                        && $record->endorsement_status === 'PENDING'
                        && auth()->user()->can('account.renew')
                        && $record->renewals->first() != null
                )
                ->requiresConfirmation()
                ->action(function (array $data, Account $record) {
                    $renewal = AccountRenewal::where('account_id', $record->id)
                        ->where('status', 'PENDING')
                        ->firstOrFail();

                    $renewalServices = AccountRenewalService::where('renewal_id', $renewal->id)->get();
                    $accountService = AccountService::where('account_id', $renewal->account_id);

                    foreach ($accountService->get() as $service) {
                        AccountServiceHistory::create([
                            'account_id' => $record->id,
                            'service_id' => $service->id,
                            'quantity' => $service->quantity ?? null,
                            'remarks' => 'Renewed to default quantity',
                            'action' => 'renewal',
                        ]);
                    }

                    if ($accountService) {
                        $accountService->delete(); // soft delete
                    }

                    foreach ($renewalServices as $service) {
                        AccountService::create([
                            'account_id' => $renewal->account_id,
                            'renewal_id' => $service['renewal_id'],
                            'service_id' => $service['service_id'],
                            'quantity' => $service['quantity'],
                            'is_unlimited' => $service['is_unlimited'],
                            'remarks' => $service['remarks'],
                        ]);
                    }

                    $renewal->update([
                        'status' => 'APPROVED',
                        'approved_by' => auth()->id(),
                    ]);

                    $record->endorsement_type = 'RENEWED';
                    $record->endorsement_status = 'APPROVED';
                    $record->save();

                    Notification::make()
                        ->title('Account renewal approved successfully.')
                        ->success()
                        ->send();

                    Mail::raw("The account {$record->company_name} has been renewed and approved.", function ($message) {
                        $message->to('acnopre@upsitf.org')
                            ->subject('Account Renewal Approved');
                    });
                }),

            Actions\Action::make('approveAmendment')
                ->label('Approve Amendment')
                ->requiresConfirmation()
                ->visible(
                    fn(Model $record) =>
                    $record->endorsement_type === 'AMENDMENT'
                        && auth()->user()->can('account.amend')
                )
                ->action(function (Account $record) {
                    $amendment = AccountAmendment::where('account_id', $record->id)
                        ->where('endorsement_status', 'PENDING')
                        ->latest()
                        ->first();

                    $record->update([
                        'company_name' => $amendment->company_name,
                        'policy_code' => $amendment->policy_code,
                        'hip' => $amendment->hip,
                        'card_used' => $amendment->card_used,
                        'effective_date' => $amendment->effective_date,
                        'expiration_date' => $amendment->expiration_date,
                        'endorsement_type' => 'AMENDED',
                        'endorsement_status' => 'APPROVED',
                    ]);

                    $accountService = AccountService::where('account_id', $record->id);
                    if ($accountService) {
                        $accountService->delete();
                    }

                    foreach ($amendment->services as $srv) {
                        AccountService::create([
                            'account_id' => $record->id,
                            'service_id' => $srv['service_id'],
                            'quantity' => $srv['quantity'],
                            'is_unlimited' => $srv['is_unlimited'],
                            'remarks' => $srv['remarks'],
                        ]);
                    }

                    $amendment->update(['endorsement_status' => 'APPROVED']);

                    Mail::raw("The amendment for account {$record->company_name} has been approved.", function ($message) {
                        $message->to('acnopre@upsitf.org')
                            ->subject('Account Amendment Approved');
                    });
                }),
        ];
    }


    /**
     * Infolist layout
     */
    public function infolist(Infolists\Infolist $infolist): Infolists\Infolist
    {
        $renewalRecord = $this->record->renewals->first();
        $amendmentAccount = AccountAmendment::where('account_id', $this->record->id)->first();
        return $infolist
            ->schema([
                Tabs::make('AccountTabs')
                    ->columnSpanFull()
                    ->tabs([
                        // Account Renewal Tab 
                        Tabs\Tab::make('Account Renewal')
                            ->badge(function ($record) {
                                if ($record->endorsement_status === 'PENDING') {
                                    return 'Pending Update';
                                }
                            })
                            ->badgeColor('warning')
                            ->visible(
                                fn(Account $record) => $record->endorsement_type === 'RENEWAL'
                                    &&  $record->endorsement_status === 'PENDING'
                                    && auth()->user()?->hasAnyRole(Role::SUPER_ADMIN, Role::UPPER_MANAGEMENT, Role::ACCOUNT_MANAGER)
                                    && $renewalRecord != null
                            )
                            ->schema([
                                Section::make('Account Renewal')
                                    ->headerActions(
                                        []
                                    )
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextEntry::make('company_name')
                                                    ->label('Company Name')
                                                    ->weight(FontWeight::Bold)
                                                    ->size(TextEntrySize::Large),

                                                TextEntry::make('policy_code')
                                                    ->label('Policy Code')
                                                    ->copyable()
                                                    ->copyMessage('Policy code copied!'),

                                                TextEntry::make('endorsement_type')
                                                    ->label('Endorsement Type')
                                                    ->badge()
                                                    ->colors([
                                                        'success' => fn($state): bool => $state === 'NEW',
                                                        'warning' => fn($state): bool => $state === 'RENEWAL',
                                                        'info'    => fn($state): bool => $state === 'AMENDMENT',
                                                    ]),
                                            ]),
                                        Grid::make(3)
                                            ->schema([
                                                TextEntry::make('endorsement_status')
                                                    ->label('Endorsement Status')
                                                    ->badge()
                                                    ->formatStateUsing(fn($state) => match ($state) {
                                                        'PENDING' => 'Pending',
                                                        'APPROVED' => 'Approved',
                                                        'REJECTED' => 'Rejected',
                                                        default => $state,
                                                    })
                                                    ->colors([
                                                        'warning' => fn($state) => $state === 'PENDING',
                                                        'success' => fn($state) => $state === 'APPROVED',
                                                        'danger' => fn($state) => $state === 'REJECTED',
                                                    ]),

                                                TextEntry::make('account_status')
                                                    ->label('Account Status')
                                                    ->badge()
                                                    ->formatStateUsing(fn($state) => match ($state) {
                                                        'active' => 'Active',
                                                        'inactive' => 'Inactive',
                                                        'expired' => 'Expired',
                                                        default => $state,
                                                    })
                                                    ->colors([
                                                        'warning' => fn($state) => $state === 'inactive',
                                                        'success' => fn($state) => $state === 'active',
                                                        'danger' => fn($state) => $state === 'expired',
                                                    ]),


                                                TextEntry::make('renewal_effective_date')
                                                    ->label('Effective Date')
                                                    ->date('M d, Y')
                                                    ->icon('heroicon-m-calendar-days')
                                                    ->default($renewalRecord?->effective_date),



                                                TextEntry::make('renewal_expiration_date')
                                                    ->label('Expiration Date')
                                                    ->date('M d, Y')
                                                    ->icon('heroicon-m-calendar-days')
                                                    ->default($renewalRecord?->expiration_date),


                                                TextEntry::make('remarks')
                                                    ->label('Remarks')

                                            ]),
                                    ])
                                    ->columns(false),
                                ViewEntry::make('full_width_tabs_wrapper')
                                    ->columnSpanFull()
                                    ->label(false)
                                    ->view('filament.infolists.renewals.renewal-services', [
                                        'renewal_services' => $renewalRecord
                                    ]),
                            ]),


                        // Account Amendment Tab
                        Tabs\Tab::make('Account Amendment')
                            ->badge(function ($record) {
                                if ($record->endorsement_status === 'PENDING') {
                                    return 'Pending Update';
                                }
                            })
                            ->badgeColor('warning')
                            ->visible(
                                fn(Account $record) => $record->endorsement_type === 'AMENDMENT'
                                    &&  $record->endorsement_status === 'PENDING'
                                    && auth()->user()?->hasAnyRole(Role::SUPER_ADMIN, Role::UPPER_MANAGEMENT, Role::ACCOUNT_MANAGER)
                                    && $amendmentAccount != null
                            )
                            ->schema([
                                Section::make('Account Renewal')
                                    ->headerActions(
                                        []
                                    )
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextEntry::make('company_name_amendment')
                                                    ->label('Company Name')
                                                    ->weight(FontWeight::Bold)
                                                    ->size(TextEntrySize::Large)
                                                    ->default($amendmentAccount?->company_name),

                                                TextEntry::make('policy_code_amendment')
                                                    ->label('Policy Code')
                                                    ->copyable()
                                                    ->copyMessage('Policy code copied!')
                                                    ->default($amendmentAccount?->policy_code),

                                                TextEntry::make('endorsement_type_amendment')
                                                    ->label('Endorsement Type')
                                                    ->badge()
                                                    ->colors([
                                                        'success' => fn($state): bool => $state === 'NEW',
                                                        'warning' => fn($state): bool => $state === 'RENEWAL',
                                                        'info'    => fn($state): bool => $state === 'AMENDMENT',
                                                    ])
                                                    ->default($amendmentAccount?->endorsement_type),

                                            ]),
                                        Grid::make(3)
                                            ->schema([
                                                TextEntry::make('endorsement_status_amendment')
                                                    ->label('Endorsement Status')
                                                    ->badge()
                                                    ->formatStateUsing(fn($state) => match ($state) {
                                                        'PENDING' => 'Pending',
                                                        'APPROVED' => 'Approved',
                                                        'REJECTED' => 'Rejected',
                                                        default => $state,
                                                    })
                                                    ->colors([
                                                        'warning' => fn($state) => $state === 'PENDING',
                                                        'success' => fn($state) => $state === 'APPROVED',
                                                        'danger' => fn($state) => $state === 'REJECTED',
                                                    ])
                                                    ->default($amendmentAccount?->endorsement_status),


                                                TextEntry::make('account_status')
                                                    ->label('Account Status')
                                                    ->badge()
                                                    ->formatStateUsing(fn($state) => match ($state) {
                                                        'active' => 'Active',
                                                        'inactive' => 'Inactive',
                                                        'expired' => 'Expired',
                                                        default => $state,
                                                    })
                                                    ->colors([
                                                        'warning' => fn($state) => $state === 'inactive',
                                                        'success' => fn($state) => $state === 'active',
                                                        'danger' => fn($state) => $state === 'expired',
                                                    ]),


                                                TextEntry::make('reffectve_date_amendment')
                                                    ->label('Effective Date')
                                                    ->date('M d, Y')
                                                    ->icon('heroicon-m-calendar-days')
                                                    ->default($amendmentAccount?->effective_date),



                                                TextEntry::make('expiration_date_amendment')
                                                    ->label('Expiration Date')
                                                    ->date('M d, Y')
                                                    ->icon('heroicon-m-calendar-days')
                                                    ->default($amendmentAccount?->expiration_date),


                                                TextEntry::make('remarks')
                                                    ->label('Remarks')
                                                    ->visible($amendmentAccount?->remarks != null)

                                            ]),
                                    ])
                                    ->columns(false),
                                ViewEntry::make('full_width_tabs_wrapper')
                                    ->columnSpanFull()
                                    ->label(false)
                                    ->view('filament.infolists.amendment.amendment-services', [
                                        'amendment_services' => $amendmentAccount
                                    ]),
                            ]),
                        // Active Account Tab
                        Tabs\Tab::make('Active Account')
                            ->schema([
                                Section::make('Account Overview')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextEntry::make('company_name')
                                                    ->label('Company Name')
                                                    ->weight(FontWeight::Bold)
                                                    ->size(TextEntrySize::Large),

                                                TextEntry::make('policy_code')
                                                    ->label('Policy Code')
                                                    ->copyable()
                                                    ->copyMessage('Policy code copied!'),

                                                TextEntry::make('hip')
                                                    ->label('HIP'),

                                                TextEntry::make('card_used')
                                                    ->label('Card Used'),

                                                TextEntry::make('endorsement_type')
                                                    ->label('Endorsement Type')
                                                    ->badge()
                                                    ->colors([
                                                        'success' => fn($state): bool => $state === 'NEW',
                                                        'warning' => fn($state): bool => $state === 'RENEWAL',
                                                        'info'    => fn($state): bool => $state === 'AMENDMENT',
                                                    ]),


                                                TextEntry::make('plan_type')
                                                    ->label('Plan Type')
                                                    ->badge()
                                                    ->colors([
                                                        'info' => fn($state): bool => $state === 'INDIVIDUAL',
                                                        'warning' => fn($state): bool => $state === 'SHARED',
                                                    ]),
                                            ]),
                                        Grid::make(3)
                                            ->schema([
                                                TextEntry::make('endorsement_status')
                                                    ->label('Endorsement Status')
                                                    ->badge()
                                                    ->formatStateUsing(fn($state) => match ($state) {
                                                        'PENDING' => 'Pending',
                                                        'APPROVED' => 'Approved',
                                                        'REJECTED' => 'Rejected',
                                                        default => $state,
                                                    })
                                                    ->colors([
                                                        'warning' => fn($state) => $state === 'PENDING',
                                                        'success' => fn($state) => $state === 'APPROVED',
                                                        'danger' => fn($state) => $state === 'REJECTED',
                                                    ]),

                                                TextEntry::make('account_status')
                                                    ->label('Account Status')
                                                    ->badge()
                                                    ->formatStateUsing(fn($state) => match ($state) {
                                                        'active' => 'Active',
                                                        'inactive' => 'Inactive',
                                                        'expired' => 'Expired',
                                                        default => $state,
                                                    })
                                                    ->colors([
                                                        'warning' => fn($state) => $state === 'inactive',
                                                        'success' => fn($state) => $state === 'active',
                                                        'danger' => fn($state) => $state === 'expired',
                                                    ]),

                                                TextEntry::make('effective_date')
                                                    ->label('Effective Date')
                                                    ->date('M d, Y')
                                                    ->icon('heroicon-m-calendar-days'),


                                                TextEntry::make('expiration_date')
                                                    ->label('Expiration Date')
                                                    ->date('M d, Y')
                                                    ->icon('heroicon-m-calendar-days'),


                                                TextEntry::make('remarks')
                                                    ->label('Remarks')

                                            ]),
                                    ])
                                    ->columns(false), // Grid handles the columns
                                ViewEntry::make('full_width_tabs_wrapper')
                                    ->columnSpanFull()
                                    ->label(false)
                                    ->view('filament.infolists.account-tabs', [
                                        // Pass necessary data to the custom view
                                        'record' => $this->record,
                                        // Group the history data here and pass it
                                        'renewal_groups' => $this->groupRenewalHistory(),
                                    ]),
                            ]),

                    ]),
            ]);
    }

    /**
     * Group renewal history by effective and expiry date range.
     */
    protected function groupRenewalHistory(): array
    {
        $records = AccountServiceHistory::with('service')
            ->where('account_id', $this->record->id)
            ->orderByDesc('effective_date')
            ->get()
            ->groupBy(function ($item) {
                // Ensure dates exist before formatting
                $start = optional($item->effective_date)->format('M d, Y');
                $end = optional($item->expiry_date)->format('M d, Y');
                return "{$start} - {$end}";
            });

        $groups = [];

        foreach ($records as $period => $items) {
            $groups[$period] = [
                'label' => $period,
                'records' => $items->map(function ($item) {
                    return [
                        'service_name' => $item->service->name ?? 'N/A',
                        'quantity' => $item->quantity,
                        'remarks' => $item->remarks,
                        // Formatted date for display
                        'created_at' => $item->created_at->format('M d, Y H:i'),
                    ];
                })->toArray(),
            ];
        }
        return $groups;
    }
}
