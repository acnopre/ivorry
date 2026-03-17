<?php

namespace App\Filament\Resources\MemberResource\Pages;

use App\Filament\Resources\MemberResource;
use App\Models\Member;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;

class EditMember extends EditRecord
{
    protected static string $resource = MemberResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['use_coc_number'] = ! empty($data['coc_number']);
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['use_coc_number']);
        return $data;
    }

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
