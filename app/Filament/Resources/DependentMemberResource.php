<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DependentMemberResource\Pages;
use App\Filament\Resources\DependentMemberResource\RelationManagers;
use App\Models\DependentMember;
use App\Models\Member;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class DependentMemberResource extends Resource
{
    protected static ?string $model = Member::class;
    protected static ?string $navigationLabel = 'Dependent Members';
    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('account_id')->default(Auth::user()->member->account_id),
                Forms\Components\TextInput::make('first_name')->required(),
                Forms\Components\TextInput::make('last_name')->required(),
                Forms\Components\TextInput::make('middle_name'),
                Forms\Components\TextInput::make('suffix'),
                Forms\Components\TextInput::make('card_number')->required(),
                Forms\Components\DatePicker::make('birthdate'),
                Forms\Components\Select::make('gender')
                    ->options(['Male' => 'Male', 'Female' => 'Female', 'Other' => 'Other']),
                Forms\Components\TextInput::make('email')->email(),
                Forms\Components\TextInput::make('phone'),
                Forms\Components\Textarea::make('address'),

                // Automatically set type
                Forms\Components\Hidden::make('member_type')->default('DEPENDENT'),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('first_name'),
                Tables\Columns\TextColumn::make('last_name'),
                Tables\Columns\TextColumn::make('card_number'),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('member_type'),

            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDependentMembers::route('/'),
            'create' => Pages\CreateDependentMember::route('/create'),
            'edit' => Pages\EditDependentMember::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('member_type', 'DEPENDENT')
            ->where('account_id', Auth::user()->member->account_id);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false; //Hide 
    }
}
