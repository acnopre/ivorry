<?php

namespace App\Filament\Resources\ClinicsResource\Pages;

use App\Filament\Resources\ClinicsResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditClinics extends EditRecord
{
    protected static string $resource = ClinicsResource::class;

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
        foreach ($basicServices as $serviceId => $fee) {
            if ($fee !== null && $fee !== '') {
                $currentFee = DB::table('dentist_basic_dental_service')
                    ->where('dentist_id', $dentist->id)
                    ->where('basic_dental_service_id', $serviceId)
                    ->value('fee');

                if ($currentFee != $fee) {
                    DB::table('dentist_basic_dental_service')->updateOrInsert(
                        [
                            'dentist_id' => $dentist->id,
                            'basic_dental_service_id' => $serviceId,
                        ],
                        [
                            'fee' => $fee,
                        ]
                    );
                }
            }
        }

        // Update only changed Plan Enhancements
        $planEnhancements = $this->form->getState()['plan_enhancements'] ?? [];
        foreach ($planEnhancements as $enhancementId => $fee) {
            if ($fee !== null && $fee !== '') {
                $currentFee = DB::table('dentist_plan_enhancement')
                    ->where('dentist_id', $dentist->id)
                    ->where('plan_enhancement_id', $enhancementId)
                    ->value('fee');

                if ($currentFee != $fee) {
                    DB::table('dentist_plan_enhancement')->updateOrInsert(
                        [
                            'dentist_id' => $dentist->id,
                            'plan_enhancement_id' => $enhancementId,
                        ],
                        [
                            'fee' => $fee,
                        ]
                    );
                }
            }
        }
    }
}
