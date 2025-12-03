<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use App\Models\AccountRenewal;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Log; // uncomment for debug

class EditAccount extends EditRecord
{
    protected static string $resource = AccountResource::class;

    protected array $servicesData = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn() => auth()->user()->hasRole('Super Admin', 'Upper Management')),
        ];
    }

    /**
     * Remove service fields from the main table payload so Filament doesn't attempt to write them to Account.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->servicesData = $data['services'] ?? [];
        unset($data['services']);
        return $data;
    }

    /**
     * Tell Filament not to run the normal save for the Account model.
     */
    protected function shouldSaveRecord(): bool
    {
        return false;
    }

    /**
     * Extra defensive override: if Filament calls this to update the record, do nothing.
     * This ensures the Account will not be changed by any internal update path.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Do not update the account
        return $record;
    }

    /**
     * After validation: create the renewal and renewal services.
     * IMPORTANT: we do NOT update the Account model here.
     */
    protected function afterValidate(): void
    {
        $record = $this->record;
        $data = $this->form->getState();
        // Only proceed if endorsement_type is RENEWAL on the Account record
        if ($data['endorsement_type']  !== 'RENEWAL') {
            return;
        }

        DB::transaction(function () use ($record, $data) {
            // Optional debug:
            // Log::info('Creating AccountRenewal for account_id: ' . $record->id, ['data' => $data]);

            // Create renewal header (do NOT touch $record)
            $renewal = AccountRenewal::create([
                'account_id'      => $record->id,
                'effective_date'  => $data['effective_date'] ?? null,
                'expiration_date' => $data['expiration_date'] ?? null,
                'requested_by'    => auth()->id(),
                'status'          => 'PENDING',
            ]);

            $record->endorsement_type = 'RENEWAL';
            $record->endorsement_status = 'PENDING';
            $record->save();


            // Save Renewal Services (basic + enhancement)
            $servicesByType = $this->servicesData ?: ($data['services'] ?? []);

            foreach (['basic', 'enhancement'] as $type) {
                if (empty($servicesByType[$type])) {
                    continue;
                }

                foreach ($servicesByType[$type] as $serviceId => $serviceData) {
                    $renewal->services()->create([
                        'service_id'   => $serviceId,
                        'quantity'     => $serviceData['quantity'] ?? null,
                        'is_unlimited' => $serviceData['is_unlimited'] ?? false,
                        'remarks'      => $serviceData['remarks'] ?? null,
                    ]);
                }
            }
        });

        Notification::make()
            ->success()
            ->title('Renewal Submitted')
            ->body('The renewal has been saved and is awaiting approval.')
            ->send();
    }
}
