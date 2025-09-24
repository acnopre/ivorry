<?php

namespace App\Filament\Resources\ClinicsResource\Pages;

use App\Filament\Resources\ClinicsResource;
use Filament\Resources\Pages\CreateRecord;

class CreateClinics extends CreateRecord
{
    protected static string $resource = ClinicsResource::class;

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
            ->filter(fn ($fee) => $fee !== null && $fee !== '')
            ->mapWithKeys(fn ($fee, $id) => [$id => ['fee' => $fee]])
            ->toArray();
        $this->record->basicDentalServices()->sync($basicSync);

        // Sync Plan Enhancements
        $enhSync = collect($this->planEnhancementsData)
            ->filter(fn ($fee) => $fee !== null && $fee !== '')
            ->mapWithKeys(fn ($fee, $id) => [$id => ['fee' => $fee]])
            ->toArray();
        $this->record->planEnhancements()->sync($enhSync);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
