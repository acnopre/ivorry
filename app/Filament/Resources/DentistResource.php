<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DentistResource\Pages;
use App\Models\Dentist;
use App\Models\BasicDentalService;
use App\Models\PlanEnhancement;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;

class DentistResource extends Resource
{
    protected static ?string $model = Dentist::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationGroup = 'Dental Management';
    protected static ?string $navigationLabel = 'Dentists';
    protected static ?int $navigationSort = 0;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Dentist Info')->schema([
                    Forms\Components\Select::make('clinic_id')
                        ->relationship('clinic', 'clinic_name')
                        ->label('Clinic')
                        ->required()
                        ->searchable(),

                    Forms\Components\TextInput::make('last_name')->required(),
                    Forms\Components\TextInput::make('first_name')->required(),
                    Forms\Components\TextInput::make('middle_initial')->maxLength(3),
                    Forms\Components\TextInput::make('prc_license_number')->label('PRC License No.'),
                    Forms\Components\DatePicker::make('prc_expiration_date')->label('PRC Expiration Date'),
                    Forms\Components\Toggle::make('is_owner')->label('Is Owner')->default(false),
                    Forms\Components\Select::make('specializations')
                        ->label('Specializations')
                        ->multiple()
                        ->relationship('specializations', 'name')
                        ->preload()
                        ->searchable(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                $user = auth()->user();
                if (isset($user->clinic->id)) {
                    $query->where('clinic_id', $user->clinic->id);
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('clinic.clinic_name')->label('Clinic')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('last_name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('first_name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('prc_license_number')->label('PRC No.'),
                Tables\Columns\TextColumn::make('is_owner')
                    ->label('Is Owner')
                    ->formatStateUsing(fn($state) => $state == 1 ? 'Owner' : '')
                    ->badge()
                    ->colors([
                        'success' => fn($state) => $state == 1,
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->visible(auth()->user()->can('dentist.view')),
                Tables\Actions\EditAction::make()->visible(auth()->user()->can('dentist.update')),
                Tables\Actions\DeleteAction::make()->visible(auth()->user()->can('dentist.delete'))
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
    public static function canViewAny(): bool
    {
        return auth()->user()->can('dentist.view');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('dentist.create');
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->can('dentist.update');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->can('dentist.delete');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check()
            && auth()->user()->can('dentist.view');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDentists::route('/'),
            'create' => Pages\CreateDentist::route('/create'),
            'edit' => Pages\EditDentist::route('/{record}/edit'),
        ];
    }
}
