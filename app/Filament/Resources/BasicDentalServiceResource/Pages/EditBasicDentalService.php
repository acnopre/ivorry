<?php

namespace App\Filament\Resources\BasicDentalServiceResource\Pages;

use App\Filament\Resources\BasicDentalServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBasicDentalService extends EditRecord
{
    protected static string $resource = BasicDentalServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
