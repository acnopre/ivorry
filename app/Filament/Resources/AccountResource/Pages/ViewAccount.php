<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use App\Models\Account;
use App\Models\AccountServiceHistory;
use Filament\Infolists;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Components\Grid;       // Added
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Filament\Infolists\Components\Placeholder;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;      // Added

class ViewAccount extends ViewRecord
{
    protected static string $resource = AccountResource::class;
    protected static bool $hasPageContentWrapper = false;
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approveAccount')
                ->label('Approve Account')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->visible(fn(Account $record) => $record->status === 'pending')
                ->requiresConfirmation()
                ->action(function (Account $record) {
                    $record->update(['status' => 'approved']);
                    Notification::make()
                        ->title('Account approved successfully.')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('renewAccount')
                ->label('Renew Account')
                ->color('info')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn(Account $record) => $record->endorsement_type === 'RENEWAL')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('effective_date')
                        ->label('Effective Date')
                        ->required(),
                    \Filament\Forms\Components\DatePicker::make('expiry_date')
                        ->label('Expiry Date')
                        ->required()
                        ->after('effective_date'),
                ])
                ->requiresConfirmation()
                ->action(function (array $data, Account $record) {
                    // Store renewal history
                    foreach ($record->services as $service) {
                        AccountServiceHistory::create([
                            'account_id'     => $record->id,
                            'service_id'     => $service->id,
                            'quantity'       => $service->pivot->quantity,
                            'remarks'        => 'Renewed to default quantity',
                            'action'         => 'renewal',
                            'effective_date' => $data['effective_date'],
                            'expiry_date'    => $data['expiry_date'],
                        ]);

                        // Reset service quantity to default
                        $service->pivot->update([
                            'quantity' => $service->pivot->default_quantity,
                        ]);
                    }

                    // Update account dates and status
                    $record->update([
                        'effective_date' => $data['effective_date'],
                        'expiration_date' => $data['expiry_date'],
                        'status' => 'renewed',
                    ]);

                    Notification::make()
                        ->title('Account renewed successfully.')
                        ->success()
                        ->send();
                }),

        ];
    }

    /**
     * Infolist layout
     */
    public function infolist(Infolists\Infolist $infolist): Infolists\Infolist
    {
        return $infolist
            ->schema([
                // Primary Account Information Section (OUTSIDE tabs for prominence)
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

                                TextEntry::make('endorsement_type')
                                    ->label('Endorsement Type')
                                    ->badge()
                                    ->color('info'),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('account_status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn(string $state): string => match ($state) {
                                        'pending' => 'warning',
                                        'approved' => 'success',
                                        'renewed' => 'info',
                                        default => 'gray',
                                    }),

                                TextEntry::make('effective_date')
                                    ->label('Effective Date')
                                    ->date('M d, Y')
                                    ->icon('heroicon-m-calendar-days'),

                                TextEntry::make('expiration_date')
                                    ->label('Expiration Date')
                                    ->date('M d, Y')
                                    ->icon('heroicon-m-calendar-days'),

                            ]),
                    ])
                    ->columns(false), // Grid handles the columns
                ViewEntry::make('full_width_tabs_wrapper')
                    ->label(false)
                    ->view('filament.infolists.account-tabs', [
                        // Pass necessary data to the custom view
                        'record' => $this->record,
                        // Group the history data here and pass it
                        'renewal_groups' => $this->groupRenewalHistory(),
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
