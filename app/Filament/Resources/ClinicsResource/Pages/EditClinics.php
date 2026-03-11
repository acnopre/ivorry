<?php

namespace App\Filament\Resources\ClinicsResource\Pages;

use App\Filament\Resources\ClinicsResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Filament\Actions;
use App\Models\User;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EditClinics extends EditRecord
{
    protected static string $resource = ClinicsResource::class;

    protected array $servicesData = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Remove service fields from the main table data.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Check if email already exists before saving
        if (!empty($data['clinic_email'])) {
            $existingUser = User::where('email', $data['clinic_email'])
                ->where('id', '!=', $this->record->user_id)
                ->first();

            if ($existingUser) {
                Notification::make()
                    ->danger()
                    ->title('Email Already Exists')
                    ->body('A user with the email ' . $data['clinic_email'] . ' already exists. Please use a different email address.')
                    ->persistent()
                    ->send();

                $this->halt();
            }
        }

        // Store all services (basic + enhancement)
        $this->servicesData = $data['services'] ?? [];

        // Remove from main record fields (pivot only)
        unset($data['services']);

        if ($data['accreditation_status'] === 'SPECIFIC HIP') {
            $data['account_id'] = null;
        } elseif ($data['accreditation_status'] === 'SPECIFIC ACCOUNT') {
            $data['hip_id'] = null;
        }

        return $data;
    }

    /**
     * After saving the Account, sync related services with pivot table.
     */
    protected function afterSave(): void
    {
        $account = $this->record;

        // Handle owner dentist user creation if not already created
        $ownerDentist = $account->dentists()->where('is_owner', true)->first();
        
        if ($ownerDentist && !$account->user_id && !empty($account->clinic_email)) {
            $plainPassword = Str::random(12);
            
            $user = User::create([
                'name' => $ownerDentist->first_name . ' ' . $ownerDentist->last_name,
                'email' => $account->clinic_email,
                'password' => Hash::make($plainPassword),
                'must_change_password' => true,
            ]);

            $user->assignRole('Dentist');
            $user->notify(new \App\Notifications\SendGeneratedPassword($plainPassword));

            $account->update(['user_id' => $user->id]);
        }

        // Get only the enhancement new fees from the form state
        $enhancementFees = $this->data['services']['enhancement_new_fee'] ?? [];

        if (empty($enhancementFees)) {
            return;
        }

        // Prepare pivot data for sync
        $syncData = collect($enhancementFees)
            ->filter(fn($fee, $serviceId) => !is_null($serviceId) && $serviceId !== '')
            ->mapWithKeys(fn($fee, $serviceId) => [
                $serviceId => ['new_fee' => $fee],
            ])
            ->toArray();

        // Sync only the new fees to the pivot table
        // This will update existing ones and keep other pivot data intact
        foreach ($syncData as $serviceId => $pivotData) {
            $account->services()->updateExistingPivot($serviceId, $pivotData);
        }

        $hasChanges = collect($this->data['services']['enhancement_new_fee'] ?? [])
            ->filter(fn($fee) => $fee !== null && $fee !== '')
            ->isNotEmpty();

        if ($hasChanges) {
            $account->update(['fee_approval' => 'pending']);
        }
    }
}
