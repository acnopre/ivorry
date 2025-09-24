<?php

namespace App\Filament\Resources\AccreditationStatusResource\Pages;

use App\Filament\Resources\AccreditationStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAccreditationStatuses extends ListRecords
{
    protected static string $resource = AccreditationStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
