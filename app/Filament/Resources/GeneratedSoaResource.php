<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GeneratedSoaResource\Pages;
use App\Models\GeneratedSoa;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Infolists\Components\Card;
use Filament\Infolists\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Storage;

class GeneratedSoaResource extends Resource
{
    protected static ?string $model = GeneratedSoa::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'SOAs';

    // ✅ Form schema (readonly)
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('clinic_id')
                    ->label('Clinic')
                    ->disabled(),
                Forms\Components\DatePicker::make('from')->disabled(),
                Forms\Components\DatePicker::make('to')->disabled(),
                Forms\Components\TextInput::make('total_amount')->disabled(),
                Forms\Components\TextInput::make('status')->disabled(),
            ]);
    }

    // ✅ Table schema
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('SOA ID')->sortable(),
                Tables\Columns\TextColumn::make('clinic.clinic_name')->label('Clinic')->sortable(),
                Tables\Columns\TextColumn::make('from_date')->label('From')->date(),
                Tables\Columns\TextColumn::make('to_date')->label('To')->date(),
                Tables\Columns\TextColumn::make('total_amount')->label('Total')->money('php'),
                Tables\Columns\TextColumn::make('status')->label('Status'),
                Tables\Columns\TextColumn::make('created_at')->label('Generated At')->dateTime(),
            ])
            ->actions([
                // ✅ Show procedures in modal table
                // Action::make('view_procedures')
                //     ->label('Procedures')
                //     ->icon('heroicon-o-eye')
                //     ->modalHeading(fn(GeneratedSoa $record) => 'Procedures in SOA #' . $record->id)
                //     ->modalContent(function (GeneratedSoa $record) {
                //         $procedures = $record->procedures()->with('member', 'service')->get();

                //         return Grid::make()->schema([
                //             Card::make()->schema([
                //                 Tables\Columns\TextColumn::make('service.name')->label('Service'),
                //                 Tables\Columns\TextColumn::make('member.full_name')->label('Member'),
                //                 Tables\Columns\TextColumn::make('approval_code')->label('Approval Code'),
                //                 Tables\Columns\TextColumn::make('quantity')->label('Quantity'),
                //             ])->columns(1),
                //         ]);
                //     }),
                // ✅ Regenerate / download PDF
                Action::make('download_pdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->button()
                    ->action(function (GeneratedSoa $record) {
                        // Use the public disk
                        $disk = Storage::disk('public');

                        if (!$disk->exists($record->duplicate_file_path)) {
                            \Filament\Notifications\Notification::make()
                                ->title('PDF file not found.')
                                ->body('No PDF file was found.')
                                ->warning()
                                ->send();
                            return;
                        }

                        $fileName = basename($record->duplicate_file_path);

                        return response()->download(
                            $disk->path($record->duplicate_file_path), // full path to storage/app/public/{duplicate_file_path}
                            $fileName
                        );
                    }),

            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check()
            && auth()->user()->hasAnyRole([Role::SUPER_ADMIN, Role::UPPER_MANAGEMENT, Role::CLAIMS_PROCESSOR]);
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGeneratedSoas::route('/'),
            'create' => Pages\CreateGeneratedSoa::route('/create'),
            'edit' => Pages\EditGeneratedSoa::route('/{record}/edit'),
        ];
    }
}
