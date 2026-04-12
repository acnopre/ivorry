<?php

namespace App\Filament\Pages;

use App\Models\Clinic;
use App\Notifications\WelcomeClinicEmail;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class WelcomeEmails extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationGroup = 'Communications';
    protected static ?string $navigationLabel = 'Welcome Emails';
    protected static ?string $title = 'Welcome Emails';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.welcome-emails';

    public static function canAccess(): bool
    {
        return auth()->user()->can('communication.welcome-email');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->can('communication.welcome-email');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Clinic::query()->with('user'))
            ->columns([
                TextColumn::make('clinic_name')
                    ->label('Clinic Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->placeholder('No email'),

                TextColumn::make('accreditation_status')
                    ->label('Accreditation')
                    ->badge()
                    ->colors([
                        'success' => 'ACTIVE',
                        'danger'  => 'INACTIVE',
                        'warning' => 'SILENT',
                        'info'    => 'SPECIFIC ACCOUNT',
                    ]),

                TextColumn::make('welcome_email_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn($state) => match ($state) {
                        'unsent' => 'Unsent',
                        'sent' => 'Sent',
                        'password_set' => 'Password Set',
                        default => $state,
                    })
                    ->colors([
                        'gray' => 'unsent',
                        'warning' => 'sent',
                        'success' => 'password_set',
                    ]),
            ])
            ->filters([
                SelectFilter::make('welcome_email_status')
                    ->label('Email Status')
                    ->options([
                        'unsent' => 'Unsent',
                        'sent' => 'Sent',
                        'password_set' => 'Password Set',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('send')
                    ->label('Send')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn(Clinic $record) => $record->welcome_email_status !== 'password_set' && $record->user?->email)
                    ->action(function (Clinic $record) {
                        $this->sendWelcomeEmail($record);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('sendBulk')
                    ->label('Send Welcome Email')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                        $sent = 0;
                        $skipped = 0;

                        foreach ($records as $clinic) {
                            if (! $clinic->user?->email || $clinic->welcome_email_status === 'password_set') {
                                $skipped++;
                                continue;
                            }

                            $this->sendWelcomeEmail($clinic);
                            $sent++;
                        }

                        Notification::make()
                            ->title("Welcome emails sent: {$sent}" . ($skipped ? ", skipped: {$skipped}" : ''))
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('welcome_email_status', 'asc');
    }

    protected function sendWelcomeEmail(Clinic $clinic): void
    {
        $user = $clinic->user;
        $tempPassword = Str::random(12);

        $user->update([
            'password' => Hash::make($tempPassword),
            'must_change_password' => true,
        ]);

        $user->notify(new WelcomeClinicEmail($tempPassword));
        $clinic->update(['welcome_email_status' => 'sent']);

        Notification::make()
            ->title("Welcome email sent to {$clinic->clinic_name}")
            ->success()
            ->send();
    }
}
