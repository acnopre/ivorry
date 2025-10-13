<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification; // Use Filament Notification
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
        // Check if both 'basic' and 'enhancement' arrays exist, merge them.
        // If they don't exist, use an empty array for a safe merge.
        $basic = $this->servicesData['basic'] ?? [];
        $enhancement = $this->servicesData['enhancement'] ?? [];

        // Merge basic + enhancement arrays
        $mergedServices = $basic + $enhancement;

        if (empty($mergedServices)) {
            // No services to sync, exit gracefully
            return;
        }

        // The structure from the form is: [service_id => ['quantity' => X, 'is_unlimited' => Y, 'remarks' => Z]]
        $filtered = collect($mergedServices)
            // Filter out any entries where the quantity is null/empty and it's not marked as unlimited.
            // We want to keep services where either quantity is set OR it's marked unlimited OR remarks are present.
            ->filter(function ($pivotData, $serviceId) {
                // If quantity is set AND > 0, or if is_unlimited is true, or if remarks is set, keep it.
                return (isset($pivotData['quantity']) && (int)$pivotData['quantity'] > 0)
                    || (isset($pivotData['is_unlimited']) && $pivotData['is_unlimited'] == true)
                    || !empty($pivotData['remarks']);
            })
            ->mapWithKeys(function ($pivotData, $serviceId) {
                // Map the pivot data to the format required by sync: [service_id => [pivot_column => value]]

                // Ensure boolean conversion for `is_unlimited`
                $isUnlimited = filter_var($pivotData['is_unlimited'] ?? false, FILTER_VALIDATE_BOOLEAN);

                return [
                    $serviceId => [
                        // If it's unlimited, set quantity to a default (e.g., 9999) or 0, or just use the input quantity.
                        // We'll use the provided quantity, as the flag is what matters.
                        'quantity' => (int) ($pivotData['quantity'] ?? 0),
                        'is_unlimited' => $isUnlimited, // Saved as boolean
                        'remarks' => $pivotData['remarks'] ?? null, // Saved as string/null
                    ],
                ];
            })
            ->toArray();

        if (! empty($filtered)) {
            // Sync the services with the new pivot data (quantity, is_unlimited, remarks)
            $this->record->services()->sync($filtered);
        } else {
            // Optional: Notify if no valid services were kept after filtering
            Notification::make()
                ->title('No services saved')
                ->body('All service entries were empty and filtered out.')
                ->warning()
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