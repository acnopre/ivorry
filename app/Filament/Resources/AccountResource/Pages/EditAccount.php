<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use App\Models\AccountAmendment;
use App\Models\AccountRenewal;
use App\Services\AccountEndorsementService;
use App\Services\AccountService;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
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

        // Validate RENEWAL dates
        if ($data['endorsement_type'] === 'RENEWAL') {
            $errors = [];
            
            if (isset($data['effective_date']) && $data['effective_date'] == $record->effective_date->format('Y-m-d')) {
                $errors[] = 'For renewal, the effective date must be different from the current effective date.';
            }
            if (isset($data['expiration_date']) && $data['expiration_date'] == $record->expiration_date->format('Y-m-d')) {
                $errors[] = 'For renewal, the expiration date must be different from the current expiration date.';
            }

            if (!empty($errors)) {
                Notification::make()
                    ->danger()
                    ->title('Validation Error')
                    ->body(implode(' ', $errors))
                    ->persistent()
                    ->send();
                $this->halt();
                return;
            }
        }

        // -------------------------------------------------------------------
        // 1. HANDLE RENEWAL WORKFLOW
        // -------------------------------------------------------------------
        if ($data['endorsement_type'] === 'RENEWAL') {

            DB::transaction(function () use ($record, $data) {
                AccountEndorsementService::deletePendingRenewals($record->id);

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

                $servicesByType = $this->servicesData ?: ($data['services'] ?? []);
                AccountEndorsementService::attachServicesToRenewalFromForm($renewal, $servicesByType);
            });

            $accountUrl = AccountResource::getUrl('view', ['record' => $record]);
            $approvers = User::permission('account.renew')->get();
            foreach ($approvers as $approver) {
                Notification::make()
                    ->title('Account Renewal Request')
                    ->body('A renewal request for ' . $record->company_name . ' has been submitted by ' . auth()->user()->name . '.')
                    ->warning()
                    ->actions([NotificationAction::make('view')->label('Review Account')->url($accountUrl)])
                    ->sendToDatabase($approver);
            }

            Notification::make()
                ->success()
                ->title('Renewal Submitted')
                ->body('The renewal has been submitted for approval. All services will be reset to their default quantities upon approval.')
                ->send();

            $this->halt();
            return;
        }


        // -------------------------------------------------------------------
        // 2. HANDLE AMENDMENT WORKFLOW (NEW)
        // -------------------------------------------------------------------
        if ($data['endorsement_type'] === 'AMENDMENT') {

            // Same company + same HIP as another account = reject
            $newCompany = $data['company_name'] ?? $record->company_name;
            $newHipId   = $data['hip_id'] ?? $record->hip_id;

            if (AccountService::isDuplicateCompanyHip($newCompany, $newHipId, $record->id)) {
                Notification::make()
                    ->danger()
                    ->title('Duplicate Account')
                    ->body(AccountService::duplicateMessage($newCompany, $newHipId))
                    ->persistent()
                    ->send();
                $this->halt();
                return;
            }

            DB::transaction(function () use ($record, $data) {
                AccountEndorsementService::deletePendingAmendments($record->id);

                $amendment = AccountAmendment::create([
                    'account_id'           => $record->id,
                    'company_name'         => $data['company_name'],
                    'policy_code'          => $data['policy_code'],
                    'hip_id'               => $data['hip_id'] ?? null,
                    'card_used'            => $data['card_used'] ?? null,
                    'effective_date'       => $data['effective_date'] ?? null,
                    'expiration_date'      => $data['expiration_date'] ?? null,
                    'endorsement_type'     => 'AMENDMENT',
                    'endorsement_status'   => 'PENDING',
                    'coverage_period_type' => $data['coverage_period_type'] ?? null,
                    'mbl_type'             => $data['mbl_type'] ?? null,
                    'mbl_amount'           => $data['mbl_amount'] ?? null,
                    'remarks'              => $data['remarks'] ?? null,
                    'requested_by'         => auth()->id(),
                    'old_company_name'         => $record->getOriginal('company_name'),
                    'old_policy_code'          => $record->getOriginal('policy_code'),
                    'old_hip_id'               => $record->getOriginal('hip_id'),
                    'old_card_used'            => $record->getOriginal('card_used'),
                    'old_effective_date'       => $record->getOriginal('effective_date'),
                    'old_expiration_date'      => $record->getOriginal('expiration_date'),
                    'old_coverage_period_type' => $record->getOriginal('coverage_period_type'),
                    'old_mbl_type'             => $record->getOriginal('mbl_type'),
                    'old_mbl_amount'           => $record->getOriginal('mbl_amount'),
                    'old_plan_type'            => $record->getOriginal('plan_type'),
                    'old_coverage_type'        => $record->getOriginal('coverage_type'),
                    'coverage_type'            => $data['coverage_type'] ?? $record->getOriginal('coverage_type'),
                ]);

                $record->update([
                    'endorsement_type'     => 'AMENDMENT',
                    'endorsement_status'   => 'PENDING',
                    'hip_id'               => $record->getOriginal('hip_id'),
                    'company_name'         => $record->getOriginal('company_name'),
                    'policy_code'          => $record->getOriginal('policy_code'),
                    'card_used'            => $record->getOriginal('card_used'),
                    'effective_date'       => $record->getOriginal('effective_date'),
                    'expiration_date'      => $record->getOriginal('expiration_date'),
                    'coverage_period_type' => $record->getOriginal('coverage_period_type'),
                    'mbl_type'             => $record->getOriginal('mbl_type'),
                    'mbl_amount'           => $record->getOriginal('mbl_amount'),
                    'plan_type'            => $record->getOriginal('plan_type'),
                    'coverage_type'        => $record->getOriginal('coverage_type'),
                ]);

                $currentServices = \App\Models\AccountService::where('account_id', $record->id)->get();
                $servicesByType = $this->servicesData ?: ($data['services'] ?? []);
                AccountEndorsementService::attachServicesToAmendmentFromForm($amendment, $servicesByType, $currentServices);
            });

            $accountUrl = AccountResource::getUrl('view', ['record' => $record]);
            $approvers = User::permission('account.amend')->get();
            foreach ($approvers as $approver) {
                Notification::make()
                    ->title('Account Amendment Request')
                    ->body('An amendment request for ' . $record->company_name . ' has been submitted by ' . auth()->user()->name . '.')
                    ->warning()
                    ->actions([NotificationAction::make('view')->label('Review Account')->url($accountUrl)])
                    ->sendToDatabase($approver);
            }

            Notification::make()
                ->success()
                ->title('Amendment Submitted')
                ->body('The amendment has been submitted for approval. Changes will take effect once approved by management.')
                ->send();

            $this->halt();
            return;
        }
    }
}
