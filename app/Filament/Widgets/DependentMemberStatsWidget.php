<?php

namespace App\Filament\Widgets;

use App\Models\Member;
use App\Models\Service;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class DependentMemberStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $member = Auth::user()->member;
        $accountId = $member->account_id;
        $account = $member->account;

        $myProcedures = \App\Models\Procedure::where('member_id', $member->id);

        $stats = [
            Stat::make('My Procedures', $myProcedures->count())
                ->icon('heroicon-o-clipboard-document-list')
                ->color('primary')
                ->description('Total procedures')
                ->chart([2, 4, 3, 5, 4, 6, $myProcedures->count()]),

            Stat::make('Completed', $myProcedures->clone()->where('status', 'sign')->count())
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->description('Signed procedures'),

            Stat::make('Pending', $myProcedures->clone()->where('status', 'pending')->count())
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->description('Awaiting signature'),

            Stat::make('Account Status', ucfirst($account->status ?? 'N/A'))
                ->icon('heroicon-o-shield-check')
                ->color($account->status === 'active' ? 'success' : 'danger')
                ->description('Current status'),

            Stat::make('Account Expiry', $account->end_date ? \Carbon\Carbon::parse($account->end_date)->format('M d, Y') : 'N/A')
                ->icon('heroicon-o-calendar')
                ->color('info')
                ->description($account->end_date && \Carbon\Carbon::parse($account->end_date)->diffInDays(now()) < 30 ? 'Expiring soon' : 'Valid until'),
        ];

        // Add MBL balance for Fixed type
        if ($account && $account->mbl_type === 'Fixed') {
            $balancePercent = ($member->mbl_balance / $account->mbl_amount) * 100;
            $stats[] = Stat::make('MBL Balance', '₱' . number_format($member->mbl_balance, 2))
                ->icon('heroicon-o-banknotes')
                ->color($balancePercent < 20 ? 'danger' : ($balancePercent < 50 ? 'warning' : 'success'))
                ->description('of ₱' . number_format($account->mbl_amount, 2));
        }

        return $stats;
    }
}
