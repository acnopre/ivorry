<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Models\Role;
use App\Models\Service;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Notifications\Notification;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;
    public static ?string $navigationGroup = 'Lookup Tables';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, callable $set) => $set('slug', str($state)->trim()->slug('_')->toString())),
            Forms\Components\TextInput::make('slug')
                ->required()
                ->maxLength(255)
                ->readOnly(),
            Forms\Components\Select::make('type')
                ->options([
                    'basic' => 'Basic',
                    'enhancement' => 'Enhancement',
                    'special' => 'Special',
                ])
                ->default('basic')
                ->required(),
            Forms\Components\Select::make('unit_type')
                ->options([
                    'Session' => 'Session',
                    'Quadrant' => 'Quadrant',
                    'Tooth' => 'Tooth',
                    'Arch' => 'Arch',
                    'Surface' => 'Surface',
                    'Canal' => 'Canal',
                ])
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('slug')->searchable(),
            Tables\Columns\BadgeColumn::make('type')
                ->colors([
                    'primary' => 'basic',
                    'success' => 'enhancement',
                    'warning' => 'special',
                ]),
            Tables\Columns\TextColumn::make('unit_type'),
            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->label('Created'),
        ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Tables\Actions\DeleteAction $action, Service $record) {
                        $accountCount = $record->accountServices()->count();
                        $clinicCount = $record->clinic()->count();

                        if ($accountCount > 0 || $clinicCount > 0) {
                            Notification::make()
                                ->danger()
                                ->title('Cannot Delete Service')
                                ->body("This service is in use: {$accountCount} account(s), {$clinicCount} clinic(s).")
                                ->persistent()
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->before(function (Tables\Actions\DeleteBulkAction $action, $records) {
                        foreach ($records as $record) {
                            $accountCount = $record->accountServices()->count();
                            $clinicCount = $record->clinic()->count();

                            if ($accountCount > 0 || $clinicCount > 0) {
                                Notification::make()
                                    ->danger()
                                    ->title('Cannot Delete Services')
                                    ->body('One or more services are in use and cannot be deleted.')
                                    ->persistent()
                                    ->send();

                                $action->cancel();
                                return;
                            }
                        }
                    }),
            ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check()
            && auth()->user()->hasAnyRole([Role::SUPER_ADMIN, Role::UPPER_MANAGEMENT]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
        ];
    }
}
