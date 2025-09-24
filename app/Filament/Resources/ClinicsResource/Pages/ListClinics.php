<?php

namespace App\Filament\Resources\ClinicsResource\Pages;

use App\Filament\Resources\ClinicsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClinics extends ListRecords
{
    protected static string $resource = ClinicsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
