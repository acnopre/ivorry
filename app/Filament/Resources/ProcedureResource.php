<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProcedureResource\Pages;
use App\Models\Procedure;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;

class ProcedureResource extends Resource
{
    protected static ?string $model = Procedure::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Dental Management';
    protected static ?string $navigationLabel = 'My Procedures';

    /**
     * Safely get clinic-based query
     */
    public static function getEloquentQuery(): Builder
    {
        $clinicId = Auth::user()->clinic->id ?? null;
        $query = Procedure::query();
        return $clinicId
            ? $query->where('clinic_id', $clinicId)
            : $query->whereRaw('1 = 0');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('clinic.clinic_name')
                    ->label('Clinic')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('member.full_name')
                    ->label('Member Name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('procedure_units')
                    ->label('Units')
                    ->state(function ($record) {

                        $lines = [];

                        /*
                        |--------------------------------------------------------------------------
                        | NORMAL UNITS (no surface)
                        |--------------------------------------------------------------------------
                        */
                        foreach ($record->units as $unit) {
                            if (! isset($unit->pivot->surface_id)) {

                                $unitType = isset($unit->unitType->name)
                                    ? $unit->unitType->name
                                    : '—';

                                $unitName = isset($unit->name)
                                    ? $unit->name
                                    : '—';

                                $qty = isset($unit->pivot->quantity)
                                    ? $unit->pivot->quantity
                                    : 0;

                                $lines[] = "{$unitType}: {$unitName} | Qty: {$qty}";
                            }
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | SURFACE UNITS
                        |--------------------------------------------------------------------------
                        */
                        foreach ($record->surface_units as $surface) {

                            if (! isset($surface->pivot->unit_id)) {
                                continue;
                            }

                            $unit = \App\Models\Unit::find($surface->pivot->unit_id);

                            $unitType = isset($unit->unitType->name)
                                ? $unit->unitType->name
                                : '—';

                            $unitName = isset($unit->name)
                                ? $unit->name
                                : '—';

                            $surfaceName = isset($surface->name)
                                ? $surface->name
                                : '—';

                            $qty = isset($surface->pivot->quantity)
                                ? $surface->pivot->quantity
                                : 0;

                            $lines[] = "{$unitType}: {$unitName} | Surface: {$surfaceName} | Qty: {$qty}";
                        }

                        return implode("\n", $lines);
                    })
                    ->wrap(),


                Tables\Columns\TextColumn::make('availment_date')
                    ->label('Availment Date')
                    ->date('M d, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn($state) => ucfirst($state))
                    ->color(fn($state) => match($state) {
                        'pending'   => 'warning',
                        'signed'    => 'info',
                        'valid'     => 'success',
                        'invalid'   => 'danger',
                        'returned'  => 'warning',
                        'processed' => 'success',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    }),

                Tables\Columns\TextColumn::make('applied_fee')
                    ->label('Applied Fee')
                    ->money('PHP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('lastUpdatedBy.name')
                    ->label('Last Updated By')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('approval_code')
                    ->label('Approval Code')
                    ->copyable()
                    ->copyMessage('Approval code copied!')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending'   => 'Pending',
                        'sign'      => 'Signed',
                        'valid'     => 'Valid',
                        'rejected'  => 'Rejected',
                        'returned'  => 'Returned',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                // ❌ CANCEL PROCEDURE
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn($record) => $record->status === Procedure::STATUS_PENDING)
                    ->modalHeading('Cancel Procedure')
                    ->modalDescription('Please provide a reason for cancelling this procedure.')
                    ->modalWidth('md')
                    ->form([
                        Forms\Components\Textarea::make('cancel_reason')
                            ->label('Reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Procedure $record, array $data) {
                        $record->update([
                            'status'  => Procedure::STATUS_CANCELLED,
                            'remarks' => $data['cancel_reason'],
                        ]);

                        Notification::make()->title('Procedure Cancelled')->success()->send();
                    }),

                // ✏️ EDIT FEE
                Tables\Actions\Action::make('edit_fee')
                    ->label('Request Fee Edit')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('warning')
                    ->visible(fn($record) => $record->status === Procedure::STATUS_SIGN && !$record->hasPendingFeeAdjustment())
                    ->fillForm(fn(Procedure $record) => ['current_fee' => $record->applied_fee])
                    ->form([
                        Forms\Components\TextInput::make('current_fee')
                            ->label('Current Fee')
                            ->prefix('₱')
                            ->disabled(),
                        Forms\Components\TextInput::make('proposed_fee')
                            ->label('Proposed Fee')
                            ->numeric()
                            ->prefix('₱')
                            ->required()
                            ->minValue(0),
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason / Justification')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Procedure $record, array $data) {
                        \App\Models\FeeAdjustmentRequest::create([
                            'procedure_id' => $record->id,
                            'current_fee' => $record->applied_fee,
                            'proposed_fee' => $data['proposed_fee'],
                            'reason' => $data['reason'],
                            'requested_by' => auth()->id(),
                        ]);

                        Notification::make()
                            ->title('Fee Adjustment Requested')
                            ->body('Your request has been submitted for approval.')
                            ->success()
                            ->send();
                    }),

                // ✅ SIGN PROCEDURE
                Tables\Actions\Action::make('sign')
                    ->label('Sign Procedure')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->visible(fn($record) => $record->status === 'pending')
                    ->url(fn(Procedure $record): string => ProcedureResource::getUrl('sign', ['record' => $record]))
                    ->openUrlInNewTab(),

                // 🔄 RESUBMIT PROCEDURE
                Tables\Actions\Action::make('resubmit')
                    ->label('Resubmit')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Resubmission')
                    ->modalSubheading('Are you sure you want to resubmit this procedure?')
                    ->visible(fn($record) => $record->status === 'returned')
                    ->action(function (Procedure $record) {
                        $record->status = 'signed';
                        $record->save();

                        \Filament\Notifications\Notification::make()
                            ->title('Procedure resubmitted successfully.')
                            ->success()
                            ->send();

                        $dentistEmail = $record->clinic?->user?->email;
                        if ($dentistEmail) {
                            Mail::raw("The procedure '{$record->title}' has been resubmitted.", function ($message) use ($dentistEmail) {
                                $message->to($dentistEmail)
                                    ->subject('Procedure Resubmitted');
                            });
                        }
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProcedures::route('/'),
            'sign'  => Pages\SignProcedurePage::route('/sign/{record}'),
        ];
    }


    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check()
            && auth()->user()->can('dentist.my-procedure');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('dentist.my-procedure');
    }
}
