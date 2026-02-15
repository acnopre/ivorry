<?php

namespace App\Filament\Resources\MblTypeResource\Pages;

use App\Filament\Resources\MblTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMblTypes extends ListRecords
{
    protected static string $resource = MblTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->modalHeading('Create MBL Type'),
        ];
    }
}
