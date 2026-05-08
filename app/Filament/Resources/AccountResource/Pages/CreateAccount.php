<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use App\Models\Role;
use App\Models\User;
use App\Services\AccountService;
use Carbon\Carbon;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;

class CreateAccount extends CreateRecord
{
    protected static string $resource = AccountResource::class;

    protected array $servicesData = [];
    public bool $confirmedExpired = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->servicesData = $data['services'] ?? [];

        // Same company + same HIP = reject
        if (!empty($data['company_name']) && !empty($data['hip_id'])) {
            if (AccountService::isDuplicateCompanyHip($data['company_name'], $data['hip_id'])) {
                Notification::make()
                    ->danger()
                    ->title('Duplicate Account')
                    ->body(AccountService::duplicateMessage($data['company_name'], $data['hip_id']))
                    ->persistent()
                    ->send();
                throw new Halt();
            }
        }

        // Check if expiration_date is in the past
        if (! $this->confirmedExpired && ! empty($data['expiration_date'])) {
            if (Carbon::parse($data['expiration_date'])->lt(today())) {
                $this->confirmedExpired = false;

                Notification::make()
                    ->title('Account is Already Expired')
                    ->body('The expiration date you entered is in the past. This account will be marked as expired. Do you still want to proceed?')
                    ->warning()
                    ->persistent()
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('confirm')
                            ->label('Yes, Add Anyway')
                            ->button()
                            ->color('warning')
                            ->dispatch('confirmExpiredAccount'),
                        \Filament\Notifications\Actions\Action::make('cancel')
                            ->label('Cancel')
                            ->color('gray')
                            ->close(),
                    ])
                    ->send();

                throw new Halt();
            }
        }

        $this->confirmedExpired = false;

        if (isset($data['mbl_type']) && $data['mbl_type'] === 'Fixed' && isset($data['mbl_amount'])) {
            $data['mbl_balance'] = $data['mbl_amount'];
        }

        unset($data['services'], $data['members']);

        $data['created_by'] = auth()->id();

        return $data;
    }

    #[\Livewire\Attributes\On('confirmExpiredAccount')]
    public function confirmExpiredAccount(): void
    {
        $this->confirmedExpired = true;
        $this->create();
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
        if (empty($this->servicesData)) return;
        AccountService::syncServicesFromForm($this->record, $this->servicesData);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
