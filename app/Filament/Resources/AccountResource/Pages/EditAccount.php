<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAccount extends EditRecord
{
    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['basic_dental_services'], $data['plan_enhancements']);
        return $data;
    }

    protected function afterSave(): void
    {
        $dentist = $this->record;

        // Update only changed Basic Dental Services
        $basicServices = $this->form->getState()['basic_dental_services'] ?? [];
        foreach ($basicServices as $serviceId => $quantity) {
            if ($quantity !== null && $quantity !== '') {
                $currentQuantity = DB::table('dentist_basic_dental_service')
                    ->where('dentist_id', $dentist->id)
                    ->where('basic_dental_service_id', $serviceId)
                    ->value('quantity');

                if ($currentQuantity != $quantity) {
                    DB::table('dentist_basic_dental_service')->updateOrInsert(
                        [
                            'dentist_id' => $dentist->id,
                            'basic_dental_service_id' => $serviceId,
                        ],
                        [
                            'quantity' => $quantity,
                        ]
                    );
                }
            }
        }

        // Update only changed Plan Enhancements
        $planEnhancements = $this->form->getState()['plan_enhancements'] ?? [];
        foreach ($planEnhancements as $enhancementId => $quantity) {
            if ($quantity !== null && $quantity !== '') {
                $currentQuantity = DB::table('dentist_plan_enhancement')
                    ->where('dentist_id', $dentist->id)
                    ->where('plan_enhancement_id', $enhancementId)
                    ->value('quantity');

                if ($currentQuantity != $quantity) {
                    DB::table('dentist_plan_enhancement')->updateOrInsert(
                        [
                            'dentist_id' => $dentist->id,
                            'plan_enhancement_id' => $enhancementId,
                        ],
                        [
                            'quantity' => $quantity,
                        ]
                    );
                }
            }
        }
    }
}
