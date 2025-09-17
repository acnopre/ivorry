<?php

namespace App\Filament\Resources\DentalPlanBenefitResource\Pages;

use App\Filament\Resources\DentalPlanBenefitResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDentalPlanBenefit extends EditRecord
{
    protected static string $resource = DentalPlanBenefitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
