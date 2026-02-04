<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProcedureResource\Pages;
use App\Models\Procedure;
use App\Models\Role;
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
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'completed',
                        'danger'  => 'rejected',
                        'warning'    => 'returned',
                        'success' => 'valid',
                    ]),

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
                        'completed' => 'Completed',
                        'valid'     => 'Valid',
                        'rejected'  => 'Rejected',
                        'returned'  => 'Returned',
                    ]),
            ])
            ->actions([
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
                        $record->status = 'completed';
                        $record->save();

                        \Filament\Notifications\Notification::make()
                            ->title('Procedure resubmitted successfully.')
                            ->success()
                            ->send();

                        // Send email notification
                        Mail::raw("The procedure '{$record->title}' has been resubmitted.", function ($message) use ($record) {
                            $message->to('acnopre@upsitf.org')
                                ->subject('Procedure Resubmitted');
                        });
                    }),
            ])
            ->bulkActions([]);
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
