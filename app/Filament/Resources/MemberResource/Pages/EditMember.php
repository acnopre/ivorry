<?php

namespace App\Filament\Resources\MemberResource\Pages;

use App\Filament\Resources\MemberResource;
use App\Models\Member;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMember extends EditRecord
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $member = $this->record;
        $account = $member->account;

        // If SHARED plan and PRINCIPAL set to INACTIVE, set all dependents to INACTIVE
        if (strtoupper($account->plan_type) === 'SHARED' && 
            strtoupper($member->member_type) === 'PRINCIPAL' && 
            strtoupper($member->status) === 'INACTIVE') {
            Member::where('account_id', $account->id)
                ->where('member_type', 'DEPENDENT')
                ->update([
                    'status' => 'inactive',
                    'inactive_date' => $member->inactive_date ?? now()->format('Y-m-d')
                ]);
        }
    }
}
