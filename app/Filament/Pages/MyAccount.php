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

            // For SHARED plans, override service quantities with family-level member_service data
            if (strtoupper($this->account->plan_type) === 'SHARED' && $member->card_number) {
                \App\Models\MemberService::initializeForCard($member->card_number, $this->account->id);

                $memberServices = \App\Models\MemberService::where('card_number', $member->card_number)
                    ->where('account_id', $this->account->id)
                    ->get()
                    ->keyBy('service_id');

                $this->account->services->each(function ($service) use ($memberServices) {
                    $ms = $memberServices->get($service->id);
                    if ($ms) {
                        $service->pivot->quantity    = $ms->quantity;
                        $service->pivot->is_unlimited = $ms->is_unlimited;
                    }
                });
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
