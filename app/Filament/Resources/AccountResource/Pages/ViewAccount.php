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
use App\Services\MblBalanceService;
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

                    $createdByEmail = $record->createdBy?->email;
                    if ($createdByEmail) {
                        Mail::raw("The account {$record->company_name} has been approved.", function ($message) use ($createdByEmail) {
                            $message->to($createdByEmail)
                                ->subject('Account Approved');
                        });
                    }

                    $this->redirect(AccountResource::getUrl('index'));
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

                    $createdByEmail = $record->createdBy?->email;
                    if ($createdByEmail) {
                        Mail::raw("The account {$record->company_name} has been rejected. Reason: {$data['remarks']}", function ($message) use ($createdByEmail) {
                            $message->to($createdByEmail)
                                ->subject('Account Rejected');
                        });
                    }

                    $this->redirect(AccountResource::getUrl('index'));
                }),

            Actions\Action::make('rejectRenewal')
                ->label('Reject Renewal')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(
                    fn(Account $record) =>
                    $record->endorsement_type === 'RENEWAL'
                        && $record->endorsement_status === 'PENDING'
                        && auth()->user()->can('account.renew')
                        && $record->renewals->first() != null
                )
                ->form([
                    Textarea::make('remarks')
                        ->label('Remarks')
                        ->required()
                        ->maxLength(1000)
                        ->placeholder('Enter reason for rejection...'),
                ])
                ->requiresConfirmation()
                ->action(function (Account $record, array $data) {
                    $renewal = AccountRenewal::where('account_id', $record->id)
                        ->where('status', 'PENDING')
                        ->firstOrFail();

                    $renewal->update([
                        'status' => 'REJECTED',
                        'remarks' => $data['remarks'],
                    ]);

                    // Revert account to previous approved state
                    $lastApprovedEndorsement = $record->endorsement_type === 'RENEWAL'
                        ? ($record->renewals()->where('status', 'APPROVED')->exists() ? 'RENEWED' : 'NEW')
                        : $record->endorsement_type;

                    $record->update([
                        'endorsement_type' => $lastApprovedEndorsement,
                        'endorsement_status' => 'APPROVED',
                        'remarks' => $data['remarks'],
                    ]);

                    Notification::make()
                        ->title('Account renewal rejected.')
                        ->danger()
                        ->send();

                    $createdByEmail = $record->createdBy?->email;
                    if ($createdByEmail) {
                        Mail::raw("The renewal for account {$record->company_name} has been rejected. Reason: {$data['remarks']}", function ($message) use ($createdByEmail) {
                            $message->to($createdByEmail)
                                ->subject('Account Renewal Rejected');
                        });
                    }

                    $this->redirect(AccountResource::getUrl('index'));
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
                            'service_id' => $service->service_id,
                            'quantity' => $service->quantity ?? null,
                            'remarks' => 'Renewed to default quantity',
                            'action' => 'renewal',
                            'effective_date' => $record->effective_date,
                            'expiration_date' => $record->expiration_date,
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
                            'default_quantity' => $service['default_quantity'] ?? $service['quantity'],
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
                    $record->account_status = 'active';
                    $record->effective_date = $renewal->effective_date;
                    $record->expiration_date = $renewal->expiration_date;
                    $record->save();

                    Notification::make()
                        ->title('Account renewal approved successfully.')
                        ->success()
                        ->send();

                    $createdByEmail = $record->createdBy?->email;
                    if ($createdByEmail) {
                        Mail::raw("The account {$record->company_name} has been renewed and approved.", function ($message) use ($createdByEmail) {
                            $message->to($createdByEmail)
                                ->subject('Account Renewal Approved');
                        });
                    }

                    $this->redirect(AccountResource::getUrl('index'));
                }),

            Actions\Action::make('rejectAmendment')
                ->label('Reject Amendment')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(
                    fn(Model $record) =>
                    $record->endorsement_type === 'AMENDMENT'
                        && $record->endorsement_status === 'PENDING'
                        && auth()->user()->can('account.amend')
                )
                ->form([
                    Textarea::make('remarks')
                        ->label('Remarks')
                        ->required()
                        ->maxLength(1000)
                        ->placeholder('Enter reason for rejection...'),
                ])
                ->requiresConfirmation()
                ->action(function (Account $record, array $data) {
                    $amendment = AccountAmendment::where('account_id', $record->id)
                        ->where('endorsement_status', 'PENDING')
                        ->latest()
                        ->first();

                    $amendment->update([
                        'endorsement_status' => 'REJECTED',
                        'remarks' => $data['remarks'],
                    ]);

                    // Revert account to previous approved state
                    $lastApprovedEndorsement = 'NEW';
                    if ($record->renewals()->where('status', 'APPROVED')->exists()) {
                        $lastApprovedEndorsement = 'RENEWED';
                    }
                    if (AccountAmendment::where('account_id', $record->id)
                        ->where('endorsement_status', 'APPROVED')
                        ->exists()
                    ) {
                        $lastApprovedEndorsement = 'AMENDED';
                    }

                    $record->update([
                        'endorsement_type' => $lastApprovedEndorsement,
                        'endorsement_status' => 'APPROVED',
                        'remarks' => $data['remarks'],
                    ]);

                    Notification::make()
                        ->title('Account amendment rejected.')
                        ->danger()
                        ->send();

                    $createdByEmail = $record->createdBy?->email;
                    if ($createdByEmail) {
                        Mail::raw("The amendment for account {$record->company_name} has been rejected. Reason: {$data['remarks']}", function ($message) use ($createdByEmail) {
                            $message->to($createdByEmail)
                                ->subject('Account Amendment Rejected');
                        });
                    }

                    $this->redirect(AccountResource::getUrl('index'));
                }),

            Actions\Action::make('approveAmendment')
                ->label('Approve Amendment')
                ->requiresConfirmation()
                ->visible(
                    fn(Model $record) =>
                    $record->endorsement_type === 'AMENDMENT'
                        && $record->endorsement_status === 'PENDING'
                        && auth()->user()->can('account.amend')
                )
                ->action(function (Account $record) {
                    $amendment = AccountAmendment::where('account_id', $record->id)
                        ->where('endorsement_status', 'PENDING')
                        ->latest()
                        ->first();

                    $updateData = [
                        'company_name' => $amendment->company_name,
                        'policy_code' => $amendment->policy_code,
                        'hip_id' => $amendment->hip_id,
                        'card_used' => $amendment->card_used,
                        'effective_date' => $amendment->effective_date,
                        'expiration_date' => $amendment->expiration_date,
                        'endorsement_type' => 'AMENDED',
                        'endorsement_status' => 'APPROVED',
                        'account_status' => 'active'
                    ];

                    // Update MBL if changed
                    if ($amendment->mbl_type) {
                        $updateData['mbl_type'] = $amendment->mbl_type;
                    }
                    if ($amendment->mbl_amount) {
                        $updateData['mbl_amount'] = $amendment->mbl_amount;
                        if ($amendment->mbl_type === 'Fixed') {
                            $updateData['mbl_balance'] = $amendment->mbl_amount;
                        }
                    }

                    // Handle MBL type change
                    if ($amendment->mbl_type && $record->mbl_type !== $amendment->mbl_type) {
                        $effectiveDate = $amendment->effective_date ?? $record->effective_date;
                        MblBalanceService::handleMblTypeChange(
                            $record->id,
                            $record->mbl_type,
                            $amendment->mbl_type,
                            $amendment->mbl_amount,
                            $effectiveDate
                        );
                    }

                    $record->update($updateData);

                    $accountService = AccountService::where('account_id', $record->id);
                    if ($accountService) {
                        $accountService->delete();
                    }

                    foreach ($amendment->services as $srv) {
                        AccountService::create([
                            'account_id' => $record->id,
                            'service_id' => $srv['service_id'],
                            'quantity' => $srv['quantity'],
                            'default_quantity' => $srv['default_quantity'],
                            'is_unlimited' => $srv['is_unlimited'],
                            'remarks' => $srv['remarks'],
                        ]);
                    }

                    $amendment->update([
                        'endorsement_status' => 'APPROVED',
                        'approved_by' => auth()->id(),
                    ]);
                    $amendment->services()->delete();
                    $amendment->delete();

                    Notification::make()
                        ->title('Account amendment approved successfully.')
                        ->success()
                        ->send();

                    $createdByEmail = $record->createdBy?->email;
                    if ($createdByEmail) {
                        Mail::raw("The amendment for account {$record->company_name} has been approved.", function ($message) use ($createdByEmail) {
                            $message->to($createdByEmail)
                                ->subject('Account Amendment Approved');
                        });
                    }

                    $this->redirect(AccountResource::getUrl('index'));
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
                        Tabs\Tab::make('Account Information')
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

                                                TextEntry::make('hip.name')
                                                    ->label('HIP'),

                                                TextEntry::make('card_used')
                                                    ->label('Card Used'),

                                                TextEntry::make('plan_type')
                                                    ->label('Plan Type')
                                                    ->badge()
                                                    ->colors([
                                                        'info' => fn($state): bool => $state === 'INDIVIDUAL',
                                                        'warning' => fn($state): bool => $state === 'SHARED',
                                                    ]),

                                                TextEntry::make('coverage_period_type')
                                                    ->label('Coverage Type')
                                                    ->badge(),

                                                TextEntry::make('endorsement_type')
                                                    ->label('Endorsement Type')
                                                    ->badge()
                                                    ->colors([
                                                        'success' => fn($state): bool => $state === 'NEW',
                                                        'warning' => fn($state): bool => $state === 'RENEWAL',
                                                        'info'    => fn($state): bool => $state === 'AMENDMENT',
                                                    ]),

                                                TextEntry::make('mbl_type')
                                                    ->label('MBL Type')
                                                    ->badge()
                                                    ->formatStateUsing(fn($state) => ucfirst($state))
                                                    ->colors([
                                                        'info' => fn($state): bool => strtolower($state) === 'procedural',
                                                        'success' => fn($state): bool => strtolower($state) === 'fixed',
                                                    ])
                                                    ->visible(fn($record) => $record->mbl_type),

                                                TextEntry::make('mbl_amount')
                                                    ->label('MBL Amount')
                                                    ->money('PHP')
                                                    ->visible(fn($record) => $record->mbl_type === 'Fixed'),
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
                                Section::make('Account Information')
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

                                                TextEntry::make('hip_amendment')
                                                    ->label('HIP')
                                                    ->default($amendmentAccount?->hip?->name),

                                                TextEntry::make('card_used_amendment')
                                                    ->label('Card Used')
                                                    ->default($amendmentAccount?->card_used),

                                                TextEntry::make('plan_type_amendment')
                                                    ->label('Plan Type')
                                                    ->badge()
                                                    ->colors([
                                                        'info' => fn($state): bool => $state === 'INDIVIDUAL',
                                                        'warning' => fn($state): bool => $state === 'SHARED',
                                                    ])
                                                    ->default($amendmentAccount?->plan_type ?? $this->record->plan_type),

                                                TextEntry::make('coverage_period_type_amendment')
                                                    ->label('Coverage Type')
                                                    ->badge()
                                                    ->default($amendmentAccount?->coverage_period_type),

                                                TextEntry::make('endorsement_type_amendment')
                                                    ->label('Endorsement Type')
                                                    ->badge()
                                                    ->colors([
                                                        'success' => fn($state): bool => $state === 'NEW',
                                                        'warning' => fn($state): bool => $state === 'RENEWAL',
                                                        'info'    => fn($state): bool => $state === 'AMENDMENT',
                                                    ])
                                                    ->default($amendmentAccount?->endorsement_type),

                                                TextEntry::make('mbl_type_amendment')
                                                    ->label('MBL Type')
                                                    ->badge()
                                                    ->formatStateUsing(fn($state) => ucfirst($state))
                                                    ->colors([
                                                        'info' => fn($state): bool => strtolower($state) === 'procedural',
                                                        'success' => fn($state): bool => strtolower($state) === 'fixed',
                                                    ])
                                                    ->default($amendmentAccount?->mbl_type)
                                                    ->visible(fn() => $amendmentAccount?->mbl_type),

                                                TextEntry::make('mbl_amount_amendment')
                                                    ->label('MBL Amount')
                                                    ->money('PHP')
                                                    ->default($amendmentAccount?->mbl_amount)
                                                    ->visible(fn() => $amendmentAccount?->mbl_type === 'Fixed'),
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
                                                    ->default($amendmentAccount?->remarks)
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

                                                TextEntry::make('hip.name')
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

                                                TextEntry::make('mbl_type')
                                                    ->label('MBL Type')
                                                    ->badge()
                                                    ->formatStateUsing(fn($state) => ucfirst($state))
                                                    ->colors([
                                                        'info' => fn($state): bool => strtolower($state) === 'procedural',
                                                        'success' => fn($state): bool => strtolower($state) === 'fixed',
                                                    ]),

                                                TextEntry::make('mbl_amount')
                                                    ->label('MBL Amount')
                                                    ->money('PHP')
                                                    ->visible(fn($record) => $record->mbl_type === 'Fixed'),
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

                        // Usage History Tab
                        Tabs\Tab::make('Usage History')
                            ->schema([
                                Section::make('Service Usage History')
                                    ->schema([
                                        ViewEntry::make('usage_history')
                                            ->label(false)
                                            ->view('filament.infolists.usage-history', [
                                                'record' => $this->record,
                                            ]),
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
                $end = optional($item->expiration_date)->format('M d, Y');
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
