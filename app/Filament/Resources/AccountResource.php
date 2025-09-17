<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountResource\Pages;
use App\Imports\AccountImport;
use App\Models\Account;
use App\Models\EndorsementType;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Actions\ViewAction;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static ?string $navigationLabel = 'Accounts';
    protected static ?string $navigationGroup = 'Accounts & Members';
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Account Information')
                    ->schema([
                        TextInput::make('company_name')
                            ->label('Company Name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('policy_code')
                            ->label('Policy Code')
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->maxLength(50),

                        TextInput::make('hip')
                            ->label('HIP')
                            ->maxLength(255),

                        TextInput::make('card_used')
                            ->label('Card Used')
                            ->maxLength(255),
                    ])->columns(2),

                Section::make('Contract Information')
                    ->schema([
                        DatePicker::make('effective_date')
                            ->label('Effective Date'),

                        DatePicker::make('expiration_date')
                            ->label('Expiration Date'),

                        // dynamic from endorsement_types table
                        Select::make('endorsement_type')
                            ->label('Endorsement Type')
                            ->options(
                                \App\Models\EndorsementType::pluck('name', 'name')
                            )
                            ->required(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->searchable(),

                // display endorsement type name instead of ID
                TextColumn::make('endorsement_type')
                    ->label('Endorsement')
                    ->badge()
                    ->colors([
                        'success' => fn($state) => $state === 'NEW',
                        'warning' => fn($state) => $state === 'RENEWAL',
                        'info'    => fn($state) => $state === 'AMENDMENT',
                    ]),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn($state) => $state === 1 ? 'Active' : 'Inactive')
                    ->badge()
                    ->colors([
                        'success' => fn($state) => $state === 1,
                        'warning'  => fn($state) => $state === 0,
                    ]),

                TextColumn::make('effective_date')->label('Effective')->date(),
                TextColumn::make('expiration_date')->label('Expiration')->date(),
                TextColumn::make('created_at')->dateTime()->label('Created'),
            ])
            ->filters([
                // filter dynamically from DB
                SelectFilter::make('endorsement_type')
                    ->label('Endorsement Type')
                    ->options(
                        \App\Models\EndorsementType::pluck('name', 'name')
                    )
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

                        Excel::import(new AccountImport, $absolutePath);
                    }),

            ])
            ->actions([
                ViewAction::make()
                    ->modalHeading('Account Details')
                    ->modalSubmitAction(false)
                    ->extraModalFooterActions([
                        TableAction::make('approve')
                            ->label('Approve')
                            ->color('success')
                            ->requiresConfirmation()
                            ->action(function (Model $record) {
                                $record->update(['status' => 1]);

                                Notification::make()
                                    ->success()
                                    ->title('Account approved')
                                    ->send();
                            })
                            ->cancelParentActions(), // ← closes the view modal
                    ]),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])

            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check()
            && auth()->user()->hasAnyRole(['Super Admin', 'Account Manager', 'Upper Management']);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccounts::route('/'),
            'create' => Pages\CreateAccount::route('/create'),
            'edit' => Pages\EditAccount::route('/{record}/edit'),
        ];
    }
}
