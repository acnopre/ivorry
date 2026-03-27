<?php

namespace App\Filament\Resources\MemberResource\Pages;

use App\Filament\Resources\MemberResource;
use Filament\Infolists\Components\{Section, TextEntry, Grid, BadgeEntry, View};
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;

class ViewMember extends ViewRecord
{
    protected static string $resource = MemberResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
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
                                        'active' => 'success',
                                        'inactive' => 'danger',
                                        default => 'gray',
                                    }),
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

                Section::make('Account Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('account.company_name')->label('Company Name'),
                                TextEntry::make('account.policy_code')->label('Policy Code'),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('account.effective_date')->date()->label('Account Effective Date'),
                                TextEntry::make('account.expiration_date')->date()->label('Account Expiration Date'),
                                TextEntry::make('account.account_status')
                                    ->label('Account Status')
                                    ->badge()
                                    ->color(fn($state) => match ($state) {
                                        'active' => 'success',
                                        'expired' => 'danger',
                                        default => 'warning',
                                    }),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('account.plan_type')->label('Plan Type')->badge(),
                                TextEntry::make('account.mbl_type')->label('MBL Type')->badge(),
                                TextEntry::make('account.mbl_amount')
                                    ->label('MBL Amount')
                                    ->money('PHP')
                                    ->visible(fn($record) => $record->account?->mbl_type === 'Fixed'),
                            ]),
                        Grid::make(1)
                            ->schema([
                                TextEntry::make('mbl_balance')
                                    ->label('MBL Balance')
                                    ->money('PHP')
                                    ->visible(fn($record) => $record->account?->mbl_type === 'Fixed')
                                    ->color(fn($state, $record) => $state < ($record->account?->mbl_amount * 0.2) ? 'danger' : 'success'),
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
                                TextEntry::make('expiration_date')->date()->placeholder('—'),
                                TextEntry::make('inactive_date')->date()->placeholder('—'),
                            ]),
                    ]),

                Section::make('Assigned Account Services')
                    ->schema([
                        View::make('filament.infolists.services-table'),
                    ])
                    ->collapsible(),
            ]);
    }
}
