<?php

namespace App\Filament\Resources\SoaResource\Pages;

use App\Filament\Resources\SoaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSoas extends ListRecords
{
    protected static string $resource = SoaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
