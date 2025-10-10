<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use DB;
class EditAccount extends EditRecord
{
    protected static string $resource = AccountResource::class;

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
    
        // Sync to pivot table (account_service)
        $account->services()->sync($filtered);
    }
}    
