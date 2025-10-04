<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use App\Models\Member;

class MemberLogin extends Page 
{
    use Forms\Concerns\InteractsWithForms;

    protected static string $view = 'filament.pages.member-login';
    protected static string $layout = 'filament-panels::components.layout.simple';


    public ?string $card_number = null;
    public ?string $firstname = null;
    public ?string $lastname = null;

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('card_number')
                ->label('Card Number')
                ->required(),

            Forms\Components\TextInput::make('firstname')
                ->label('First Name')
                ->required(),

            Forms\Components\TextInput::make('lastname')
                ->label('Last Name')
                ->required(),
        ]);
    }


    public function submit()
    {
        $data = $this->form->getState();

        $member = Member::where('card_number', $data['card_number'])
            ->first();

        if (! $member) {
            Notification::make()
                ->title('Login failed')
                ->body('The provided credentials do not match our records.')
                ->danger()
                ->send();
            return;
        }
        Auth::login($member->user);

        return redirect()->intended('app/set-email');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function isAccessibleWithoutAuthentication(): bool
    {
        return true;
    }
    
    public function hasLogo(): bool
    {
        return false;
    }
      /**
     * Override default layout (removes sidebar + topbar).
     */
    // protected static string $layout = 'filament-panels::components.layout.base';
}
