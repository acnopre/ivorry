<?php

namespace App\Filament\Resources\ImportLogResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;

class ImportLogItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('row_number')
                    ->label('Row #')
                    ->sortable()
                    ->width('80px'),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'success',
                        'danger'  => 'error',
                    ])
                    ->formatStateUsing(fn($state) => ucfirst($state)),

                Tables\Columns\TextColumn::make('message')
                    ->wrap()
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'success' => 'Success',
                        'error'   => 'Error',
                    ]),
            ])
            ->actions([
                Action::make('view_details')
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn($record) => 'Row ' . $record->row_number . ' — ' . ucfirst($record->status))
                    ->modalContent(fn($record) => view('filament.import-log.row-details', [
                        'data'    => is_array($record->raw_data) ? $record->raw_data : json_decode($record->raw_data, true) ?? [],
                        'status'  => $record->status,
                        'message' => $record->message,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->defaultSort('row_number');
    }
}
