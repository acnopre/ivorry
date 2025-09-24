<?php

namespace App\Filament\Resources\TaxTypeResource\Pages;

use App\Filament\Resources\TaxTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTaxTypes extends ListRecords
{
    protected static string $resource = TaxTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
