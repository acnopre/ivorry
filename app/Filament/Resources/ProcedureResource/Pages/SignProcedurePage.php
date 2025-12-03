<?php

namespace App\Filament\Resources\ProcedureResource\Pages;

use App\Filament\Resources\ProcedureResource;
use App\Models\Member;
use App\Models\Procedure;
use App\Models\ProcedureSignature;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SignProcedurePage extends Page
{
    protected static string $resource = ProcedureResource::class;
    protected static string $view = 'filament.resources.procedure-resource.pages.sign-procedure-page';

    public Procedure $record;

    /**
     * @var array Holds signatures and the 'memberUnavailable' flag from the frontend.
     */
    public array $signatures = [];

    public function mount(Procedure $record): void
    {
        $this->record = $record;
    }

    /**
     * Handles the submission of signatures.
     */
    public function signNow(): void
    {
        $isMemberUnavailable = $this->signatures['memberUnavailable'] ?? false;
        // $dentistSignature = $this->signatures['dentist'] ?? null;
        $memberSignature = $this->signatures['member'] ?? null;

        // --- 1. Server-Side Validation ---

        // Dentist signature is always mandatory.
        // if (empty($dentistSignature)) {
        //     Notification::make()
        //         ->title('Missing Signature')
        //         ->body('The **Dentist** signature is required to complete the procedure.')
        //         ->danger()
        //         ->send();
        //     return;
        // }

        // Member signature is required unless the unavailable flag is checked.
        if (!$isMemberUnavailable && empty($memberSignature)) {
            Notification::make()
                ->title('Missing Signature')
                ->body('The Member signature is required, or you must check the "Member not available" box.')
                ->danger()
                ->send();
            return;
        }

        // --- 2. Process and Store Signatures ---

        // Collect only the signatures that contain data (i.e., not null)
        $signaturesToStore = [];
        // if ($dentistSignature) {
        //     $signaturesToStore['dentist'] = $dentistSignature;
        // }
        if ($memberSignature) {
            $signaturesToStore['member'] = $memberSignature;
        }

        foreach ($signaturesToStore as $type => $base64) {
            // Remove the data URI header (e.g., 'data:image/png;base64,')
            $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64));

            if ($imageData === false) {
                // Throwing an exception is cleaner than just failing silently
                throw ValidationException::withMessages([
                    'signatures' => 'Could not decode image data for ' . $type . '. Please try signing again.',
                ]);
            }

            $filePath = "signatures/{$type}_" . uniqid() . '.png';
            Storage::disk('public')->put($filePath, $imageData);

            // Determine signer name for auditing
            $signerName = ($type === 'dentist')
                ? ($this->record->dentist->full_name ?? 'Dentist')
                : ($this->record->member->full_name ?? 'Member');

            ProcedureSignature::create([
                'procedure_id'   => $this->record->id,
                'signer_name'    => $signerName,
                'signer_type'    => $type,
                'signature_path' => $filePath,
            ]);
        }

        // --- 3. Handle Member Unavailable Audit Record ---

        if ($isMemberUnavailable) {
            ProcedureSignature::create([
                'procedure_id'   => $this->record->id,
                'signer_name'    => $this->record->dentist->full_name ?? 'Dentist',
                'signer_type'    => 'member_unavailable_waiver', // Specific type for auditing
                'signature_path' => null, // No file path
                'notes'          => 'Member was explicitly marked unavailable. Dentist attested to the procedure.',
            ]);
        }
        // --- 4. Finalize Procedure ---
        $this->record->update(['status' => 'completed']);

        // 🧮 Deduct service quantity from account_service pivot
        $member = Member::find($this->record->member_id);

        if ($member && $member->account) {
            $account = $member->account;
            $serviceId = $this->record->service_id;
            $quantityUsed = $this->record->quantity ?? null;

            $pivot = $account->services()
                ->where('service_id', $serviceId)
                ->first()
                ?->pivot;

            if ($pivot) {
                // Skip deduction if unlimited
                if (!$pivot->is_unlimited) {
                    $newQuantity = max(0, $pivot->quantity - $quantityUsed);

                    $account->services()->updateExistingPivot($serviceId, [
                        'quantity' => $newQuantity,
                    ]);
                }
            }
        }

        Notification::make()
            ->title('Procedure Signed Successfully! 🎉')
            ->body('All required documentation has been saved. This procedure is now marked as completed.')
            ->success()
            ->send();

        $this->redirect(ProcedureResource::getUrl('index'));
    }
}
