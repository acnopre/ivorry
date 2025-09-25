<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAccount extends CreateRecord
{
    protected static string $resource = AccountResource::class;

    protected array $basicDentalServicesData = [];
    protected array $planEnhancementsData = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Grab pivot data then remove from main data
        $this->basicDentalServicesData = $data['basic_dental_services'] ?? [];
        $this->planEnhancementsData = $data['plan_enhancements'] ?? [];

        unset($data['basic_dental_services'], $data['plan_enhancements']);

        return $data;
    }

    protected function afterCreate(): void
    {
        // Sync Basic Dental Services
        $basicSync = collect($this->basicDentalServicesData)
            ->filter(fn ($quantity) => $quantity !== null && $quantity !== '')
            ->mapWithKeys(fn ($quantity, $id) => [$id => ['quantity' => $quantity]])
            ->toArray();
        $this->record->basicDentalServices()->sync($basicSync);

        // Sync Plan Enhancements
        $enhSync = collect($this->planEnhancementsData)
            ->filter(fn ($quantity) => $quantity !== null && $quantity !== '')
            ->mapWithKeys(fn ($quantity, $id) => [$id => ['quantity' => $quantity]])
            ->toArray();
        $this->record->planEnhancements()->sync($enhSync);
    }

    /**
     * Redirect after creation
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
