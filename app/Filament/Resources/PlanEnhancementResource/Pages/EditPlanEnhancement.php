<?php

namespace App\Filament\Resources\PlanEnhancementResource\Pages;

use App\Filament\Resources\PlanEnhancementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlanEnhancement extends EditRecord
{
    protected static string $resource = PlanEnhancementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
