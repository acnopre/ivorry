<?php

namespace App\Filament\Pages;

use App\Models\FeeAdjustmentRequest;
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

class FeeAdjustmentApprovals extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $title = 'Fee Adjustment Approvals';
    protected static string $view = 'filament.pages.fee-adjustment-approvals';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Claims Management';
    protected static ?int $navigationSort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(FeeAdjustmentRequest::query()->with(['procedure.member', 'procedure.clinic', 'procedure.service', 'requestedBy', 'reviewedBy']))
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
                Tables\Columns\TextColumn::make('current_fee')
                    ->label('Current Fee')
                    ->money('PHP'),
                Tables\Columns\TextColumn::make('proposed_fee')
                    ->label('Proposed Fee')
                    ->money('PHP'),
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
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('reviewedBy.name')
                    ->label('Reviewed By')
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
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
                    ->modalHeading('Approve Fee Adjustment')
                    ->modalDescription(fn($record) => "Approve fee change from ₱" . number_format($record->current_fee, 2) . " to ₱" . number_format($record->proposed_fee, 2) . "?")
                    ->action(function (FeeAdjustmentRequest $record) {
                        $record->update([
                            'status' => 'approved',
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);

                        $record->procedure->update([
                            'applied_fee' => $record->proposed_fee,
                            'is_fee_adjusted' => true,
                            'last_updated_by' => auth()->id(),
                        ]);

                        $approvalCode = $record->procedure->approval_code ?? '—';
                        $newFee = '₱' . number_format($record->proposed_fee, 2);

                        if ($record->requestedBy) {
                            Notification::make()
                                ->title('Fee Adjustment Approved')
                                ->body("Your fee adjustment request for approval code {$approvalCode} has been approved. New fee: {$newFee}")
                                ->success()
                                ->actions([NotificationAction::make('view')->label('View Approvals')->url(FeeAdjustmentApprovals::getUrl())])
                                ->sendToDatabase($record->requestedBy);
                        }

                        $clinicUser = $record->procedure->clinic?->user;
                        if ($clinicUser && $clinicUser->id !== ($record->requestedBy?->id)) {
                            $clinicUrl = $clinicUser->hasRole('Dentist')
                                ? ClinicProfile::getUrl()
                                : \App\Filament\Resources\ClinicsResource::getUrl('edit', ['record' => $record->procedure->clinic]);

                            Notification::make()
                                ->title('Service Fee Adjusted')
                                ->body("The applied fee for approval code {$approvalCode} at your clinic has been adjusted to {$newFee}.")
                                ->success()
                                ->actions([NotificationAction::make('view')->label('View Clinic')->url($clinicUrl)])
                                ->sendToDatabase($clinicUser);
                        }

                        Notification::make()
                            ->title('Fee Adjustment Approved')
                            ->body("Applied fee has been updated to {$newFee}")
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
                    ->modalHeading('Reject Fee Adjustment')
                    ->action(function (FeeAdjustmentRequest $record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'reviewed_by' => auth()->id(),
                            'review_remarks' => $data['review_remarks'],
                            'reviewed_at' => now(),
                        ]);

                        $approvalCode = $record->procedure->approval_code ?? '—';

                        if ($record->requestedBy) {
                            Notification::make()
                                ->title('Fee Adjustment Rejected')
                                ->body("Your fee adjustment request for approval code {$approvalCode} was rejected. Reason: {$data['review_remarks']}")
                                ->danger()
                                ->actions([NotificationAction::make('view')->label('View Approvals')->url(FeeAdjustmentApprovals::getUrl())])
                                ->sendToDatabase($record->requestedBy);
                        }

                        $clinicUser = $record->procedure->clinic?->user;
                        if ($clinicUser && $clinicUser->id !== ($record->requestedBy?->id)) {
                            $clinicUrl = $clinicUser->hasRole('Dentist')
                                ? ClinicProfile::getUrl()
                                : \App\Filament\Resources\ClinicsResource::getUrl('edit', ['record' => $record->procedure->clinic]);

                            Notification::make()
                                ->title('Fee Adjustment Rejected')
                                ->body("A fee adjustment request for approval code {$approvalCode} at your clinic was rejected. Reason: {$data['review_remarks']}")
                                ->danger()
                                ->actions([NotificationAction::make('view')->label('View Clinic')->url($clinicUrl)])
                                ->sendToDatabase($clinicUser);
                        }

                        Notification::make()
                            ->title('Fee Adjustment Rejected')
                            ->danger()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->can('claims.approve-fee');
    }

    public static function canAccess(): bool
    {
        return auth()->user()->can('claims.approve-fee');
    }
}
