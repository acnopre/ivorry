<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HipResource\Pages;
use App\Models\Hip;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HipResource extends Resource
{
    protected static ?string $model = Hip::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'HIPs';
    public static ?string $navigationGroup = 'Lookup Tables';

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->can('lookup_tables.view');
    }

    public static function canCreate(): bool
    {
        return auth()->check() && auth()->user()->can('lookup_tables.create');
    }

    public static function canEdit($record): bool
    {
        return auth()->check() && auth()->user()->can('lookup_tables.edit');
    }

    public static function canDelete($record): bool
    {
        return auth()->check() && auth()->user()->can('lookup_tables.delete');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('HIP Name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (\App\Models\Hip $record, Tables\Actions\DeleteAction $action) {
                        $usedInAccounts = \App\Models\Account::where('hip_id', $record->id)->exists();
                        $usedInAmendments = \App\Models\AccountAmendment::where('hip_id', $record->id)->exists();

                        if ($usedInAccounts || $usedInAmendments) {
                            \Filament\Notifications\Notification::make()
                                ->title('Cannot Delete HIP')
                                ->body("'{$record->name}' is currently used by existing accounts or amendments and cannot be deleted.")
                                ->danger()
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->before(function (\Illuminate\Database\Eloquent\Collection $records, Tables\Actions\DeleteBulkAction $action) {
                        $usedIds = \App\Models\Account::whereIn('hip_id', $records->pluck('id'))->pluck('hip_id')
                            ->merge(\App\Models\AccountAmendment::whereIn('hip_id', $records->pluck('id'))->pluck('hip_id'))
                            ->unique();

                        if ($usedIds->isNotEmpty()) {
                            $usedNames = $records->whereIn('id', $usedIds)->pluck('name')->join(', ');

                            \Filament\Notifications\Notification::make()
                                ->title('Cannot Delete Some HIPs')
                                ->body("The following HIPs are in use and were not deleted: {$usedNames}.")
                                ->danger()
                                ->send();

                            $action->cancel();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHips::route('/'),
            'create' => Pages\CreateHip::route('/create'),
            'edit' => Pages\EditHip::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->can('lookup_tables.view');
    }
}
