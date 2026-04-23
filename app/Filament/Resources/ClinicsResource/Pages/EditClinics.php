<?php

namespace App\Filament\Resources\ClinicsResource\Pages;

use App\Filament\Resources\ClinicsResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Filament\Actions;
use App\Models\User;
use Illuminate\Support\Str;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Notifications\SendGeneratedPassword;

class EditClinics extends EditRecord
{
    protected static string $resource = ClinicsResource::class;

    protected array $servicesData = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('resendCredentials')
                ->label('Resend Credentials')
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Resend Login Credentials')
                ->modalDescription(fn() => 'This will generate a new password and send it to: ' . $this->record->clinic_email)
                ->visible(fn() => !empty($this->record->clinic_email) && !empty($this->record->user_id) && auth()->user()->can('clinic.update'))
                ->action(function () {
                    $user = $this->record->user;

                    if (!$user) {
                        Notification::make()->danger()->title('No user account linked to this clinic.')->send();
                        return;
                    }

                    try {
                        $plainPassword = Str::random(12);
                        $user->update([
                            'password' => Hash::make($plainPassword),
                            'must_change_password' => true,
                        ]);

                        $user->notify(new SendGeneratedPassword($plainPassword));

                        Notification::make()->success()->title('Credentials sent successfully.')->send();
                    } catch (\Throwable $e) {
                        Notification::make()->danger()->title('Failed to send email.')->body($e->getMessage())->send();
                    }
                }),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Validate email uniqueness
        if (!empty($data['clinic_email'])) {
            $existingUser = User::where('email', $data['clinic_email'])
                ->where('id', '!=', $this->record->user_id)
                ->first();

            if ($existingUser) {
                Notification::make()
                    ->danger()
                    ->title('Email Already Exists')
                    ->body('A user with the email ' . $data['clinic_email'] . ' already exists.')
                    ->persistent()
                    ->send();

                $this->halt();
            }

            $clinicEmailTaken = \App\Models\Clinic::where('clinic_email', $data['clinic_email'])
                ->where('id', '!=', $this->record->id)
                ->exists();

            if ($clinicEmailTaken) {
                Notification::make()
                    ->danger()
                    ->title('Email Already Exists')
                    ->body('The email ' . $data['clinic_email'] . ' is already assigned to another clinic.')
                    ->persistent()
                    ->send();

                $this->halt();
            }
        }

        // Validate owner dentist exists
        if (!empty($data['dentists'])) {
            $hasOwner = collect($data['dentists'])->contains('is_owner', true);
            if (!$hasOwner) {
                Notification::make()
                    ->danger()
                    ->title('Owner Required')
                    ->body('At least one dentist must be marked as the clinic owner.')
                    ->persistent()
                    ->send();

                $this->halt();
            }
        }

        $this->servicesData = $data['services'] ?? [];
        unset($data['services']);

        if ($data['accreditation_status'] === 'SPECIFIC HIP') {
            $data['account_id'] = null;
        } elseif ($data['accreditation_status'] === 'SPECIFIC ACCOUNT') {
            $data['hip_id'] = null;
        }

