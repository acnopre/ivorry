<?php

namespace App\Filament\Resources\DentalPlanBenefitResource\Pages;

use App\Filament\Resources\DentalPlanBenefitResource;
use Filament\Resources\Pages\ListRecords;

class ListDentalPlanBenefits extends ListRecords
{
    protected static string $resource = DentalPlanBenefitResource::class;

    protected function getHeaderWidgets(): array
    {
        return [];
    }
}
