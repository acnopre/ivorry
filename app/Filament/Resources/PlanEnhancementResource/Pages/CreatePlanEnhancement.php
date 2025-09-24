<?php

namespace App\Filament\Resources\PlanEnhancementResource\Pages;

use App\Filament\Resources\PlanEnhancementResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePlanEnhancement extends CreateRecord
{
    protected static string $resource = PlanEnhancementResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
