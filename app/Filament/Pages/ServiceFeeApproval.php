<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\DB;
use App\Models\Clinic;
use App\Models\ClinicServiceFeeHistory;
use App\Models\Procedure;
use App\Models\User;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;

class ServiceFeeApproval extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.service-fee-approval';

    protected static ?string $navigationLabel = 'Service Fee Approval';
    protected static ?string $navigationGroup = 'Dental Management';
    protected static ?int $navigationSort = 2;

    /**
     * Required by Filament: table query
     */
    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Clinic::query()
            ->with('services')
            ->whereIn('fee_approval', ['PENDING', 'UNAPPROVE']);
    }

    /**
     * Table columns and actions
     */
    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('clinic_name')
                ->searchable()
                ->sortable(),

            Tables\Columns\BadgeColumn::make('fee_approval')
                ->colors([
                    'warning' => 'PENDING',
                    'success' => 'APPROVED',
                ]),

            Tables\Columns\TextColumn::make('services')
                ->label('Services (Old Fee | New Fee | Difference)')
                ->formatStateUsing(function ($record) {
                    if (! $record->services->count()) {
                        return '—';
                    }

                    $html = '<table class="w-full text-sm">';
                    $html .= '<thead><tr class="border-b"><th class="text-left px-2 py-1">Service</th><th class="text-right px-2 py-1">Old Fee</th><th class="text-right px-2 py-1">New Fee</th><th class="text-right px-2 py-1">Difference</th></tr></thead>';
                    $html .= '<tbody>';

                    foreach ($record->services as $service) {
                        $old = number_format($service->pivot->fee, 2);
                        $new = number_format($service->pivot->new_fee ?? $service->pivot->fee, 2);
                        $diff = number_format(($service->pivot->new_fee ?? $service->pivot->fee) - $service->pivot->fee, 2);

                        $html .= '<tr class="border-b">';
                        $html .= "<td class='px-2 py-1'>{$service->name}</td>";
                        $html .= "<td class='px-2 py-1 text-right'>₱{$old}</td>";
                        $html .= "<td class='px-2 py-1 text-right'>₱{$new}</td>";
                        $html .= "<td class='px-2 py-1 text-right'>₱{$diff}</td>";
                        $html .= '</tr>';
                    }

                    $html .= '</tbody></table>';

                    return $html;
                })
                ->html(),
            Tables\Columns\TextColumn::make('services')
                ->label('Services (Fee / New Fee / Difference)')
                ->formatStateUsing(function ($record) {
                    $pendingServices = $record->services->filter(fn($s) => filled($s->pivot->new_fee));

                    if ($pendingServices->isEmpty()) {
                        return '—';
                    }

                    $html = '<table class="w-full text-sm">';
                    $html .= '<thead><tr class="border-b">'
                        . '<th class="text-left px-2 py-1">Service</th>'
                        . '<th class="text-right px-2 py-1">Current Fee</th>'
                        . '<th class="text-right px-2 py-1">New Fee</th>'
                        . '<th class="text-right px-2 py-1">Difference</th>'
                        . '<th class="text-left px-2 py-1">Effective Date</th>'
                        . '</tr></thead><tbody>';

                    foreach ($pendingServices as $service) {
                        $fee  = number_format($service->pivot->fee, 2);
                        $new  = number_format($service->pivot->new_fee, 2);
                        $diff = number_format($service->pivot->new_fee - $service->pivot->fee, 2);
                        $date = $service->pivot->effective_date
                            ? \Carbon\Carbon::parse($service->pivot->effective_date)->format('M d, Y')
                            : '—';

                        $html .= '<tr class="border-b">';
                        $html .= "<td class='px-2 py-1'>{$service->name}</td>";
                        $html .= "<td class='px-2 py-1 text-right'>₱{$fee}</td>";
                        $html .= "<td class='px-2 py-1 text-right'>₱{$new}</td>";
                        $html .= "<td class='px-2 py-1 text-right'>₱{$diff}</td>";
                        $html .= "<td class='px-2 py-1'>{$date}</td>";
                        $html .= '</tr>';
                    }

                    $html .= '</tbody></table>';

                    return $html;
                })
                ->html(),


        ];
    }


    protected function getTableActions(): array
    {
        return [
            Action::make('approve_fees')
                ->label('Approve Service Fees')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(auth()->user()->can('fee.approval'))
                ->requiresConfirmation()
                ->modalHeading('Approve Clinic Service Fees')
                ->form(function (Clinic $record) {
                    $services = $record->services
                        ->filter(fn($s) => filled($s->pivot->new_fee))
                        ->map(function ($s) {
                            $fee  = number_format($s->pivot->fee, 2);
                            $new  = number_format($s->pivot->new_fee, 2);
                            $diff = number_format($s->pivot->new_fee - $s->pivot->fee, 2);
                            $date = $s->pivot->effective_date
                                ? ' | Effective: ' . \Carbon\Carbon::parse($s->pivot->effective_date)->format('M d, Y')
                                : '';
                            return "{$s->name}: ₱{$fee} → ₱{$new} | Diff ₱{$diff}{$date}";
                        })
                        ->implode("\n");

                    $affectedCount = $record->services
                        ->filter(fn($s) => filled($s->pivot->new_fee))
                        ->sum(function ($s) use ($record) {
                            $effectiveDate = $s->pivot->effective_date ?? now()->toDateString();
                            return Procedure::where('clinic_id', $record->id)
                                ->where('service_id', $s->id)
                                ->where('status', Procedure::STATUS_PROCESSED)
                                ->whereDate('availment_date', '>=', $effectiveDate)
                                ->count();
                        });

                    $procedureNotice = $affectedCount > 0
                        ? "\u26a0 {$affectedCount} processed procedure(s) with availment date >= effective date will receive fee adjustment."
                        : 'No processed procedures fall within the effective date range. No adjustments will be created.';

                    return [
                        Textarea::make('modal_summary')
                            ->label('Pending Service Fees')
                            ->default("{$services}\n\n{$procedureNotice}")
                            ->disabled()
                            ->rows(5)
                            ->columnSpanFull(),
                    ];
                })
                ->action(function (Clinic $record, array $data, Action $action) {
                    $this->approveClinicFees($record);

                    $clinicEditUrl = \App\Filament\Resources\ClinicsResource::getUrl('edit', ['record' => $record]);
                    $clinicProfileUrl = ClinicProfile::getUrl();

                    if ($record->user && $record->user->id) {
                        $clinicUser = $record->user;
                        $url = $clinicUser->hasRole('Dentist') ? $clinicProfileUrl : $clinicEditUrl;

                        Notification::make()
                            ->title('Service Fees Approved')
                            ->body('The service fee update for ' . $record->clinic_name . ' has been approved.')
                            ->success()
                            ->actions([NotificationAction::make('view')->label('View Clinic')->url($url)])
                            ->sendToDatabase($clinicUser);
                    }

                    $accreditationUsers = User::permission('clinic.update')
                        ->where('id', '!=', auth()->id())
                        ->get();
                    foreach ($accreditationUsers as $user) {
                        Notification::make()
                            ->title('Service Fees Approved')
                            ->body('The service fee update for ' . $record->clinic_name . ' has been approved.')
                            ->success()
                            ->actions([NotificationAction::make('view')->label('View Clinic')->url($clinicEditUrl)])
                            ->sendToDatabase($user);
                    }

                    Notification::make()->success()->title('Service fees approved successfully!')->send();
                }),

            Action::make('reject_fees')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(
                    auth()->user()->hasAnyRole([
                        \App\Models\Role::UPPER_MANAGEMENT,
                        \App\Models\Role::MIDDLE_MANAGEMENT,
                    ])
                )
                ->form([
                    Textarea::make('rejection_reason')
                        ->label('Reason for Rejection')
                        ->required()
                        ->rows(3),
                ])
                ->requiresConfirmation()
                ->modalHeading('Reject Service Fee Update')
                ->modalDescription('This will discard the proposed fees and revert the clinic back to its current fees.')
                ->action(function (Clinic $record, array $data) {
                    DB::transaction(function () use ($record) {
                        foreach ($record->services as $service) {
                            if (filled($service->pivot->new_fee)) {
                                $record->services()->updateExistingPivot($service->id, ['new_fee' => null]);
                            }
                        }
                        $record->update(['fee_approval' => 'UNAPPROVE']);
                    });

                    $clinicEditUrl = \App\Filament\Resources\ClinicsResource::getUrl('edit', ['record' => $record]);

                    if ($record->user && $record->user->id) {
                        Notification::make()
                            ->title('Service Fee Update Rejected')
                            ->body("The proposed fee update for {$record->clinic_name} was rejected. Reason: {$data['rejection_reason']}")
                            ->danger()
                            ->actions([NotificationAction::make('view')->label('View Clinic')->url($clinicEditUrl)])
                            ->sendToDatabase($record->user);
                    }

                    Notification::make()->danger()->title('Service fee update rejected.')->send();
                }),
        ];
    }


    /**
     * This is required by Filament: provide query()
     */
    protected function query()
    {
        return $this->getTableQuery();
    }

    /**
     * Fee approval logic
     */
    protected function approveClinicFees(Clinic $clinic): void
    {
        DB::transaction(function () use ($clinic) {

            foreach ($clinic->services as $service) {

                if (! filled($service->pivot->new_fee)) {
                    continue;
                }

                $oldFee        = $service->pivot->fee;
                $newFee        = $service->pivot->new_fee;
                $effectiveDate = $service->pivot->effective_date ?? now()->toDateString();

                // Insert fee history record
                ClinicServiceFeeHistory::create([
                    'clinic_id'      => $clinic->id,
                    'service_id'     => $service->id,
                    'old_fee'        => $oldFee,
                    'new_fee'        => $newFee,
                    'effective_date' => $effectiveDate,
                    'approved_by'    => auth()->id(),
                    'created_by'     => auth()->id(),
                ]);

                // Mark as approved — scheduler will apply when effective_date arrives
                $clinic->services()->updateExistingPivot($service->id, [
                    'approved_at' => now(),
                ]);
            }

            $clinic->update(['fee_approval' => 'APPROVED']);
        });
    }

    public static function getNavigationBadge(): ?string
    {
        if (! auth()->user()?->can('fee.approval')) {
            return null;
        }

        $pendingCount = Clinic::where('fee_approval', 'PENDING')->count();

        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check()
            && auth()->user()->can('fee.approval');
    }
}
