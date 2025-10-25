<?php

namespace App\Filament\Resources\ProcedureResource\Pages;

use App\Filament\Resources\ProcedureResource;
use App\Models\Procedure;
use App\Models\ProcedureSignature;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class SignProcedurePage extends Page
{
    protected static string $resource = ProcedureResource::class;
    protected static string $view = 'filament.resources.procedure-resource.pages.sign-procedure-page';

    public Procedure $record;
    public array $signatures = [];

    public function mount(Procedure $record): void
    {
        $this->record = $record;
    }

    public function signNow(): void
    {
        if (empty($this->signatures['dentist']) || empty($this->signatures['member'])) {
            Notification::make()
                ->title('Missing Signatures')
                ->body('Please provide all three signatures before submitting.')
                ->danger()
                ->send();
            return;
        }

        foreach ($this->signatures as $type => $base64) {
            $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64));
            $filePath = "signatures/{$type}_" . uniqid() . '.png';
            Storage::disk('public')->put($filePath, $imageData);

            ProcedureSignature::create([
                'procedure_id' => $this->record->id,
                'signer_name'  => ucfirst($type),
                'signer_type'  => $type,
                'signature_path' => $filePath,
            ]);
        }

        $this->record->update(['status' => 'completed']);

        Notification::make()
            ->title('Procedure Signed Successfully!')
            ->body('All signatures have been saved. This procedure is now marked as completed.')
            ->success()
            ->send();

        $this->redirect(ProcedureResource::getUrl('index'));
    }
}
