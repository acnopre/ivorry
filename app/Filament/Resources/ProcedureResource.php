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

                Tables\Columns\TextColumn::make('availment_date')
                    ->label('Availment Date')
                    ->date('M d, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'gray'    => 'pending',
                        'success' => 'valid',
                        'danger'  => 'rejected',
                        'warning' => 'returned',
                        'primary' => 'completed',
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


                // ✅ VALIDATE CLAIM
                Tables\Actions\Action::make('valid')
                    ->label('Mark as Valid')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn($record) => $record->status === 'completed')
                    ->action(function (Procedure $record) {
                        $record->update(['status' => 'valid']);
                        Notification::make()
                            ->title('Procedure marked as valid.')
                            ->success()
                            ->send();
                    }),

                // ✅ REJECT CLAIM
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('remarks')
                            ->label('Remarks')
                            ->rows(3)
                            ->required()
                            ->placeholder('Enter reason for rejection...'),
                    ])
                    ->visible(fn($record) => $record->status === 'completed')
                    ->modalHeading('Reject Procedure')
                    ->modalButton('Reject')
                    ->action(function (array $data, Procedure $record) {
                        $record->update([
                            'status'  => 'rejected',
                            'remarks' => $data['remarks'],
                        ]);
                        Notification::make()
                            ->title('Procedure rejected.')
                            ->danger()
                            ->send();
                    }),

                // ✅ RETURN CLAIM
                Tables\Actions\Action::make('return')
                    ->label('Return')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->form([
                        Forms\Components\Textarea::make('remarks')
                            ->label('Remarks')
                            ->rows(3)
                            ->required()
                            ->placeholder('Reason for returning procedure...'),
                    ])
                    ->visible(fn($record) => $record->status === 'completed')
                    ->modalHeading('Return Procedure')
                    ->modalButton('Return')
                    ->action(function (array $data, Procedure $record) {
                        $record->update([
                            'status'  => 'returned',
                            'remarks' => $data['remarks'],
                        ]);
                        Notification::make()
                            ->title('Procedure returned to dentist.')
                            ->warning()
                            ->send();
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
        return auth()->check() &&
            auth()->user()->hasAnyRole(['Super Admin', 'Dentist']);
    }
}
