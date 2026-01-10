<?php

namespace App\Filament\Resources\HipResource\Pages;

use App\Filament\Resources\HipResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHips extends ListRecords
{
    protected static string $resource = HipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
