<?php

namespace App\Filament\Pages;

use App\Models\Account;
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

    public function mount(): void
    {
        $user = Auth::user();

        // Find the account linked to this user through the member record
        $member = $user->member ?? null;

        if ($member && $member->account) {
            $this->account = $member->account->load('services');
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
