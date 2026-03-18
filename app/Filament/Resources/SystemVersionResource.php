<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SystemVersionResource\Pages;
use App\Models\SystemVersion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SystemVersionResource extends Resource
{
    protected static ?string $model = SystemVersion::class;

    protected static ?string $navigationIcon = 'heroicon-o-code-bracket';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'System Versions';

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_system::version');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('version')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('notes')
                    ->rows(3),
                Forms\Components\DateTimePicker::make('released_at')
                    ->required()
                    ->default(now()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('version')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('notes')
                    ->limit(50),
                Tables\Columns\TextColumn::make('released_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('released_at', 'desc')
            ->filters([])
            ->actions([
                Tables\Actions\Action::make('view_details')
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn($record) => 'Version ' . $record->version)
                    ->modalContent(fn($record) => view('filament.system-version.details', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSystemVersions::route('/'),
            'create' => Pages\CreateSystemVersion::route('/create'),
            'edit' => Pages\EditSystemVersion::route('/{record}/edit'),
        ];
    }
}
