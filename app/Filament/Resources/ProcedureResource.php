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
                    ->formatStateUsing(fn($state) => ucfirst($state))
                    ->colors([
                        'gray'    => 'pending',
                        'success' => 'valid',
                        'danger'  => 'rejected',
                        'warning' => 'returned',
                        'info' => 'completed',
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
