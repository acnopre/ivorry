<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MemberResource\Pages;
use App\Imports\MembersImport;
use App\Models\Member;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\{DatePicker, FileUpload, Section, Select, Textarea, TextInput};
use Filament\Tables\Columns\{TextColumn, BadgeColumn};
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Tables\Actions\Action;

class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static ?string $navigationGroup = 'Accounts & Members';
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Member Information')
                    ->schema([
                        Select::make('account_id')
                            ->label('Account')
                            ->relationship(
                                name: 'account',
                                titleAttribute: 'company_name',
                                modifyQueryUsing: fn($query) => $query->where('status', 1)
                            )
                            ->required()
                            ->searchable(),

                        TextInput::make('card_number')
                            ->label('Card Number')
                            ->required()
                            ->unique(ignoreRecord: true),

                        TextInput::make('first_name')->required()->maxLength(255),
                        TextInput::make('last_name')->required()->maxLength(255),
                        TextInput::make('middle_name')->required()->maxLength(255),
                        TextInput::make('suffix')->required()->maxLength(255),


                        Select::make('member_type')
                            ->options([
                                'PRINCIPAL' => 'Principal',
                                'DEPENDENT' => 'Dependent',
                            ])
                            ->required(),

                        DatePicker::make('birthdate'),

                        Select::make('gender')
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female',
                            ])
                            ->native(false),
                    ])->columns(2),

                Section::make('Contact Details')
                    ->schema([
                        TextInput::make('email')->email(),
                        TextInput::make('phone')->tel(),
                        // Textarea::make('address')->rows(2),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('account.policy_code')
                    ->label('Policy Code')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('account.company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('full_name')
                    ->label('Full Name')
                    ->getStateUsing(fn($record) => "{$record->first_name} {$record->last_name}")
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(
                        query: fn($query, $direction) =>
                        $query->orderByRaw("CONCAT(first_name, ' ', last_name) {$direction}")
                    ),

                TextColumn::make('card_number')
                    ->label('Card Number')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('member_type')
                    ->label('Member Type')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('account.status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'success' => fn($state) => $state === 1,
                        'warning' => fn($state) => $state === 0,
                    ])
                    ->formatStateUsing(fn($state) => $state === 1 ? 'Active' : 'Inactive'),

                TextColumn::make('created_at')
                    ->date()
                    ->label('Created At'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
            ])
            ->headerActions([
                Action::make('importXls')
                    ->label('Import XLS')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->form([
                        FileUpload::make('file')
                            ->label('Upload Excel File')
                            ->acceptedFileTypes([
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $relativePath = $data['file'];
                        $disk = Storage::disk('public'); // use the public disk
                        $absolutePath = $disk->path($relativePath);

                        if (! $disk->exists($relativePath)) {
                            throw new \Exception("File not found at: {$absolutePath}");
                        }

                        Excel::import(new MembersImport, $absolutePath);
                    }),

            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }



    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check()
            && auth()->user()->hasAnyRole([
                'Super Admin',
                'Account Manager',
                'Upper Management',
                'CSR'
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMembers::route('/'),
            'create' => Pages\CreateMember::route('/create'),
            'edit' => Pages\EditMember::route('/{record}/edit'),
            'view' => Pages\ViewMember::route('/{record}'),
        ];
    }


    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\MemberResource\RelationManagers\ProceduresRelationManager::class,
        ];
    }
}
