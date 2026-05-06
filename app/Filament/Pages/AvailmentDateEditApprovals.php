<?php

namespace App\Filament\Pages;

use App\Models\AvailmentDateEditRequest;
use App\Models\Procedure;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class AvailmentDateEditApprovals extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $title = 'Date Edit Approvals';
    protected static string $view = 'filament.pages.availment-date-edit-approvals';
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Claims Management';
    protected static ?string $navigationLabel = 'Date Edit Approvals';
    protected static ?int $navigationSort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(AvailmentDateEditRequest::query()->with(['procedure.member', 'procedure.clinic', 'procedure.service', 'requestedBy', 'reviewedBy']))
            ->columns([
                Tables\Columns\TextColumn::make('procedure.approval_code')
                    ->label('Approval Code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('procedure.member.first_name')
                    ->label('Member')
                    ->formatStateUsing(fn($state, $record) => trim(($record->procedure?->member?->first_name ?? '') . ' ' . ($record->procedure?->member?->last_name ?? '')))
                    ->searchable(),
                Tables\Columns\TextColumn::make('procedure.clinic.clinic_name')
                    ->label('Clinic')
                    ->searchable(),
                Tables\Columns\TextColumn::make('procedure.service.name')
                    ->label('Service')
                    ->limit(20),
                Tables\Columns\TextColumn::make('current_date')
                    ->label('Current Date')
                    ->date('M d, Y'),
                Tables\Columns\TextColumn::make('proposed_date')
                    ->label('Proposed Date')
                    ->date('M d, Y'),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Reason')
                    ->limit(30)
                    ->tooltip(fn($record) => $record->reason),
                Tables\Columns\TextColumn::make('requestedBy.name')
                    ->label('Requested By'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date Requested')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn($state) => ucfirst($state))
                    ->color(fn(string $state) => match ($state) {
                        'pending'  => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default    => 'gray',
                    }),
                Tables\Columns\TextColumn::make('reviewedBy.name')
                    ->label('Reviewed By')
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending'  => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->default('pending'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Date Edit')
                    ->modalDescription(fn($record) => 'Approve date change from ' . $record->current_date?->format('M d, Y') . ' to ' . $record->proposed_date?->format('M d, Y') . '?')
                    ->action(function (AvailmentDateEditRequest $record) {
                        $record->update([
                            'status'      => 'approved',
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);

                        $record->procedure->update([
                            'availment_date' => $record->proposed_date,
                        ]);

                        $approvalCode  = $record->procedure->approval_code ?? '—';
                        $proposedDate  = $record->proposed_date?->format('M d, Y');

                        if ($record->requestedBy) {
                            Notification::make()
                                ->title('Availment Date Edit Approved')
                                ->body("Your availment date edit request for approval code {$approvalCode} has been approved. New date: {$proposedDate}")
                                ->success()
                                ->actions([NotificationAction::make('view')->label('View Approvals')->url(AvailmentDateEditApprovals::getUrl())])
                                ->sendToDatabase($record->requestedBy);
                        }

                        Notification::make()
                            ->title('Availment Date Edit Approved')
                            ->body("Availment date updated to {$proposedDate}")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn($record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('review_remarks')
                            ->label('Reason for Rejection')
                            ->required()
                            ->rows(3),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Reject Date Edit')
                    ->action(function (AvailmentDateEditRequest $record, array $data) {
                        $record->update([
                            'status'         => 'rejected',
                            'reviewed_by'    => auth()->id(),
                            'review_remarks' => $data['review_remarks'],
                            'reviewed_at'    => now(),
                        ]);

                        $approvalCode = $record->procedure->approval_code ?? '—';

                        if ($record->requestedBy) {
                            Notification::make()
                                ->title('Availment Date Edit Rejected')
                                ->body("Your availment date edit request for approval code {$approvalCode} was rejected. Reason: {$data['review_remarks']}")
                                ->danger()
                                ->actions([NotificationAction::make('view')->label('View Approvals')->url(AvailmentDateEditApprovals::getUrl())])
                                ->sendToDatabase($record->requestedBy);
                        }

                        Notification::make()
                            ->title('Availment Date Edit Rejected')
                            ->danger()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->can('claims.approve-availment-date');
    }

    public static function canAccess(): bool
    {
        return auth()->user()->can('claims.approve-availment-date');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = \App\Models\AvailmentDateEditRequest::where('status', 'pending')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
