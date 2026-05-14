<?php

namespace App\Filament\Resources\MemberResource\Pages;

use App\Filament\Resources\AccountResource;
use App\Filament\Resources\MemberResource;
use App\Models\Member;
use Filament\Infolists\Components\{Section, TextEntry, Grid, BadgeEntry, View, Tabs};
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;

class ViewMember extends ViewRecord
{
    protected static string $resource = MemberResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        $record = $this->record;

        // All accounts linked to this card number (including current)
        $relatedMembers = Member::with('account.hip')
            ->where('card_number', $record->card_number)
            ->get();

        $currentTab = Tabs\Tab::make($record->account?->company_name ?? 'Current Account')
            ->badge('Current')
            ->badgeColor('success')
            ->schema([
                $this->accountInfoSection($record->account, $record),
                View::make('filament.infolists.services-table'),
            ]);

        $otherTabs = $relatedMembers
            ->where('id', '!=', $record->id)
            ->map(fn($m) => Tabs\Tab::make($m->account?->company_name ?? 'Account #' . $m->account_id)
                ->schema([
                    $this->accountInfoSection($m->account, $m),
                    View::make('filament.infolists.member-account-services')
                        ->viewData(['member' => $m]),
                ])
            )->values()->all();

        return $infolist
            ->schema([
                Section::make('Member Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('card_number')->label('Card Number')->placeholder('—'),
                                TextEntry::make('coc_number')->label('COC Number')->placeholder('—')->visible(fn($record) => filled($record->coc_number)),
                                TextEntry::make('member_type')->label('Member Type')->badge(),
                                TextEntry::make('status')
                                    ->badge()
                                    ->formatStateUsing(fn($state) => ucfirst($state ?? 'unknown'))
                                    ->color(fn($state) => match (strtolower($state ?? '')) {
                                        'active'   => 'success',
                                        'inactive' => 'danger',
                                        default    => 'gray',
                                    })
                                    ->helperText(
                                        fn($record) => $record->renewal_id
                                            ? '🔄 Staged for renewal — will activate on ' . \Carbon\Carbon::parse(
                                                \App\Models\AccountRenewal::find($record->renewal_id)?->effective_date
                                            )->format('M d, Y')
                                            : null
                                    ),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('first_name')->label('First Name'),
                                TextEntry::make('middle_name')->label('Middle Name')->placeholder('—'),
                                TextEntry::make('last_name')->label('Last Name'),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('suffix')->label('Suffix')->placeholder('—'),
                                TextEntry::make('birthdate')->date()->placeholder('—'),
                                TextEntry::make('gender')->label('Gender')->placeholder('—'),
                            ]),
                    ]),

                Section::make('Contact Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('email')->placeholder('—'),
                                TextEntry::make('phone')->placeholder('—'),
                            ]),
                    ]),

                Section::make('Contract Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('effective_date')->date()->placeholder('—'),
                                TextEntry::make('expiration_date')->label('Valid Until Date')->date()->placeholder('—'),
                                TextEntry::make('inactive_date')->date()->placeholder('—'),
                                TextEntry::make('endorsement_deletion_date')
                                    ->label('Endorsement Deletion Date')
                                    ->date()
                                    ->placeholder('—')
                                    ->visible(fn($record) => filled($record->endorsement_deletion_date))
                                    ->color('danger'),
                            ]),
                    ]),

                Section::make('Accounts')
                    ->schema([
                        Tabs::make('accounts_tabs')
                            ->tabs(array_merge([$currentTab], $otherTabs))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private function accountInfoSection(?\App\Models\Account $account, Member $member): Section
    {
        return Section::make('Account Information')
            ->schema([
                Grid::make(3)
                    ->schema([
                        TextEntry::make('account.company_name')
                            ->label('Company Name')
                            ->state($account?->company_name)
                            ->url($account ? AccountResource::getUrl('view', ['record' => $account->id]) : null)
                            ->openUrlInNewTab(),
                        TextEntry::make('account.policy_code')
                            ->label('Policy Code')
                            ->state($account?->policy_code),
                        TextEntry::make('account.hip.name')
                            ->label('HIP')
                            ->state($account?->hip?->name),
                        TextEntry::make('account.plan_type')
                            ->label('Plan Type')
                            ->state($account?->plan_type)
                            ->badge(),
                        TextEntry::make('account.mbl_type')
                            ->label('MBL Type')
                            ->state($account?->mbl_type)
                            ->badge(),
                        TextEntry::make('account.mbl_amount_' . $member->id)
                            ->label('MBL Amount')
                            ->state($account?->mbl_type === 'Fixed' ? $account?->mbl_amount : null)
                            ->money('PHP')
                            ->visible($account?->mbl_type === 'Fixed'),
                        TextEntry::make('mbl_balance_' . $member->id)
                            ->label('MBL Balance')
                            ->state($account?->mbl_type === 'Fixed' ? $member->mbl_balance : null)
                            ->money('PHP')
                            ->visible($account?->mbl_type === 'Fixed')
                            ->color(fn($state) => ($state ?? 0) < ($account?->mbl_amount * 0.2) ? 'danger' : 'success'),
                        TextEntry::make('account.account_status_' . $member->id)
                            ->label('Account Status')
                            ->state($account?->account_status)
                            ->badge()
                            ->formatStateUsing(fn($state) => ucfirst($state ?? ''))
                            ->color(fn($state) => match ($state) {
                                'active'   => 'success',
                                'expired'  => 'danger',
                                'inactive' => 'warning',
                                default    => 'gray',
                            }),
                    ]),
            ])
            ->compact();
    }
}
