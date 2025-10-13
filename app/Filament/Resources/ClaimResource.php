<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClaimResource\Pages;
use App\Models\Procedure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;

class ClaimResource extends Resource
{
    protected static ?string $model = Procedure::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationGroup = 'Claims Management';
    protected static ?string $modelLabel = 'Claim';
    protected static ?string $pluralModelLabel = 'Claims';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() > 0 ? 'warning' : null;
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('status', 'pending')
            ->orderBy('availment_date');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Select::make('member_id')
                    ->relationship('member', 'name')
                    ->label('Member Name')
                    ->disabled(),
                Forms\Components\Select::make('service_id')
                    ->relationship('service', 'name')
                    ->label('Service Claimed')
                    ->disabled(),
            ])->columns(2),

            Forms\Components\Group::make()->schema([
                Forms\Components\DatePicker::make('availment_date')
                    ->label('Date of Availment')
                    ->disabled(),
                Forms\Components\TextInput::make('status')
                    ->disabled()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->label('Current Status'),
            ])->columns(3),

            Forms\Components\Section::make('Units Involved')
                ->description('List of all units linked to this claim')
                ->schema([
                    Forms\Components\Repeater::make('units')
                        ->schema([
                            Forms\Components\TextInput::make('unit.name')
                                ->label('Unit Name')
                                ->disabled(),
                            Forms\Components\TextInput::make('unitType.name')
                                ->label('Unit Type')
                                ->disabled(),
                            Forms\Components\TextInput::make('quantity')
                                ->label('Quantity')
                                ->disabled(),
                        ])
                        ->columns(3)
                        ->disableItemCreation()
                        ->disableItemDeletion()
                        ->disableItemMovement(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('member.name')
                    ->label('Member Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('member.card_number')
                    ->label('Card No.')
                    ->searchable(),
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service Claimed')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('availment_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'denied' => 'danger',
                        default => 'warning',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'denied' => 'Denied',
                    ])
                    ->label('Claim Status'),

                Tables\Filters\Filter::make('availment_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('From Date'),
                        Forms\Components\DatePicker::make('until')->label('To Date'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('availment_date', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('availment_date', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators['from'] = 'From: ' . \Carbon\Carbon::parse($data['from'])->format('M d, Y');
                        }
                        if ($data['until'] ?? null) {
                            $indicators['until'] = 'To: ' . \Carbon\Carbon::parse($data['until'])->format('M d, Y');
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Claim Details')
                    ->modalWidth('4xl')
                    ->mutateRecordDataUsing(function (Procedure $record, array $data): array {
                        $record->load([
                            'units.unitType',
                            'units.unit',
                            'member',
                            'clinic',
                            'service.clinic.dentists',
                        ]);
                        dd($record);

                        $ownerDentist = optional($record->service->clinic?->dentists->firstWhere('is_owner', true));

                        $units = $record->units->map(fn($unit) => [
                            'unit' => ['name' => $unit->unit?->name ?? '-'],
                            'unitType' => ['name' => $unit->unitType?->name ?? '-'],
                            'quantity' => $unit->quantity,
                        ])->toArray();

                        return [
                            ...$record->toArray(),
                            'dentist_name' => $ownerDentist?->full_name ?? '-',
                            'clinic_name' => $record->service->clinic?->clinic_name ?? '-',
                            'units' => $units,
                        ];
                    })
                    ->form([
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('member.name')
                                    ->label('Member Name')
                                    ->disabled(),
                                Forms\Components\TextInput::make('service.name')
                                    ->label('Service Claimed')
                                    ->disabled(),
                                Forms\Components\TextInput::make('dentist_name')
                                    ->label('Dentist Name')
                                    ->disabled(),
                                Forms\Components\TextInput::make('clinic_name')
                                    ->label('Clinic Name')
                                    ->disabled(),
                                Forms\Components\DatePicker::make('availment_date')
                                    ->label('Date of Availment')
                                    ->disabled(),
                                Forms\Components\TextInput::make('status')
                                    ->label('Current Status')
                                    ->disabled(),
                                Forms\Components\Textarea::make('remarks')
                                    ->label('Remarks')
                                    ->placeholder('Enter remarks...')
                                    ->visible(fn ($record) => in_array($record->status, ['denied', 'approved']))
                                    ->disabled(fn ($record) => $record->status !== 'denied')
                                    ->afterStateUpdated(function (callable $set, callable $get, $state, $record) {
                                        if ($record && $record->status === 'denied') {
                                            $record->update(['remarks' => $state]);
                                        }
                                    }),
                                Forms\Components\Repeater::make('units')
                                    ->schema([
                                        Forms\Components\TextInput::make('unit.name')->label('Unit Name')->disabled(),
                                        Forms\Components\TextInput::make('unitType.name')->label('Unit Type')->disabled(),
                                        Forms\Components\TextInput::make('quantity')->label('Quantity')->disabled(),
                                    ])
                                    ->columns(3)
                                    ->disableItemCreation()
                                    ->disableItemDeletion()
                                    ->disableItemMovement(),
                            ])
                            ->columns(2),
                    ])
                    ->modalFooterActions([
                        Tables\Actions\Action::make('approve')
                            ->label('Approve')
                            ->color('success')
                            ->icon('heroicon-o-check-circle')
                            ->visible(fn (Procedure $record): bool => $record->status === 'pending')
                            ->requiresConfirmation()
                            ->action(function (Procedure $record) {
                                $approvalCode = Str::upper(Str::random(8));
                                $record->update([
                                    'status' => 'approved',
                                    'approval_code' => $approvalCode,
                                ]);
                                Notification::make()
                                    ->title('Claim Approved')
                                    ->body("Approval Code: **{$approvalCode}**")
                                    ->success()
                                    ->send();
                            }),

                        Tables\Actions\Action::make('reject')
                            ->label('Reject')
                            ->color('danger')
                            ->icon('heroicon-o-x-circle')
                            ->visible(fn (Procedure $record): bool => $record->status === 'pending')
                            ->form([
                                Forms\Components\Textarea::make('remarks')
                                    ->label('Rejection Remarks')
                                    ->required()
                                    ->placeholder('Enter reason for denial...'),
                            ])
                            ->requiresConfirmation()
                            ->action(function (Procedure $record, array $data) {
                                $record->update([
                                    'status' => 'denied',
                                    'remarks' => $data['remarks'] ?? null,
                                ]);
                                Notification::make()
                                    ->title('Claim Rejected')
                                    ->body('The claim has been denied with remarks.')
                                    ->danger()
                                    ->send();
                            }),

                        Tables\Actions\Action::make('editRemarks')
                            ->label('Edit Remarks')
                            ->color('warning')
                            ->icon('heroicon-o-pencil-square')
                            ->visible(fn (Procedure $record): bool => $record->status === 'denied')
                            ->form([
                                Forms\Components\Textarea::make('remarks')
                                    ->label('Edit Remarks')
                                    ->placeholder('Update remarks here...')
                                    ->required(),
                            ])
                            ->action(function (Procedure $record, array $data) {
                                $record->update(['remarks' => $data['remarks']]);
                                Notification::make()
                                    ->title('Remarks Updated')
                                    ->body('Remarks have been successfully updated.')
                                    ->success()
                                    ->send();
                            }),

                        Tables\Actions\Action::make('close')
                            ->label('Close')
                            ->color('gray')
                            ->close(),
                    ]),
            ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check()
            && auth()->user()->hasAnyRole(['Super Admin', 'Claims Processor']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClaims::route('/'),
        ];
    }
}