        return $data;
    }

    protected function getSavedNotification(): ?Notification
    {
        return null; // handled manually in afterSave()
    }

    protected function afterSave(): void
    {
        $clinic = $this->record;
        $ownerDentist = $clinic->dentists()->where('is_owner', true)->first();

        // Create or update user for owner dentist
        if ($ownerDentist && !empty($clinic->clinic_email)) {
            if (!$clinic->user_id) {
                $plainPassword = Str::random(12);
                $user = User::create([
                    'name' => $ownerDentist->first_name . ' ' . $ownerDentist->last_name,
                    'email' => $clinic->clinic_email,
                    'password' => Hash::make($plainPassword),
                    'must_change_password' => true,
                ]);
                $user->assignRole('Dentist');
                $user->notify(new \App\Notifications\SendGeneratedPassword($plainPassword));
                $clinic->update(['user_id' => $user->id]);
            } else {
                $oldEmail = $clinic->user->email;
                $newEmail = $clinic->clinic_email;
                $clinic->user->update([
                    'name'  => $ownerDentist->first_name . ' ' . $ownerDentist->last_name,
                    'email' => $newEmail,
                ]);
                if ($oldEmail !== $newEmail) {
                    try {
                        $plainPassword = Str::random(12);
                        $clinic->user->update([
                            'password' => Hash::make($plainPassword),
                            'must_change_password' => true,
                        ]);
                        $clinic->user->notify(new SendGeneratedPassword($plainPassword));
                    } catch (\Throwable $e) {
                        Notification::make()->danger()->title('Email updated but failed to send credentials.')->body($e->getMessage())->send();
                    }
                }
            }
        }

        // Sync new fees (basic, enhancement, special)
        $basicFees       = $this->data['services']['basic_new_fee'] ?? [];
        $enhancementFees = $this->data['services']['enhancement_new_fee'] ?? [];
        $specialFees     = $this->data['services']['special_new_fee'] ?? [];

        $allNewFees = collect($basicFees)->union($enhancementFees)->union($specialFees)
            ->filter(fn($fee) => !is_null($fee) && $fee !== '')
            ->mapWithKeys(fn($fee, $serviceId) => [$serviceId => ['new_fee' => $fee]])
            ->toArray();

        $feesSubmitted = !empty($allNewFees);

        if ($feesSubmitted) {
            foreach ($allNewFees as $serviceId => $pivotData) {
                $clinic->services()->updateExistingPivot($serviceId, $pivotData);
            }

            $clinic->update(['fee_approval' => 'pending']);

            $approvalUrl     = \App\Filament\Pages\ServiceFeeApproval::getUrl();
            $clinicEditUrl   = ClinicsResource::getUrl('edit', ['record' => $clinic]);
            $clinicProfileUrl = \App\Filament\Pages\ClinicProfile::getUrl();

            $approvers = User::permission('fee.approval')->get();
            foreach ($approvers as $approver) {
                Notification::make()
                    ->title('Service Fee Update Pending')
                    ->body('Service fees for ' . $clinic->clinic_name . ' have been updated and require approval.')
                    ->warning()
                    ->actions([NotificationAction::make('view')->label('Review Fees')->url($approvalUrl)])
                    ->sendToDatabase($approver);
            }

            $accreditationUsers = User::permission('clinic.update')->where('id', '!=', auth()->id())->get();
            foreach ($accreditationUsers as $user) {
                Notification::make()
                    ->title('Clinic Fee Update Submitted')
                    ->body('Service fees for ' . $clinic->clinic_name . ' have been submitted for approval.')
                    ->info()
                    ->actions([NotificationAction::make('view')->label('View Clinic')->url($clinicEditUrl)])
                    ->sendToDatabase($user);
            }

            $clinicUser = $clinic->user;
            if ($clinicUser && $clinicUser->id !== auth()->id()) {
                Notification::make()
                    ->title('Service Fee Update Submitted')
                    ->body('Service fees for ' . $clinic->clinic_name . ' have been submitted for approval.')
                    ->info()
                    ->actions([NotificationAction::make('view')->label('View Profile')->url($clinicProfileUrl)])
                    ->sendToDatabase($clinicUser);
            }
        }

        // Toast for the current user
        if ($feesSubmitted) {
            $feeCount = count($allNewFees);
            Notification::make()
                ->success()
                ->title('Clinic Saved & Fees Submitted')
                ->body(
                    $clinic->clinic_name . ' has been updated. ' .
                    $feeCount . ' service fee' . ($feeCount > 1 ? 's have' : ' has') .
                    ' been submitted for approval.'
                )
                ->icon('heroicon-o-check-badge')
                ->persistent()
                ->send();
        } else {
            Notification::make()
                ->success()
                ->title('Clinic Saved')
                ->body($clinic->clinic_name . ' has been updated successfully.')
                ->icon('heroicon-o-building-storefront')
                ->send();
        }
    }
}
