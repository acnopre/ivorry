<?php

namespace App\Filament\Resources\ClinicsResource\Pages;

use App\Filament\Resources\ClinicsResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;

class CreateClinics extends CreateRecord
{
    protected static string $resource = ClinicsResource::class;
    protected array $servicesData = [];

    /**
     * Prepare data before creating the clinic record.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Store services (basic + enhancement) temporarily
        $this->servicesData = $data['services'] ?? [];

        // Remove pivot data before saving
        unset($data['services']);
        // Find the owner dentist (where is_owner = true)
        $ownerDentist = collect($data['dentists'] ?? [])->firstWhere('is_owner', true);
        //  Check if clinic email is provided and not duplicated
        if (!empty($data['clinic_email'])) {
            if (User::where('email', $data['clinic_email'])->exists()) {
                Notification::make()
                    ->title('This email is already registered.')
                    ->danger()
                    ->send();

                throw new Halt();
            }
        }

        //  If owner dentist exists, create a linked user first
        if ($ownerDentist) {
            $plainPassword = Str::random(12);

            $user = User::create([
                'name' => $ownerDentist['first_name'] . ' ' . $ownerDentist['last_name'],
                'email' => $data['clinic_email'] ?? null,
                'password' => Hash::make($plainPassword),
                'must_change_password' => true,
            ]);

            $user->assignRole('Dentist');

            // Send notification only if email exists
            if (!empty($data['clinic_email'])) {
                $token = Password::broker()->createToken($user);
                $user->notify(new \App\Notifications\SendGeneratedPassword($plainPassword));
            }

            //  Assign user_id before creating the clinic
            $data['user_id'] = $user->id;
        }

        return $data;
    }

    /**
     * Handle services pivot table after clinic creation.
     */
    protected function afterCreate(): void
    {
        // Merge basic + enhancement arrays safely
        $mergedServices = ($this->servicesData['basic'] ?? []) + ($this->servicesData['enhancement'] ?? []);

        if (empty($mergedServices)) {
            return;
        }

        $filtered = collect($mergedServices)
            ->filter(fn($fee, $serviceId) => $serviceId)
            ->mapWithKeys(fn($fee, $serviceId) => [
                $serviceId => ['fee' => $fee],
            ])
            ->toArray();

        if (!empty($filtered)) {
            $this->record->services()->sync($filtered);
        } else {
            Notification::make()
                ->title('No valid services found')
                ->body('The selected services were not found in the services table.')
                ->danger()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
