<?php

namespace App\Filament\Pages;

use App\Models\Account;
use App\Models\MemberService;
use App\Models\Role;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class MyAccount extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationGroup = 'Profile';
    protected static ?string $title = 'My Account';
    protected static string $view = 'filament.pages.my-account';

    public ?Account $account = null;
    public $memberServices = null;

    public function mount(): void
    {
        $user = Auth::user();
        $member = $user->member ?? null;

        if (!$member) {
            \Filament\Notifications\Notification::make()
                ->title('No member profile found')
                ->body('Your account is not linked to a member profile. Please contact support.')
                ->warning()
                ->persistent()
                ->send();
            return;
        }

        if ($member->account) {
            $this->account = $member->account->load(['services', 'hip']);

            // Use MemberService for all plan types (per card_number)
            if ($member->card_number) {
                \App\Models\MemberService::initializeForCard($member->card_number, $this->account->id);
                $this->memberServices = \App\Models\MemberService::where('card_number', $member->card_number)
                    ->where('account_id', $this->account->id)
                    ->with('service')
                    ->get();
            }
        }
    }

    public function hasAccount(): bool
    {
        return !is_null($this->account);
    }
    public static function canAccess(): bool
    {
        return auth()->user()->can('member.myaccount') || auth()->check() && auth()->user()->member;
    }
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->can('member.myaccount') || auth()->check() && auth()->user()->member;
    }
}
