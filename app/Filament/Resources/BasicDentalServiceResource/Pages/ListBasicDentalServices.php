<?php

namespace App\Filament\Resources\BasicDentalServiceResource\Pages;

use App\Filament\Resources\BasicDentalServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBasicDentalServices extends ListRecords
{
    protected static string $resource = BasicDentalServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
