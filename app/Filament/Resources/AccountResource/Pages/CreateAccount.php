<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use Filament\Resources\Pages\CreateRecord;
use DB;
class CreateAccount extends CreateRecord
{
    protected static string $resource = AccountResource::class;

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
        // Merge basic + enhancement arrays safely
        $mergedServices = $this->servicesData['basic'] + $this->servicesData['enhancement'] ;

        if (empty($mergedServices)) {
            return;
        }

        $filtered = collect($mergedServices)
            ->filter(fn($quantity, $serviceId) => $serviceId)
            ->mapWithKeys(fn($quantity, $serviceId) => [
                $serviceId => ['quantity' => $quantity],
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

    /**
     * Redirect after creation.
     */
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}