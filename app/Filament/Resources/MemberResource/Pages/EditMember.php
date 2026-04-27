<?php

namespace App\Filament\Resources\MemberResource\Pages;

use App\Filament\Resources\MemberResource;
use App\Filament\Resources\MemberResource\Pages\CreateMember;
use App\Models\Member;
use App\Models\MemberService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;

class EditMember extends EditRecord
{
    protected static string $resource = MemberResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['use_coc_number'] = ! empty($data['coc_number']);

        // For SHARED accounts, populate the principal + dependents virtual fields
        $member  = $this->record;
        $account = $member->account;

        if ($account && strtoupper($account->plan_type) === 'SHARED') {
            $cardNumber = $member->card_number;

            $principal = \App\Models\Member::where('account_id', $account->id)
                ->where('card_number', $cardNumber)
                ->where('member_type', 'PRINCIPAL')
                ->first();

            $dependents = \App\Models\Member::where('account_id', $account->id)
                ->where('card_number', $cardNumber)
                ->where('member_type', 'DEPENDENT')
                ->get();

            $data['shared_card_number']    = $cardNumber;

            if ($principal) {
                $data['principal_first_name']  = $principal->first_name;
                $data['principal_last_name']   = $principal->last_name;
                $data['principal_middle_name'] = $principal->middle_name;
                $data['principal_suffix']      = $principal->suffix;
                $data['principal_birthdate']   = $principal->birthdate;
                $data['principal_gender']      = $principal->gender;
                $data['principal_email']       = $principal->email;
                $data['principal_phone']       = $principal->phone;
            }

            $data['dependents'] = $dependents->map(fn($dep) => [
                'id'          => $dep->id,
                'first_name'  => $dep->first_name,
                'last_name'   => $dep->last_name,
                'middle_name' => $dep->middle_name,
                'suffix'      => $dep->suffix,
                'birthdate'   => $dep->birthdate,
                'gender'      => $dep->gender,
                'email'       => $dep->email,
                'phone'       => $dep->phone,
            ])->values()->toArray();
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['use_coc_number']);

        $member  = $this->record;
        $account = $member->account;

        if ($account && strtoupper($account->plan_type) === 'SHARED') {
            $cardNumber = $data['shared_card_number'] ?? $member->card_number;

            // Update principal
            \App\Models\Member::where('account_id', $account->id)
                ->where('card_number', $member->card_number)
                ->where('member_type', 'PRINCIPAL')
                ->update([
                    'card_number'  => $cardNumber,
                    'first_name'   => $data['principal_first_name'] ?? null,
                    'last_name'    => $data['principal_last_name'] ?? null,
                    'middle_name'  => $data['principal_middle_name'] ?? null,
                    'suffix'       => $data['principal_suffix'] ?? null,
                    'birthdate'    => $data['principal_birthdate'] ?? null,
                    'gender'       => $data['principal_gender'] ?? null,
                    'email'        => $data['principal_email'] ?? null,
                    'phone'        => $data['principal_phone'] ?? null,
                ]);

            // Update dependents — match by id if present, create new ones otherwise
            foreach ($data['dependents'] ?? [] as $dep) {
                if (! empty($dep['id'])) {
                    \App\Models\Member::where('id', $dep['id'])->update([
                        'card_number'  => $cardNumber,
                        'first_name'   => $dep['first_name'] ?? null,
                        'last_name'    => $dep['last_name'] ?? null,
                        'middle_name'  => $dep['middle_name'] ?? null,
                        'suffix'       => $dep['suffix'] ?? null,
                        'birthdate'    => $dep['birthdate'] ?? null,
                        'gender'       => $dep['gender'] ?? null,
                        'email'        => $dep['email'] ?? null,
                        'phone'        => $dep['phone'] ?? null,
                    ]);
                } else {
                    // New dependent added via repeater
                    $depUser = CreateMember::createUserForMember(
                        $dep['first_name'] ?? 'Unknown',
                        $dep['last_name'] ?? 'Unknown',
                        $dep['email'] ?? null
                    );
                    Member::create([
                        'account_id'  => $account->id,
                        'card_number' => $cardNumber,
                        'first_name'  => $dep['first_name'] ?? null,
                        'last_name'   => $dep['last_name'] ?? null,
                        'middle_name' => $dep['middle_name'] ?? null,
                        'suffix'      => $dep['suffix'] ?? null,
                        'member_type' => 'DEPENDENT',
                        'birthdate'   => $dep['birthdate'] ?? null,
                        'gender'      => $dep['gender'] ?? null,
                        'email'       => $dep['email'] ?? null,
                        'phone'       => $dep['phone'] ?? null,
                        'status'      => 'ACTIVE',
                        'user_id'     => $depUser->id,
                        'mbl_balance' => $account->mbl_type === 'Fixed' ? $account->mbl_amount : null,
                    ]);
                    MemberService::initializeForCard($cardNumber, $account->id);
                }
            }

            // Prevent Filament from trying to save virtual fields onto the record
            foreach ([
                'shared_card_number', 'principal_first_name', 'principal_last_name',
                'principal_middle_name', 'principal_suffix', 'principal_birthdate',
                'principal_gender', 'principal_email', 'principal_phone', 'dependents',
            ] as $key) {
                unset($data[$key]);
            }
        }

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
