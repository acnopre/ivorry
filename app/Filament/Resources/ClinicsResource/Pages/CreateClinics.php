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
     * Prepare data before creating the account record.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Store services (basic + enhancement) temporarily
        $this->servicesData = $data['services'] ?? [];

        // Remove 'services' from main account data (pivot data)
        unset($data['services']);

        return $data;
    }
    /**
     * Handle services pivot table after account creation.
     */
    protected function afterCreate(): void
    {
        $data = $this->record;
        //TODO:: fix the toggle in the UI part.
        // get the first dentist that is owner
        $ownerDentist = $data->dentists()->where('is_owner', true)->first();

        if ($ownerDentist) {
            if (! empty($data['clinic_email']) && User::where('email', $data['clinic_email'])->exists()) {
                    Notification::make()
                        ->title('This email is already registered.')
                        ->danger()
                        ->send();
        
                    // Stop the create process without crashing
                    throw new Halt();
                }

            $dentistRole = 'Dentist';
            $plainPassword = Str::random(12);

            $user = User::create([
                    'name'     => $ownerDentist['first_name'] . ' ' . $ownerDentist['last_name'],
                    'email'    => $data['clinic_email'] ?? null, // accept null
                    'password' => Hash::make($plainPassword),
                    'must_change_password' => true,
            ]);

                // Generate password reset token only if email exists
            if (! empty($data['clinic_email'])) {
                    $token = Password::broker()->createToken($user);

                    // Send clinic_email with reset link + generated password
                    $user->notify(new \App\Notifications\SendGeneratedPassword($plainPassword));
            }

                // Link user to member
            $user->assignRole($dentistRole);
        }

        // Merge basic + enhancement arrays safely
        $mergedServices = $this->servicesData['basic'] + $this->servicesData['enhancement'] ;

        if (empty($mergedServices)) {
            return;
        }

        $filtered = collect($mergedServices)
            ->filter(fn($fee, $serviceId) => $serviceId)
            ->mapWithKeys(fn($fee, $serviceId) => [
                $serviceId => ['fee' => $fee],
            ])
            ->toArray();
        if (! empty($filtered)) {
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
