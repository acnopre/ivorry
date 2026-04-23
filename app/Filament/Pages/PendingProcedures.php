<?php

namespace App\Filament\Pages;

use App\Models\Procedure;
use App\Models\User;
use App\Services\ServiceQuantityService;
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

class PendingProcedures extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $title = 'Pending Procedures';
    protected static string $view = 'filament.pages.pending-procedures';
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Claims Management';
    protected static ?int $navigationSort = 1;

    public function mount(): void
    {
        if (request()->boolean('validation')) {
            $this->tableFilters['validation_requested']['isActive'] = true;
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Procedure::pending()
                    ->with(['member', 'clinic', 'service', 'units.unitType'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('approval_code')
                    ->label('Approval Code')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Copied!'),
                Tables\Columns\TextColumn::make('member.first_name')
                    ->label('Member')
                    ->formatStateUsing(fn($state, $record) => trim(($record->member?->first_name ?? '') . ' ' . ($record->member?->last_name ?? '')))
                    ->searchable(),
                Tables\Columns\TextColumn::make('clinic.clinic_name')
                    ->label('Clinic')
                    ->searchable()
                    ->limit(25),
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->limit(25),
                Tables\Columns\TextColumn::make('applied_fee')
                    ->label('Fee')
                    ->money('PHP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('availment_date')
                    ->label('Availment Date')
                    ->date('M d, Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('validation_requested')
                    ->label('Validation')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? 'Requested' : '—')
                    ->colors(['info' => true])
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading('Procedure Details')
                    ->modalContent(fn(Procedure $record) => view('filament.pages.partials.pending-procedure-detail', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),

                Tables\Actions\Action::make('approve_validation')
                    ->label('Approve Validation')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn(Procedure $record) =>
                        $record->validation_requested
                        && auth()->user()->hasAnyRole([\App\Models\Role::UPPER_MANAGEMENT, \App\Models\Role::MIDDLE_MANAGEMENT, \App\Models\Role::SUPER_ADMIN])
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Approve Validation')
                    ->modalDescription('This will mark the procedure as Valid.')
                    ->action(function (Procedure $record) {
                        $record->update([
                            'status' => Procedure::STATUS_VALID,
                            'validation_requested' => false,
                            'last_updated_by' => auth()->id(),
                        ]);

                        $this->notifyOnAction($record, 'approved');

                        Notification::make()
                            ->title('Procedure Validated')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(Procedure $record) =>
                        auth()->user()->can('member.approve_procedure')
                        && ! $record->validation_requested
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Approve Procedure')
                    ->modalDescription('This will mark the procedure as Valid.')
                    ->action(function (Procedure $record) {
                        $record->update([
                            'status' => Procedure::STATUS_VALID,
                            'last_updated_by' => auth()->id(),
                        ]);

                        $this->notifyOnAction($record, 'approved');

                        Notification::make()
                            ->title('Procedure Approved')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn() => auth()->user()->can('member.deny_procedure'))
                    ->form([
                        Forms\Components\Textarea::make('remarks')
                            ->label('Reason for Rejection')
                            ->required()
                            ->rows(3),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Reject Procedure')
                    ->action(function (Procedure $record, array $data) {
                        $record->update([
                            'status' => Procedure::STATUS_REJECT,
                            'remarks' => $data['remarks'],
                            'last_updated_by' => auth()->id(),
                        ]);

                        // Return quantity and MBL balance on rejection
                        $member = $record->member;
                        if ($member && $member->account) {
                            $account = $member->account;
                            ServiceQuantityService::returnQuantity($member, $record->service_id);

                            if ($account->mbl_type === 'Fixed') {
                                \App\Services\ProcedureService::returnMbl($member, $record->applied_fee);
                            }
                        }

                        $this->notifyOnAction($record, 'rejected', $data['remarks']);

                        Notification::make()
                            ->title('Procedure Rejected')
                            ->body('Service quantity and MBL balance have been returned.')
                            ->danger()
                            ->send();
                    }),

                Tables\Actions\Action::make('return')
                    ->label('Return')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn() => auth()->user()->can('member.deny_procedure'))
                    ->form([
                        Forms\Components\Textarea::make('remarks')
                            ->label('Reason for Return')
                            ->required()
                            ->rows(3),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Return Procedure')
                    ->action(function (Procedure $record, array $data) {
                        $record->update([
                            'previous_status' => $record->status,
                            'status' => Procedure::STATUS_RETURN,
                            'remarks' => $data['remarks'],
                            'last_updated_by' => auth()->id(),
                        ]);

                        $this->notifyOnAction($record, 'returned', $data['remarks']);

                        Notification::make()
                            ->title('Procedure Returned')
                            ->warning()
                            ->send();
                    }),
            ])
            ->deferLoading()
            ->defaultPaginationPageOption(25)
            ->defaultSort(fn($query) => $query->orderByDesc('validation_requested')->orderByDesc('updated_at'))
            ->filters([
                Tables\Filters\Filter::make('validation_requested')
                    ->label('Validation Requested')
                    ->query(fn($query) => $query->where('validation_requested', true)),
            ]);
    }

    protected function notifyOnAction(Procedure $record, string $action, ?string $remarks = null): void
    {
        $approvalCode = $record->approval_code ?? '—';
        $clinicName = $record->clinic?->clinic_name ?? '—';
        $memberName = trim(($record->member?->first_name ?? '') . ' ' . ($record->member?->last_name ?? ''));

        $color = match ($action) {
            'approved' => 'success',
            'rejected' => 'danger',
            'returned' => 'warning',
        };

        $title = 'Procedure ' . ucfirst($action);
        $body = match ($action) {
            'approved' => "Procedure {$approvalCode} for {$memberName} has been approved.",
            'rejected' => "Procedure {$approvalCode} for {$memberName} has been rejected. Reason: {$remarks}",
            'returned' => "Procedure {$approvalCode} for {$memberName} has been returned. Reason: {$remarks}",
        };

        // Notify dentist (clinic user)
        $clinicUser = $record->clinic?->user;
        if ($clinicUser && $clinicUser->id !== auth()->id()) {
            $dentistUrl = $clinicUser->hasRole('Dentist')
                ? \App\Filament\Resources\ProcedureResource::getUrl('index')
                : static::getUrl();

            Notification::make()
                ->title($title)
                ->body($body)
                ->color($color)
                ->{$color}()
                ->actions([NotificationAction::make('view')->label('View Procedures')->url($dentistUrl)])
                ->sendToDatabase($clinicUser);
        }

        // Notify claims users
        $claimsUsers = User::permission('claims.search')
            ->where('id', '!=', auth()->id())
            ->get();

        foreach ($claimsUsers as $user) {
            Notification::make()
                ->title($title)
                ->body($body)
                ->color($color)
                ->{$color}()
                ->actions([NotificationAction::make('view')->label('View Claims')->url(\App\Filament\Pages\SearchClaims::getUrl())])
                ->sendToDatabase($user);
        }
    }

    public static function getNavigationBadge(): ?string
    {
        if (!auth()->user()?->can('member.view_pending_procedure')) {
            return null;
        }

        $count = Procedure::pending()->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->can('member.view_pending_procedure');
    }

    public static function canAccess(): bool
    {
        return auth()->user()->can('member.view_pending_procedure');
    }
}
