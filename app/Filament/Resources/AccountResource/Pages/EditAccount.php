<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use App\Models\AccountAmendment;
use App\Models\AccountRenewal;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return $record; // Prevent changes to Account
    }

    /**
     * Handles RENEWAL + AMENDMENT logic.
     */
    protected function afterValidate(): void
    {
        $record = $this->record;
        $data   = $this->form->getState();

        // -------------------------------------------------------------------
        // 1. HANDLE RENEWAL WORKFLOW
        // -------------------------------------------------------------------
        if ($data['endorsement_type'] === 'RENEWAL') {

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
                ->body('The renewal has been submitted and is awaiting approval.')
                ->send();

            return;
        }


        // -------------------------------------------------------------------
        // 2. HANDLE AMENDMENT WORKFLOW (NEW)
        // -------------------------------------------------------------------
        if ($data['endorsement_type'] === 'AMENDMENT') {

            DB::transaction(function () use ($record, $data) {

                // Create Amendment Header (Snapshot)
                $amendment = AccountAmendment::create([
                    'account_id'        => $record->id,
                    'company_name'      => $data['company_name'],
                    'policy_code'       => $data['policy_code'],
                    'hip'               => $data['hip'] ?? null,
                    'card_used'         => $data['card_used'] ?? null,
                    'effective_date'    => $data['effective_date'] ?? null,
                    'expiration_date'   => $data['expiration_date'] ?? null,
                    'endorsement_type'  => 'AMENDMENT',
                    'endorsement_status' => 'PENDING',
                    'coverage_period_type' => $data['coverage_period_type'] ?? null,
                    'mbl_type'          => $data['mbl_type'] ?? null,
                    'mbl_amount'        => $data['mbl_amount'] ?? null,
                    'remarks'           => $data['remarks'] ?? null,
                    'requested_by'      => auth()->id(),
                ]);

                // Update main account status only
                $record->update([
                    'endorsement_type'   => 'AMENDMENT',
                    'endorsement_status' => 'PENDING',
                ]);

                // Save Amendment Services
                $servicesByType = $this->servicesData ?: ($data['services'] ?? []);
                foreach (['basic', 'enhancement'] as $type) {
                    if (empty($servicesByType[$type])) continue;

                    foreach ($servicesByType[$type] as $serviceId => $serviceData) {
                        $amendment->services()->create([
                            'service_id'       => $serviceId,
                            'quantity'         => $serviceData['quantity'] ?? null,
                            'is_unlimited'     => $serviceData['is_unlimited'] ?? false,
                            'remarks'          => $serviceData['remarks'] ?? null,
                            'default_quantity' => $serviceData['default_quantity'] ?? $serviceData['quantity'] ?? null,
                        ]);
                    }
                }
            });

            Notification::make()
                ->success()
                ->title('Amendment Submitted')
                ->body('The amendment has been submitted and is awaiting approval.')
                ->send();

            return;
        }
    }
}
