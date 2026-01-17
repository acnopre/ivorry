<?php

namespace App\Filament\Resources\ClinicsResource\Pages;

use App\Filament\Resources\ClinicsResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Filament\Actions;

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
        // Store all services (basic + enhancement)
        $this->servicesData = $data['services'] ?? [];

        // Remove from main record fields (pivot only)
        unset($data['services']);

        return $data;
    }

    /**
     * After saving the Account, sync related services with pivot table.
     */
    protected function afterSave(): void
    {
        $account = $this->record;

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
