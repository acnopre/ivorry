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
                        'warning' => 'duplicate',
                        'info'    => 'updated',
                    ])
                    ->formatStateUsing(fn($state) => ucfirst($state)),

                // Account import columns
                Tables\Columns\TextColumn::make('raw_data.company_name')
                    ->label('Company Name')
                    ->placeholder('—')
                    ->visible(fn() => $this->ownerRecord->import_type === 'account'),

                Tables\Columns\TextColumn::make('raw_data.policy_code')
                    ->label('Policy Code')
                    ->placeholder('—')
                    ->visible(fn() => $this->ownerRecord->import_type === 'account'),


                Tables\Columns\TextColumn::make('raw_data.effective_date')
                    ->label('Effective Date')
                    ->placeholder('—')
                    ->formatStateUsing(fn($state) => $state && is_numeric($state)
                        ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $state)->format('M d, Y')
                        : ($state ?: '—'))
                    ->visible(fn() => $this->ownerRecord->import_type === 'account'),

                Tables\Columns\TextColumn::make('raw_data.expiration_date')
                    ->label('Expiration Date')
                    ->placeholder('—')
                    ->formatStateUsing(fn($state) => $state && is_numeric($state)
                        ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $state)->format('M d, Y')
                        : ($state ?: '—'))
                    ->visible(fn() => $this->ownerRecord->import_type === 'account'),

                // Member import columns
                Tables\Columns\TextColumn::make('raw_data.card_number')
                    ->label('Card #')
                    ->placeholder('—')
                    ->visible(fn() => $this->ownerRecord->import_type === 'member'),

                Tables\Columns\TextColumn::make('raw_data.first_name')
                    ->label('First Name')
                    ->placeholder('—')
                    ->visible(fn() => $this->ownerRecord->import_type === 'member'),

                Tables\Columns\TextColumn::make('raw_data.last_name')
                    ->label('Last Name')
                    ->placeholder('—')
                    ->visible(fn() => $this->ownerRecord->import_type === 'member'),

                Tables\Columns\TextColumn::make('raw_data.account_name')
                    ->label('Account')
                    ->placeholder('—')
                    ->visible(fn() => $this->ownerRecord->import_type === 'member'),

                Tables\Columns\TextColumn::make('message')
                    ->wrap()
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'success'   => 'Success',
                        'error'     => 'Error',
                        'duplicate' => 'Duplicate',
                        'updated'   => 'Updated',
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
