<?php

namespace App\Filament\Pages;

use App\Models\Clinic;
use App\Models\ClinicService;
use App\Models\ClinicServiceFeeHistory;
use App\Models\Procedure;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class ServiceFeeApproval extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.service-fee-approval';
    protected static ?string $navigationLabel = 'Service Fee Approval';
    protected static ?string $navigationGroup = 'Dental Management';
    protected static ?int $navigationSort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ClinicService::query()
                    ->whereNotNull('new_fee')
                    ->whereNull('approved_at')
                    ->with(['clinic', 'service'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('clinic.clinic_name')
                    ->label('Clinic')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->searchable(),

                Tables\Columns\TextColumn::make('fee')
                    ->label('Current Fee')
                    ->money('PHP'),

                Tables\Columns\TextColumn::make('new_fee')
                    ->label('New Fee')
                    ->money('PHP'),

                Tables\Columns\TextColumn::make('diff')
                    ->label('Difference')
                    ->getStateUsing(fn($record) => $record->new_fee - $record->fee)
                    ->formatStateUsing(fn($state) => ($state >= 0 ? '+' : '') . '₱' . number_format($state, 2))
                    ->color(fn($record) => $record->new_fee >= $record->fee ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('effective_date')
                    ->label('Effective Date')
                    ->date('M d, Y')
                    ->placeholder('—'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn() => auth()->user()->can('fee.approval'))
                    ->requiresConfirmation()
                    ->modalHeading('Approve Service Fee')
                    ->modalDescription(fn(ClinicService $record) =>
                        "Approve fee update for {$record->service->name} at {$record->clinic->clinic_name}? " .
                        "₱" . number_format($record->fee, 2) . " → ₱" . number_format($record->new_fee, 2) .
                        ($record->effective_date ? " effective " . Carbon::parse($record->effective_date)->format('M d, Y') : '')
                    )
                    ->form(function (ClinicService $record) {
                        $effectiveDate = $record->effective_date ?? now()->toDateString();
                        $affectedCount = Procedure::where('clinic_id', $record->clinic_id)
                            ->where('service_id', $record->service_id)
                            ->where('status', Procedure::STATUS_PROCESSED)
                            ->whereDate('availment_date', '>=', $effectiveDate)
                            ->count();

                        $notice = $affectedCount > 0
                            ? "There are {$affectedCount} completed claim(s) for this service on or after the effective date. These claims will be updated to reflect the new fee once approved."
                            : 'No completed claims are affected by this fee change.';

                        return [
                            \Filament\Forms\Components\Placeholder::make('summary')
                                ->label('Impact Summary')
                                ->content($notice),
                        ];
                    })
                    ->action(function (ClinicService $record) {
                        $this->approveServiceFee($record);
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn() => auth()->user()->hasAnyRole([Role::UPPER_MANAGEMENT, Role::MIDDLE_MANAGEMENT, Role::SUPER_ADMIN]))
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('Reason for Rejection')
                            ->required()
                            ->rows(3),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Reject Service Fee Update')
                    ->action(function (ClinicService $record, array $data) {
                        $this->rejectServiceFee($record, $data['rejection_reason']);
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected function approveServiceFee(ClinicService $record): void
    {
        DB::transaction(function () use ($record) {
            $effectiveDate = $record->effective_date ?? now()->toDateString();
            $applyNow = Carbon::parse($effectiveDate)->lte(now());

            ClinicServiceFeeHistory::create([
                'clinic_id'      => $record->clinic_id,
                'service_id'     => $record->service_id,
                'old_fee'        => $record->fee,
                'new_fee'        => $record->new_fee,
                'effective_date' => $effectiveDate,
                'approved_by'    => auth()->id(),
                'created_by'     => auth()->id(),
            ]);

            $oldFee = $record->fee;

            if ($applyNow) {
                $newFee = $record->new_fee;

                $record->update([
                    'fee'         => $newFee,
                    'new_fee'     => null,
                    'approved_at' => now(),
                ]);

                // Create adjustment procedures for affected processed procedures
                Procedure::where('clinic_id', $record->clinic_id)
                    ->where('service_id', $record->service_id)
                    ->where('status', Procedure::STATUS_PROCESSED)
                    ->whereDate('availment_date', '>=', $effectiveDate)
                    ->each(function ($procedure) use ($oldFee, $newFee, $record) {
                        $difference = $newFee - $oldFee;

                        if ($difference == 0) return;

                        Procedure::create([
                            'member_id'       => $procedure->member_id,
                            'clinic_id'       => $procedure->clinic_id,
                            'service_id'      => $procedure->service_id,
                            'availment_date'  => $procedure->availment_date,
                            'status'          => Procedure::STATUS_VALID,
                            'remarks'         => 'Service fee adjustment after approval',
                            'applied_fee'     => $difference,
                            'is_fee_adjusted' => true,
                            'adc_number_from' => $procedure->adc_number,
                        ]);

                        \App\Models\FeeAdjustmentRequest::create([
                            'procedure_id' => $procedure->id,
                            'current_fee'  => $oldFee,
                            'proposed_fee' => $newFee,
                            'reason'       => 'Service fee updated — approved by management.',
                            'status'       => 'approved',
                            'reviewed_by'  => auth()->id(),
                            'reviewed_at'  => now(),
                        ]);
                    });
            } else {
                $record->update(['approved_at' => now()]);
            }

            // Reset clinic fee_approval if no more pending
            $stillPending = ClinicService::where('clinic_id', $record->clinic_id)
                ->whereNotNull('new_fee')
                ->whereNull('approved_at')
                ->exists();

            if (! $stillPending) {
                $record->clinic->update(['fee_approval' => 'APPROVED']);
            }
        });

        $this->notifyClinic($record->clinic, 'Service Fee Approved',
            "The fee for {$record->service->name} has been approved: ₱" . number_format($record->new_fee, 2),
            'success'
        );

        Notification::make()->title('Service fee approved.')->success()->send();
    }

    protected function rejectServiceFee(ClinicService $record, string $reason): void
    {
        DB::transaction(function () use ($record) {
            $record->update(['new_fee' => null, 'effective_date' => null]);

            $stillPending = ClinicService::where('clinic_id', $record->clinic_id)
                ->whereNotNull('new_fee')
                ->whereNull('approved_at')
                ->exists();

            if (! $stillPending) {
                $record->clinic->update(['fee_approval' => 'UNAPPROVE']);
            }
        });

        $this->notifyClinic($record->clinic, 'Service Fee Rejected',
            "The fee update for {$record->service->name} was rejected. Reason: {$reason}",
            'danger'
        );

        Notification::make()->title('Service fee rejected.')->danger()->send();
    }

    protected function notifyClinic(Clinic $clinic, string $title, string $body, string $color): void
    {
        $url = \App\Filament\Resources\ClinicsResource::getUrl('edit', ['record' => $clinic]);

        if ($clinic->user) {
            $clinicUrl = $clinic->user->hasRole('Dentist') ? ClinicProfile::getUrl() : $url;
            Notification::make()->title($title)->body($body)->{$color}()
                ->actions([NotificationAction::make('view')->label('View')->url($clinicUrl)])
                ->sendToDatabase($clinic->user);
        }

        User::permission('clinic.update')->where('id', '!=', auth()->id())->get()
            ->each(fn($u) => Notification::make()->title($title)->body($body)->{$color}()
                ->actions([NotificationAction::make('view')->label('View')->url($url)])
                ->sendToDatabase($u)
            );
    }

    public static function getNavigationBadge(): ?string
    {
        if (! auth()->user()?->can('fee.approval')) return null;
        $count = ClinicService::whereNotNull('new_fee')->whereNull('approved_at')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->can('fee.approval');
    }

    public static function canAccess(): bool
    {
        return auth()->user()->can('fee.approval');
    }
}
