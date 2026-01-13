<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;

class CreateAccount extends CreateRecord
{
    protected static string $resource = AccountResource::class;

    protected array $servicesData = [];
    protected array $membersData = [];

    /**
     * Prepare data before creating the account record.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Store services (basic + enhancement) temporarily
        $this->servicesData = $data['services'] ?? [];

        // Store members temporarily
        $this->membersData = $data['members'] ?? [];

        // Remove non-account columns
        unset($data['services'], $data['members']);

        return $data;
    }

    /**
     * Handle services + members after account creation.
     */
    protected function afterCreate(): void
    {
        $this->saveServices();
        $this->saveMembers();
    }

    /**
     * Save services pivot data.
     */
    protected function saveServices(): void
    {
        $basic = $this->servicesData['basic'] ?? [];
        $enhancement = $this->servicesData['enhancement'] ?? [];

        $mergedServices = $basic + $enhancement;

        if (empty($mergedServices)) {
            return;
        }

        $filtered = collect($mergedServices)
            ->filter(function ($pivotData) {
                return (isset($pivotData['quantity']) && (int) $pivotData['quantity'] > 0)
                    || ($pivotData['is_unlimited'] ?? false)
                    || ! empty($pivotData['remarks']);
            })
            ->mapWithKeys(function ($pivotData, $serviceId) {
                return [
                    $serviceId => [
                        'quantity' => $pivotData['quantity'] ?? null,
                        'default_quantity' => $pivotData['quantity'] ?? null,
                        'is_unlimited' => filter_var($pivotData['is_unlimited'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        'remarks' => $pivotData['remarks'] ?? null,
                    ],
                ];
            })
            ->toArray();

        if (! empty($filtered)) {
            $this->record->services()->sync($filtered);
        } else {
            Notification::make()
                ->title('No services saved')
                ->body('All service entries were empty and filtered out.')
                ->warning()
                ->send();
        }
    }

    /**
     * Save members (SHARED plans only).
     */
    protected function saveMembers(): void
    {
        if ($this->record->plan_type !== 'SHARED') {
            return;
        }

        if (empty($this->membersData)) {
            return;
        }

        foreach ($this->membersData as $memberData) {
            // Skip empty rows
            if (empty($memberData['first_name']) || empty($memberData['last_name'])) {
                continue;
            }

            $userId = null;
            $plainPassword = Str::random(12);
            // if (!empty($memberData['email'])) {
            $user = \App\Models\User::create([
                'name' => $memberData['first_name'] . ' ' . ($memberData['middle_name'] ?? '') . ' ' . $memberData['last_name'],
                'email' => $memberData['email'],
                'password' => $plainPassword, // temporary random password
            ]);
            $userId = $user->id;
            // }

            if (!empty($memberData['email'])) {
                $token = Password::broker()->createToken($user);
                $user->notify(new \App\Notifications\SendGeneratedPassword($plainPassword));
            }

            // ✅ Create member with the new user_id
            $this->record->members()->create([
                'first_name'   => $memberData['first_name'],
                'middle_name'  => $memberData['middle_name'] ?? null,
                'last_name'    => $memberData['last_name'],
                'suffix'       => $memberData['suffix'] ?? null,
                'member_type' => !empty($memberData['is_principal']) ? 'PRINCIPAL' : 'DEPENDENT',
                'birthdate'    => $memberData['birthdate'] ?? null,
                'card_number'  => $this->record->card_used,
                'gender'       => $memberData['gender'] ?? null,
                'email'        => $memberData['email'] ?? null,
                'phone'        => $memberData['phone'] ?? null,
                'address'      => $memberData['address'] ?? null,
                'user_id'      => $userId, // link created user
            ]);
        }
    }


    /**
     * Redirect after creation.
     */
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
