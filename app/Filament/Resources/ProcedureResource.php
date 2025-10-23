<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProcedureResource\Pages;
use App\Models\Procedure;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ProcedureResource extends Resource
{
    protected static ?string $model = Procedure::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Dental Management';
    protected static ?string $navigationLabel = 'My Procedures';

    /**
     * Build a safe eloquent query for Filament to use.
     * This method avoids null issues when the user or clinic is not present.
     */
    public static function getEloquentQuery(): Builder
    {
        $clinicId = null;

        // guard to avoid trying to access properties on null
        if (Auth::check() && isset(Auth::user()->clinic) && Auth::user()->clinic) {
            $clinicId = Auth::user()->clinic->id;
        }

        $query = Procedure::query();

        if ($clinicId) {
            return $query->where('clinic_id', $clinicId);
        }

        // no clinic — return an empty query so Filament doesn't attempt to resolve null classes
        return $query->whereRaw('1 = 0');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('member.full_name')
                    ->label('Member Name')
                    ->sortable()
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->sortable()
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('availment_date')
                    ->label('Availment Date')
                    ->date('M d, Y')
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    // use simple string mapping instead of arrow functions
                    ->colors([
                        'success' => 'approved',
                        'danger'  => 'declined',
                        'warning' => 'pending',
                    ])
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('approval_code')
                    ->label('Approval Code')
                    ->copyable()
                    ->copyMessage('Approval code copied!')
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending'  => 'Pending',
                        'approved' => 'Approved',
                        'declined' => 'Declined',
                    ]),
            ])
            ->actions([]) // hide default actions if not needed
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProcedures::route('/'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check()
            && auth()->user()->hasAnyRole([
                'Super Admin',
                'Dentist',
            ]);
    }
}
