<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use App\Models\Role;
use App\Models\User;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateAccount extends CreateRecord
{
    protected static string $resource = AccountResource::class;

    protected array $servicesData = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->servicesData = $data['services'] ?? [];

        if (isset($data['mbl_type']) && $data['mbl_type'] === 'Fixed' && isset($data['mbl_amount'])) {
            $data['mbl_balance'] = $data['mbl_amount'];
        }

        unset($data['services'], $data['members']);

        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->saveServices();
        $this->notifyApprovers();
    }

    protected function notifyApprovers(): void
    {
        $approvers = User::role([Role::UPPER_MANAGEMENT, Role::MIDDLE_MANAGEMENT])->get();

        Notification::make()
            ->title('New Account Pending Approval')
            ->body('Account ' . $this->record->company_name . ' (' . $this->record->policy_code . ') needs approval.')
            ->warning()
            ->actions([
                NotificationAction::make('view')
                    ->label('View Account')
                    ->url(AccountResource::getUrl('view', ['record' => $this->record])),
            ])
            ->sendToDatabase($approvers);
    }

    protected function saveServices(): void
    {
        $basic = $this->servicesData['basic'] ?? [];
        $enhancement = $this->servicesData['enhancement'] ?? [];
        $special = $this->servicesData['special'] ?? [];

        $mergedServices = $basic + $enhancement + $special;
        if (empty($mergedServices)) {
            return;
        }

        $filtered = collect($mergedServices)
            ->filter(function ($pivotData) {
                return (isset($pivotData['quantity']) && (int) $pivotData['quantity'] > 0)
                    || ($pivotData['is_unlimited'] ?? false)
                    || ! empty($pivotData['remarks']);
            })
            ->mapWithKeys(function ($pivotData, $serviceId) {
                return [
                    $serviceId => [
                        'quantity' => $pivotData['quantity'] ?? null,
                        'default_quantity' => $pivotData['quantity'] ?? null,
                        'is_unlimited' => filter_var($pivotData['is_unlimited'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        'remarks' => $pivotData['remarks'] ?? null,
                    ],
                ];
            })
            ->toArray();

        if (! empty($filtered)) {
            $this->record->services()->sync($filtered);
        } else {
            Notification::make()
                ->title('No services saved')
                ->body('All service entries were empty and filtered out.')
                ->warning()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
