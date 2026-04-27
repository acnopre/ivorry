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
    protected ?array $ownerDentistData = null;

    /**
     * Prepare data before creating the clinic record.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Store services temporarily
        $this->servicesData = $data['services'] ?? [];
        unset($data['services']);

        // Check if clinic email is already registered
        if (!empty($data['clinic_email'])) {
            if (User::where('email', $data['clinic_email'])->exists()) {
                Notification::make()
                    ->title('This email is already registered.')
                    ->danger()
                    ->send();
                throw new Halt();
            }

            if (\App\Models\Clinic::where('clinic_email', $data['clinic_email'])->exists()) {
                Notification::make()
                    ->title('This email is already assigned to another clinic.')
                    ->danger()
                    ->send();
                throw new Halt();
            }
        }

        return $data;
    }

    /**
     * Handle services and owner dentist user creation after clinic creation.
     */
    protected function afterCreate(): void
    {
        // Handle owner dentist user creation
        $ownerDentist = $this->record->dentists()->where('is_owner', true)->first();
        
        if ($ownerDentist && !$this->record->user_id) {
            $plainPassword = Str::random(12);

            $user = User::create([
                'name' => $ownerDentist->first_name . ' ' . $ownerDentist->last_name,
                'email' => $this->record->clinic_email ?? null,
                'password' => Hash::make($plainPassword),
                'must_change_password' => true,
            ]);

            $user->assignRole('Dentist');

            if (!empty($this->record->clinic_email)) {
                $user->notify(new \App\Notifications\SendGeneratedPassword($plainPassword));
            }

            $this->record->update(['user_id' => $user->id]);
        }

        // Handle services
        $mergedServices = ($this->servicesData['basic'] ?? []) + ($this->servicesData['enhancement'] ?? []) + ($this->servicesData['special'] ?? []);

        if (!empty($mergedServices)) {
            $filtered = collect($mergedServices)
                ->filter(fn($fee, $serviceId) => $serviceId)
                ->mapWithKeys(fn($fee, $serviceId) => [
                    $serviceId => ['fee' => $fee],
                ])
                ->toArray();

            if (!empty($filtered)) {
                $this->record->services()->sync($filtered);
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
