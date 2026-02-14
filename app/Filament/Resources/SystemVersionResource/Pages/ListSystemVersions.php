<?php

namespace App\Filament\Resources\SystemVersionResource\Pages;

use App\Filament\Resources\SystemVersionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSystemVersions extends ListRecords
{
    protected static string $resource = SystemVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
