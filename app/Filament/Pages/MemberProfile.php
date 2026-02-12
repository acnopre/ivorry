<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;

class MemberProfile extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static string $view = 'filament.pages.member-profile';
    protected static ?string $navigationLabel = 'My Profile';
    protected static ?string $title = 'My Profile';
    protected static ?string $navigationGroup = 'Profile';

    public ?array $data = [];

    public function mount(): void
    {
        $member = auth()->user()->member;

        if ($member) {
            $this->form->fill($member->toArray());
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Personal Information')
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->label('First Name')
                            ->disabled(),
                        Forms\Components\TextInput::make('last_name')
                            ->label('Last Name')
                            ->disabled(),
                        Forms\Components\TextInput::make('middle_name')
                            ->label('Middle Name')
                            ->disabled(),
                        Forms\Components\TextInput::make('suffix')
                            ->label('Suffix')
                            ->disabled(),
                        Forms\Components\TextInput::make('card_number')
                            ->label('Card Number')
                            ->disabled(),
                        Forms\Components\TextInput::make('member_type')
                            ->label('Member Type')
                            ->disabled(),
                        Forms\Components\DatePicker::make('birthdate')
                            ->label('Birthdate')
                            ->disabled(),
                        Forms\Components\TextInput::make('gender')
                            ->label('Gender')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required(),
                        Forms\Components\TextInput::make('phone')
                            ->label('Phone')
                            ->tel()
                            ->required(),
                        Forms\Components\Textarea::make('address')
                            ->label('Address')
                            ->rows(3),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $member = auth()->user()->member;

        if ($member) {
            $member->update([
                'email' => $this->data['email'],
                'phone' => $this->data['phone'],
                'address' => $this->data['address'],
            ]);

            Notification::make()
                ->title('Profile updated successfully')
                ->success()
                ->send();
        }
    }

    public static function canAccess(): bool
    {
        return auth()->user()->can('member.myprofile') || auth()->check() && auth()->user()->member;
    }
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->can('member.myprofile') || auth()->check() && auth()->user()->member;
    }
}
